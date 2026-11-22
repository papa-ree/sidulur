<?php

namespace Bale\Sidulur\Livewire\Pages\Pendaftaran;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Models\Region;
use Bale\Sidulur\Livewire\Concerns\RegistersSubmission;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Pendaftaran Baru')]
class Create extends Component
{
    use RegistersSubmission;
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

    public array $formData = [];

    public array $requirementData = [];

    public array $requirementFiles = [];

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
        $this->authorizeCreate();

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
        $this->authorizeCreate();

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
        $this->authorizeCreate();

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'phone' => ['required', 'string', 'max:20'],
            'nomor_kk' => ['required', 'digits:16'],
            'district_code' => ['required', 'string'],
            'village_code' => ['required', 'string'],
            'alamat' => ['required', 'string', 'max:500'],
            'ktp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'kk' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $citizen = $registrar->register($data, $this->ktp, $this->kk);

        $this->selectCitizen($citizen->getKey());
    }

    public function selectService(string $id): void
    {
        $this->authorizeCreate();

        if ($this->serviceId === $id) {
            $this->step = 3;

            return;
        }

        $service = Service::query()->with('form')->where('is_active', true)->findOrFail($id);

        $this->serviceId = $service->getKey();
        $this->formData = [];
        $this->requirementData = [];
        $this->requirementFiles = [];

        // Livewire menambahkan berkas ke array yang sudah ada; tanpa kunci
        // kosong, unggahan pertama menjadi satu file (bukan array) dan
        // validasi `array` gagal.
        foreach ($this->requirements($service) as $row) {
            if (($row['type'] ?? 'file') === 'file') {
                $this->requirementFiles[$row['key']] = [];
            }
        }

        $this->step = 3;
    }

    public function finishDesk(SubmissionFlow $flow)
    {
        $this->authorizeCreate();

        $pendaftaran = $this->register($flow);

        $flow->finishAtDesk($pendaftaran);

        return redirect()->route('sidulur.pendaftaran.show', $pendaftaran->getKey());
    }

    public function forward(SubmissionFlow $flow)
    {
        $this->authorizeCreate();

        $pendaftaran = $this->register($flow);

        $flow->forward($pendaftaran);

        return redirect()->route('sidulur.pendaftaran.show', $pendaftaran->getKey());
    }

    public function render()
    {
        $service = $this->serviceId ? Service::query()->with('form')->find($this->serviceId) : null;

        return view('sidulur::livewire.pages.pendaftaran.create', [
            'citizen' => $this->citizenId ? $this->citizen() : null,
            'identityMissing' => $this->identityMissing(),
            'service' => $service,
            'requirements' => $this->requirements($service),
            'services' => Service::query()->with('bidang')->where('is_active', true)->orderBy('sort')->orderBy('nama')->get(),
            'districts' => Region::districts()->map(fn ($d) => ['value' => $d->district_code, 'label' => $d->district_name])->all(),
            'villages' => $this->district_code
                ? Region::villages($this->district_code)->map(fn ($v) => ['value' => $v->village_code, 'label' => $v->village_name])->all()
                : [],
        ]);
    }
}
