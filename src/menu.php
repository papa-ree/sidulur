<?php

use Bale\Sidulur\SidulurPermissions;

return [
    'type' => 'tenant',

    'groups' => [
        [
            'key' => 'sidulur',
            'label' => 'Sidulur',
            'icon' => 'heart-handshake',
            'items' => [
                [
                    'label' => 'Overview',
                    'url' => 'sidulur/overview',
                    'icon' => 'layout-dashboard',
                    'permission' => SidulurPermissions::VIEW,
                    'table' => 'sidulur_pendaftarans',
                ],
                [
                    'label' => 'Pendaftaran',
                    'url' => 'sidulur/pendaftarans',
                    'icon' => 'ticket',
                    'permission' => SidulurPermissions::PENDAFTARAN_VIEW,
                    'table' => 'sidulur_pendaftarans',
                ],
                [
                    'label' => 'Data Warga',
                    'url' => 'sidulur/citizens',
                    'icon' => 'users',
                    'permission' => SidulurPermissions::CITIZEN_VIEW,
                    'table' => 'sidulur_citizens',
                ],
                [
                    'label' => 'Bidang',
                    'url' => 'sidulur/bidangs',
                    'icon' => 'building-2',
                    'permission' => SidulurPermissions::BIDANG_VIEW,
                    'table' => 'sidulur_bidangs',
                ],
                [
                    'label' => 'Layanan',
                    'url' => 'sidulur/services',
                    'icon' => 'clipboard-list',
                    'permission' => SidulurPermissions::SERVICE_VIEW,
                    'table' => 'sidulur_services',
                ],
                [
                    'label' => 'Formulir',
                    'url' => 'sidulur/forms',
                    'icon' => 'file-text',
                    'permission' => SidulurPermissions::FORM_VIEW,
                    'table' => 'sidulur_forms',
                ],
                [
                    'label' => 'Petugas',
                    'url' => 'sidulur/officers',
                    'icon' => 'user-cog',
                    'permission' => SidulurPermissions::OFFICER_VIEW,
                    'table' => 'sidulur_officers',
                ],

                [
                    'label' => 'Pelayanan Bidang',
                    'url' => 'sidulur/pelayanan',
                    'icon' => 'briefcase-business',
                    'permission' => SidulurPermissions::PELAYANAN_VIEW,
                    'table' => 'sidulur_pelayanan_bidangs',
                ],
                [
                    'label' => 'Verifikasi Lapangan',
                    'url' => 'sidulur/verlap',
                    'icon' => 'map-pin-check',
                    'permission' => SidulurPermissions::VERLAP_VIEW,
                    'table' => 'sidulur_pelayanan_bidangs',
                ],
                [
                    'label' => 'Pengaturan',
                    'url' => 'sidulur/settings',
                    'icon' => 'settings',
                    'permission' => SidulurPermissions::SETTING_VIEW,
                    'table' => 'sidulur_settings',
                ],
            ],
        ],
    ],
];
