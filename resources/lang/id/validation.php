<?php

/*
| Pesan validasi berbahasa Indonesia untuk paket Sidulur.
|
| Aplikasi memakai APP_LOCALE=id tetapi belum punya lang/id/validation.php,
| sehingga error tampil sebagai kunci mentah (validation.required). File ini
| didaftarkan lewat Lang::addLines() di SidulurServiceProvider::boot().
*/

return [
    'validation' => [
        'required' => ':attribute wajib diisi.',
        'required_if' => ':attribute wajib diisi.',
        'accepted' => ':attribute wajib dicentang.',
        'filled' => ':attribute wajib diisi.',
        'string' => 'Isian :attribute harus berupa teks.',
        'integer' => ':attribute harus berupa angka bulat.',
        'numeric' => ':attribute harus berupa angka.',
        'boolean' => ':attribute harus bernilai ya atau tidak.',
        'array' => ':attribute tidak valid.',
        'uuid' => ':attribute tidak valid.',

        'digits' => ':attribute harus terdiri dari :digits digit.',
        'min' => [
            'string' => 'Isian :attribute minimal :min karakter.',
            'numeric' => ':attribute minimal :min.',
            'file' => 'Ukuran :attribute minimal :min KB.',
            'array' => ':attribute minimal berisi :min item.',
        ],
        'max' => [
            'string' => 'Isian :attribute maksimal :max karakter.',
            'numeric' => ':attribute maksimal :max.',
            'file' => 'Ukuran :attribute maksimal :max KB.',
            'array' => ':attribute maksimal berisi :max item.',
        ],

        'file' => ':attribute harus berupa berkas yang valid.',
        'image' => ':attribute harus berupa gambar.',
        'mimes' => ':attribute harus berupa JPG, PNG, atau PDF.',
        'in' => ':attribute yang dipilih tidak valid.',
        'exists' => ':attribute yang dipilih tidak valid.',
        'unique' => ':attribute sudah digunakan.',
        'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
        'email' => 'Format :attribute tidak valid.',
        'date' => ':attribute bukan tanggal yang valid.',
        'after_or_equal' => ':attribute tidak boleh sebelum :date.',
        'confirmed' => 'Konfirmasi :attribute tidak cocok.',

        'attributes' => [
            'nama' => 'Nama',
            'nik' => 'NIK',
            'phone' => 'Nomor HP',
            'nomor_kk' => 'Nomor KK',
            'district_code' => 'Kecamatan',
            'village_code' => 'Kelurahan/desa',
            'alamat' => 'Alamat',
            'ktp' => 'Berkas KTP',
            'kk' => 'Berkas Kartu Keluarga',
            'anggota' => 'Anggota keluarga',
            'anggota.*.nik' => 'NIK anggota',
            'anggota.*.nama' => 'Nama anggota',
            'anggota.*.ktp' => 'Berkas KTP anggota',
            'anggota.*.kk' => 'Berkas KK anggota',
            'identity' => 'NIK atau nomor KK',
            'formData' => 'Data formulir',
            'verlapData' => 'Data verifikasi lapangan',
            'hasil' => 'Dokumen hasil',
            'note' => 'Catatan',
            'scheduled_at' => 'Jadwal verifikasi',
            'scheduled_note' => 'Catatan jadwal',
            'user_id' => 'Petugas',
            'bidang_id' => 'Bidang',
            'bidang_ids' => 'Bidang',
            'bidang_ids.*' => 'Bidang',
            'is_pendaftaran' => 'Akses pendaftaran',
            'is_bidang' => 'Akses bidang',
            'is_lapangan' => 'Akses lapangan',
            'is_active' => 'Status aktif',
            'slug' => 'Slug',
            'icon' => 'Ikon',
            'deskripsi' => 'Deskripsi',
            'sla_value' => 'Durasi SLA',
            'sla_unit' => 'Satuan SLA',
            'sort' => 'Urutan',
            'ticket_expiry_days' => 'Masa berlaku tiket',
            'document_link_days' => 'Masa berlaku tautan dokumen',
            'whatsapp_center' => 'Nomor WhatsApp pusat',
            'password' => 'Kata sandi',
            'password_confirmation' => 'Konfirmasi kata sandi',
        ],
    ],
];
