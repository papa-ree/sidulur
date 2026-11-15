<?php

namespace Bale\Sidulur\Services;

use Bale\Cms\Services\TenantManager;
use Bale\Core\Models\Region;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Support\BlindIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CitizenRegistrar
{
    public function __construct(protected DocumentService $documents) {}

    public function find(string $identity): ?Citizen
    {
        return Citizen::query()->withIdentity($identity)->with('familyCard')->first();
    }

    /**
     * @param  array{nama: string, nik: string, phone?: ?string, nomor_kk: string, district_code: string, village_code: string, alamat: string}  $data
     * @param  array<int, array{nik?: ?string, nama?: ?string, ktp?: ?UploadedFile, kk?: ?UploadedFile}>  $members  anggota keluarga baru pada KK yang sama
     */
    public function register(array $data, ?UploadedFile $ktp = null, ?UploadedFile $kk = null, ?Citizen $citizen = null, array $members = []): Citizen
    {
        $nikHash = BlindIndex::make($data['nik']);
        $kkHash = BlindIndex::make($data['nomor_kk']);
        $phoneHash = BlindIndex::make($data['phone'] ?? null);

        $this->assertUnique('nik_hash', $nikHash, 'nik', 'NIK sudah terdaftar.', $citizen);
        $this->assertUnique('phone_hash', $phoneHash, 'phone', 'Nomor HP sudah terdaftar.', $citizen);

        return TenantManager::connection()->transaction(function () use ($data, $ktp, $kk, $citizen, $kkHash, $members) {
            $card = FamilyCard::withTrashed()->where('nomor_kk_hash', $kkHash)->first();

            if ($card?->trashed()) {
                throw ValidationException::withMessages(['nomor_kk' => 'Nomor KK sudah terdaftar pada data terhapus.']);
            }

            if (! $card) {
                $district = Region::query()->where('district_code', $data['district_code'])->firstOrFail();
                $village = Region::query()->where('village_code', $data['village_code'])->firstOrFail();

                $card = FamilyCard::create([
                    'nomor_kk' => $data['nomor_kk'],
                    'district_code' => $district->district_code,
                    'district_name' => $district->district_name,
                    'village_code' => $village->village_code,
                    'village_name' => $village->village_name,
                    'alamat' => $data['alamat'],
                ]);
            }

            $citizen ??= new Citizen;

            $citizen->fill([
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
                'family_card_id' => $card->getKey(),
            ])->save();

            if ($ktp) {
                $this->replaceDocument($citizen, $ktp, 'ktp');
            }

            if ($kk) {
                $this->replaceDocument($card, $kk, 'kk');
            }

            foreach ($members as $index => $member) {
                $this->registerMember($card, $member, (int) $index);
            }

            return $citizen;
        });
    }

    /**
     * Anggota keluarga baru: satu NIK = satu citizen pada KK yang sama.
     * KTP menempel pada warga; KK hanya diunggah bila kartu belum punya KK.
     *
     * @param  array{nik?: ?string, nama?: ?string, ktp?: ?UploadedFile, kk?: ?UploadedFile}  $member
     */
    protected function registerMember(FamilyCard $card, array $member, int $index): void
    {
        $nik = BlindIndex::normalize($member['nik'] ?? null);

        if ($nik === null) {
            return;
        }

        if (Citizen::withTrashed()->where('nik_hash', BlindIndex::make($nik))->exists()) {
            throw ValidationException::withMessages([
                "anggota.$index.nik" => 'NIK anggota sudah terdaftar.',
            ]);
        }

        $citizen = Citizen::query()->create([
            'nama' => $member['nama'] ?? null,
            'nik' => $nik,
            'family_card_id' => $card->getKey(),
        ]);

        if ($ktp = $member['ktp'] ?? null) {
            $this->replaceDocument($citizen, $ktp, 'ktp');
        }

        if (($kk = $member['kk'] ?? null) && ! $card->documents()->where('kind', 'kk')->exists()) {
            $this->documents->upload($card, $kk, 'kk');
        }
    }

    protected function replaceDocument(Model $owner, UploadedFile $file, string $kind): void
    {
        $owner->documents()->where('kind', $kind)->get()->each(fn ($doc) => $this->documents->delete($doc));

        $this->documents->upload($owner, $file, $kind);
    }

    protected function assertUnique(string $column, ?string $hash, string $field, string $message, ?Citizen $citizen): void
    {
        if (! $hash) {
            return;
        }

        $exists = Citizen::withTrashed()
            ->where($column, $hash)
            ->when($citizen, fn ($q) => $q->whereKeyNot($citizen->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
