<?php

use Bale\Cms\Middleware\EnsureBaleSelected;
use Bale\Cms\Middleware\SwitchBaleConnection;
use Bale\Sidulur\Http\Controllers\DocumentController;
use Bale\Sidulur\Livewire\Pages\Bidang\Form as BidangForm;
use Bale\Sidulur\Livewire\Pages\Bidang\Index as BidangIndex;
use Bale\Sidulur\Livewire\Pages\Citizen\Form as CitizenForm;
use Bale\Sidulur\Livewire\Pages\Citizen\Index as CitizenIndex;
use Bale\Sidulur\Livewire\Pages\Citizen\Show as CitizenShow;
use Bale\Sidulur\Livewire\Pages\Form\Builder as FormBuilder;
use Bale\Sidulur\Livewire\Pages\Form\Index as FormIndex;
use Bale\Sidulur\Livewire\Pages\Officer\Form as OfficerForm;
use Bale\Sidulur\Livewire\Pages\Officer\Index as OfficerIndex;
use Bale\Sidulur\Livewire\Pages\Overview\Index as OverviewIndex;
use Bale\Sidulur\Livewire\Pages\Pelayanan\Index as PelayananIndex;
use Bale\Sidulur\Livewire\Pages\Pelayanan\Show as PelayananShow;
use Bale\Sidulur\Livewire\Pages\Pendaftaran\Create as PendaftaranCreate;
use Bale\Sidulur\Livewire\Pages\Pendaftaran\Index as PendaftaranIndex;
use Bale\Sidulur\Livewire\Pages\Pendaftaran\Show as PendaftaranShow;
use Bale\Sidulur\Livewire\Pages\Service\Form as ServiceForm;
use Bale\Sidulur\Livewire\Pages\Service\Index as ServiceIndex;
use Bale\Sidulur\Livewire\Pages\Setting\Index as SettingIndex;
use Bale\Sidulur\Livewire\Pages\Verlap\Index as VerlapIndex;
use Bale\Sidulur\Livewire\Pages\Verlap\Show as VerlapShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('cms/sidulur')->name('sidulur.')->group(function () {
    Route::middleware([EnsureBaleSelected::class, SwitchBaleConnection::class])->group(function () {
        Route::get('/overview', OverviewIndex::class)->name('overview');

        Route::get('/citizens', CitizenIndex::class)->name('citizen.index');
        Route::get('/citizens/form/{citizen?}', CitizenForm::class)->name('citizen.form');
        Route::get('/citizens/{citizen}', CitizenShow::class)->name('citizen.show');

        Route::get('/bidangs', BidangIndex::class)->name('bidang.index');
        Route::get('/bidangs/form/{bidang?}', BidangForm::class)->name('bidang.form');
        Route::get('/services', ServiceIndex::class)->name('service.index');
        Route::get('/services/form/{service?}', ServiceForm::class)->name('service.form');
        Route::get('/forms', FormIndex::class)->name('form.index');
        Route::get('/forms/{service}', FormBuilder::class)->name('form.edit');
        Route::get('/officers', OfficerIndex::class)->name('officer.index');
        Route::get('/officers/form/{officer?}', OfficerForm::class)->name('officer.form');

        Route::get('/pendaftarans', PendaftaranIndex::class)->name('pendaftaran.index');
        Route::get('/pendaftarans/create', PendaftaranCreate::class)->name('pendaftaran.create');
        Route::get('/pendaftarans/{pendaftaran}', PendaftaranShow::class)->name('pendaftaran.show');

        Route::get('/pelayanan', PelayananIndex::class)->name('pelayanan.index');
        Route::get('/pelayanan/{pelayanan}', PelayananShow::class)->name('pelayanan.show');

        Route::get('/verlap', VerlapIndex::class)->name('verlap.index');
        Route::get('/verlap/{pelayanan}', VerlapShow::class)->name('verlap.show');

        Route::get('/settings', SettingIndex::class)->name('setting.index');

        Route::get('/media/{document}', DocumentController::class)->name('media');
    });
});
