<?php

namespace Bale\Sidulur;

class SidulurPermissions
{
    const VIEW = 'sidulur.read';

    const CITIZEN_VIEW = 'sidulur.citizen.view';

    const CITIZEN_CREATE = 'sidulur.citizen.create';

    const CITIZEN_UPDATE = 'sidulur.citizen.update';

    const CITIZEN_DELETE = 'sidulur.citizen.delete';

    const BIDANG_VIEW = 'sidulur.bidang.view';

    const BIDANG_CREATE = 'sidulur.bidang.create';

    const BIDANG_UPDATE = 'sidulur.bidang.update';

    const BIDANG_DELETE = 'sidulur.bidang.delete';

    const SERVICE_VIEW = 'sidulur.service.view';

    const SERVICE_CREATE = 'sidulur.service.create';

    const SERVICE_UPDATE = 'sidulur.service.update';

    const SERVICE_DELETE = 'sidulur.service.delete';

    const FORM_VIEW = 'sidulur.form.view';

    const FORM_UPDATE = 'sidulur.form.update';

    const OFFICER_VIEW = 'sidulur.officer.view';

    const OFFICER_CREATE = 'sidulur.officer.create';

    const OFFICER_UPDATE = 'sidulur.officer.update';

    const OFFICER_DELETE = 'sidulur.officer.delete';

    const PENDAFTARAN_VIEW = 'sidulur.pendaftaran.view';

    const PENDAFTARAN_CREATE = 'sidulur.pendaftaran.create';

    const PELAYANAN_VIEW = 'sidulur.pelayanan.view';

    const PELAYANAN_UPDATE = 'sidulur.pelayanan.update';

    const VERLAP_VIEW = 'sidulur.verlap.view';

    const VERLAP_UPDATE = 'sidulur.verlap.update';

    const SETTING_VIEW = 'sidulur.setting.view';

    const SETTING_UPDATE = 'sidulur.setting.update';

    const ALL = [
        self::VIEW,
        self::CITIZEN_VIEW,
        self::CITIZEN_CREATE,
        self::CITIZEN_UPDATE,
        self::CITIZEN_DELETE,
        self::BIDANG_VIEW,
        self::BIDANG_CREATE,
        self::BIDANG_UPDATE,
        self::BIDANG_DELETE,
        self::SERVICE_VIEW,
        self::SERVICE_CREATE,
        self::SERVICE_UPDATE,
        self::SERVICE_DELETE,
        self::FORM_VIEW,
        self::FORM_UPDATE,
        self::OFFICER_VIEW,
        self::OFFICER_CREATE,
        self::OFFICER_UPDATE,
        self::OFFICER_DELETE,
        self::PENDAFTARAN_VIEW,
        self::PENDAFTARAN_CREATE,
        self::PELAYANAN_VIEW,
        self::PELAYANAN_UPDATE,
        self::VERLAP_VIEW,
        self::VERLAP_UPDATE,
        self::SETTING_VIEW,
        self::SETTING_UPDATE,
    ];
}
