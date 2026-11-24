<?php

namespace Bale\Sidulur\Livewire\SharedComponents\Chat;

use Bale\Sidulur\Livewire\Concerns\RegistersSubmission;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\BlindIndex;
use Bale\Vada\Models\VadaConversation;
use Bale\Vada\Models\VadaMessage;
use Bale\Vada\Support\Media;
use Bale\Vada\Support\PhoneIndex;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form layanan di panel kanan chat: dipasang lewat `panelView` ContactContext.
 *
 * Bukan halaman, jadi tidak ada redirect: tiket tampil di panel dan riwayat
 * pemohon diminta render ulang lewat event `vada:context-refresh`.
 */
class ServiceForm extends Component
{
    use RegistersSubmission;
    use WithFileUploads;

    #[Locked]
    public string $phone = '';

    #[Locked]
    public ?string $citizenId = null;

    #[Locked]
    public ?string $conversationId = null;

    public ?string $serviceId = null;

    public array $formData = [];

    public array $requirementData = [];

    public array $requirementFiles = [];

    /** kode persyaratan => id pesan media chat yang dipakai. */
    public array $requirementMedia = [];

    public ?string $ticket = null;

    public ?string $ticketUrl = null;

    public function mount(string $phone): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PENDAFTARAN_CREATE), 403);

        // Picker warga menampilkan nama dan NIK, jadi izin Data Warga ikut disyaratkan.
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_VIEW), 403);

        $this->phone = $phone;

        $citizens = $this->citizens();

        abort_if($citizens->isEmpty(), 404);

        // Nomor bersama: petugas memilih warga dulu sebelum memilih layanan.
        $this->citizenId = $citizens->count() === 1 ? $citizens->first()->getKey() : null;

        $this->conversationId = VadaConversation::query()
            ->where('contact_phone_hash', PhoneIndex::make($phone))
            ->value('id');
    }

    public function selectCitizen(string $id): void
    {
        $this->authorizeCreate();

        abort_unless($this->citizens()->contains('id', $id), 404);

        $this->citizenId = $id;
        $this->changeService();
    }

    public function changeCitizen(): void
    {
        $this->authorizeCreate();

        $this->citizenId = null;
        $this->changeService();
    }

    /**
     * @return Collection<int, Citizen>
     */
    protected function citizens()
    {
        return Citizen::query()
            ->where('phone_hash', BlindIndex::make($this->phone))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nik', 'is_active']);
    }

    protected function assertCitizenChosen(): void
    {
        if ($this->citizenId === null) {
            throw ValidationException::withMessages(['citizen' => __('Pilih warga terlebih dahulu.')]);
        }
    }

    public function selectService(string $id): void
    {
        $this->authorizeCreate();

        $service = Service::query()->with('form')->where('is_active', true)->findOrFail($id);

        $this->serviceId = $service->getKey();
        $this->ticket = null;
        $this->ticketUrl = null;
        $this->formData = [];
        $this->requirementData = [];
        $this->requirementFiles = [];
        $this->requirementMedia = [];

        // Livewire menambahkan berkas ke array yang sudah ada; tanpa kunci
        // kosong, unggahan pertama menjadi satu file (bukan array) dan
        // validasi `array` gagal.
        foreach ($this->requirements($service) as $row) {
            if (($row['type'] ?? 'file') === 'file') {
                $this->requirementFiles[$row['key']] = [];
            }
        }

        $this->resetValidation();
    }

    public function changeService(): void
    {
        $this->authorizeCreate();

        $this->serviceId = null;
        $this->formData = [];
        $this->requirementData = [];
        $this->requirementFiles = [];
        $this->requirementMedia = [];
        $this->resetValidation();
    }

    public function finishDesk(SubmissionFlow $flow): void
    {
        $this->authorizeCreate();
        $this->assertCitizenChosen();

        $pendaftaran = $this->register($flow, $this->mediaFiles());

        $flow->finishAtDesk($pendaftaran);

        $this->saved($pendaftaran);
    }

    public function forward(SubmissionFlow $flow): void
    {
        $this->authorizeCreate();
        $this->assertCitizenChosen();

        $pendaftaran = $this->register($flow, $this->mediaFiles());

        $flow->forward($pendaftaran);

        $this->saved($pendaftaran);
    }

    protected function saved(Pendaftaran $pendaftaran): void
    {
        $this->ticket = $pendaftaran->ticket_number;
        $this->ticketUrl = route('sidulur.pendaftaran.show', $pendaftaran->getKey());

        $this->serviceId = null;
        $this->formData = [];
        $this->requirementData = [];
        $this->requirementFiles = [];
        $this->requirementMedia = [];
        $this->resetValidation();

        // Panel buka/tutup dikelola Alpine agar tidak perlu round trip.
        $this->dispatch('sidulur-service-closed');
        $this->dispatch('vada:context-refresh');
    }

    /**
     * Berkas dari media chat. Hanya persyaratan bertipe file dan hanya pesan
     * masuk pada percakapan ini; hasilnya disalin, bukan dirujuk.
     *
     * @return array<string, UploadedFile>
     */
    protected function mediaFiles(): array
    {
        $allowed = collect($this->requirements())
            ->filter(fn (array $row) => ($row['type'] ?? 'file') === 'file')
            ->pluck('key')
            ->all();

        $files = [];

        foreach ($this->requirementMedia as $kode => $messageId) {
            if (! in_array($kode, $allowed, true) || ! is_string($messageId) || $messageId === '') {
                continue;
            }

            $message = $this->mediaQuery()->whereKey($messageId)->first();
            $file = $message ? $this->mediaFile($message) : null;

            if ($file) {
                $files[$kode] = $file;
            }
        }

        return $files;
    }

    protected function mediaFile(VadaMessage $message): ?UploadedFile
    {
        $disk = Storage::disk((string) config('vada.media.disk', 'vada'));

        if (! $message->media_path || ! $disk->exists($message->media_path)) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'sidulur-chat-');

        if ($path === false) {
            return null;
        }

        file_put_contents($path, (string) $disk->get($message->media_path));

        return new UploadedFile(
            $path,
            $message->media_name ?: basename($message->media_path),
            $message->media_mime ?: Media::mimeFor($message->media_name ?: $message->media_path),
            null,
            true,
        );
    }

    protected function mediaQuery()
    {
        return VadaMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->where('direction', VadaMessage::DIRECTION_IN)
            ->where('media_status', VadaMessage::MEDIA_READY)
            ->whereIn('media_type', ['image', 'document']);
    }

    public function render()
    {
        // Daftar dimuat sejak render awal supaya tombol "Tambah layanan" bisa
        // membuka panel tanpa round trip.
        $service = $this->serviceId
            ? Service::query()->with('form')->find($this->serviceId)
            : null;

        $citizens = $this->citizens();

        // Hanya media hari ini yang ditawarkan; media hari lain yang sudah
        // terpilih tetap ikut agar tombol Batal tidak hilang.
        $mediaMessages = $service
            ? $this->mediaQuery()->whereDate('created_at', today())->orderByDesc('created_at')->limit(9)->get()
            : collect();

        if ($service && $this->requirementMedia !== []) {
            $mediaMessages = $mediaMessages
                ->merge($this->mediaQuery()->whereKey(array_values($this->requirementMedia))->get())
                ->unique('id');
        }

        return view('sidulur::livewire.shared-components.chat.service-form', [
            'services' => $service
                ? collect()
                : Service::query()
                    ->with('bidang:id,nama')
                    ->where('is_active', true)
                    ->orderBy('sort')
                    ->orderBy('nama')
                    ->get(['id', 'bidang_id', 'nama', 'icon', 'sort']),
            'service' => $service,
            'requirements' => $service ? $this->requirements($service) : [],
            'identityMissing' => $service ? $this->identityMissing() : [],
            'mediaMessages' => $mediaMessages,
            'citizens' => $citizens->count() > 1 ? $citizens : collect(),
            'citizen' => $this->citizenId ? $citizens->firstWhere('id', $this->citizenId) : null,
        ]);
    }
}
