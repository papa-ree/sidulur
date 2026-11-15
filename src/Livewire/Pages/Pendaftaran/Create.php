<?php

namespace Bale\Sidulur\Livewire\Pages\Pendaftaran;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Models\Region;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Pendaftaran Baru')]
class Create extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $identity = '';

    public array $results = [];

    public ?string $citizenId = null;

    public bool $showNewCitizen = false;

    public string $nama = '';

    public string $nik = '';

    public string $phone = '';

    public string $nomor_kk = '';

    public string $district_code = '';

    public string $village_code = '';

    public string $alamat = '';

    public $ktp;

    public $kk;

    public ?string $serviceId = null;

    public string $keperluan = '';

    public array $formData = [];

    public array $requirements = [];

    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PENDAFTARAN_CREATE), 403);

        if (request()->filled('citizen')) {
            $citizen = Citizen::query()->find(request('citizen'));

            if ($citizen) {
                $this->selectCitizen($citizen->getKey());
            }
        }
    }

    public function search(CitizenRegistrar $registrar): void
    {
        $this->validate(['identity' => ['required', 'string', 'min:3']]);

        $found = $registrar->find($this->identity);

        $this->results = $found
            ? [['id' => $found->getKey(), 'nama' => $found->nama, 'nik' => $found->maskedNik()]]
            : [];

        if (! $found) {
            $this->showNewCitizen = true;
        }
    }

    public function selectCitizen(string $id): void
    {
        $this->citizenId = $id;
        $this->step = 2;
        $this->showNewCitizen = false;
    }

    public function updatedDistrictCode(): void
    {
        $this->village_code = '';
    }

    public function createCitizen(CitizenRegistrar $registrar): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'phone' => ['nullable', 'string', 'max:20'],
            'nomor_kk' => ['required', 'digits:16'],
            'district_code' => ['required', 'string'],
            'village_code' => ['required', 'string'],
            'alamat' => ['required', 'string', 'max:500'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $citizen = $registrar->register($data, $this->ktp, $this->kk);

        $this->selectCitizen($citizen->getKey());
    }

    public function selectService(string $id): void
    {
        $service = Service::query()->with('form')->where('is_active', true)->findOrFail($id);
        $citizen = $this->citizen();

        $this->serviceId = $service->getKey();
        $this->formData = [];
        $this->requirements = FormSchema::masterRequirements(
            $service->form?->schema ?? [],
            $citizen->documents()->where('kind', 'ktp')->exists(),
            $citizen->familyCard->documents()->where('kind', 'kk')->exists(),
            filled($citizen->phone),
        );
        $this->step = 3;
    }

    public function finishDesk(SubmissionFlow $flow)
    {
        $pendaftaran = $this->register($flow);

        $flow->finishAtDesk($pendaftaran);

        return redirect()->route('sidulur.pendaftaran.show', $pendaftaran->getKey());
    }

    public function forward(SubmissionFlow $flow)
    {
        $pendaftaran = $this->register($flow);

        $flow->forward($pendaftaran);

        return redirect()->route('sidulur.pendaftaran.show', $pendaftaran->getKey());
    }

    protected function register(SubmissionFlow $flow)
    {
        abort_unless($this->citizenId && $this->serviceId, 404);

        $service = Service::query()->with('form')->findOrFail($this->serviceId);

        $this->validate([
            'keperluan' => ['nullable', 'string', 'max:1000'],
            'formData' => ['array'],
        ]);

        return $flow->register(
            $this->citizen(),
            $service,
            $this->keperluan ?: null,
            $this->formData
        );
    }

    protected function citizen(): Citizen
    {
        return Citizen::query()->with(['familyCard.documents', 'documents'])->findOrFail($this->citizenId);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.pendaftaran.create', [
            'citizen' => $this->citizenId ? $this->citizen() : null,
            'service' => $this->serviceId ? Service::query()->with('form')->find($this->serviceId) : null,
            'services' => Service::query()->with('bidang')->where('is_active', true)->orderBy('sort')->orderBy('nama')->get(),
            'districts' => Region::districts()->map(fn ($d) => ['value' => $d->district_code, 'label' => $d->district_name])->all(),
            'villages' => $this->district_code
                ? Region::villages($this->district_code)->map(fn ($v) => ['value' => $v->village_code, 'label' => $v->village_name])->all()
                : [],
        ]);
    }
}
