# Sidulur

Modul layanan sosial untuk Bale CMS. Menangani data warga, layanan per bidang, formulir dinamis, pendaftaran di meja, pengajuan ke bidang, verifikasi lapangan, dan panel layanan di percakapan WhatsApp.

Semua tabel Sidulur berada di database tenant. Paket ini bergantung pada `bale/core`, `bale/cms`, `bale/wara`, dan `bale/vada`.

## Fitur

- Data warga dan kartu keluarga. Pencarian NIK, nomor KK, dan nomor HP memakai blind index HMAC sehingga kolom pencarian tidak menyimpan nilai mentah.
- Bidang, layanan, dan petugas. Setiap layanan punya formulir sendiri.
- Form builder: field isian, persyaratan tambahan, dan isian verifikasi. Field bertipe teks, number, date, select, textarea, dan file.
- Pendaftaran tiga langkah: cari warga, pilih layanan, isi formulir.
- Pengajuan diteruskan ke bidang atau diselesaikan di meja pendaftaran. Bidang dapat meminta verifikasi lapangan.
- Dokumen warga dan berkas persyaratan disajikan lewat route ber-auth dengan pemeriksaan permission.
- Notifikasi WhatsApp lewat `bale/wara`, dikirim sebagai job antrean.
- Panel layanan di chat Vada: petugas membuat pendaftaran dari percakapan dan memakai media chat sebagai berkas persyaratan.
- Role, permission, dan menu sidebar didaftarkan otomatis.

## Kebutuhan

- PHP 8.3 dan Laravel 12
- `bale/core`, `bale/cms`, `bale/wara`, `bale/vada`
- Tenant aktif di `bale_lists`
- Queue worker untuk notifikasi WhatsApp

## Instalasi

Tambahkan paket ke aplikasi lewat path repository, lalu jalankan installer:

```bash
composer require bale/sidulur

php artisan sidulur:install --all --tenant=<slug-tenant>
```

`sidulur:install` menjalankan tiga langkah:

1. Publikasi config ke `config/sidulur.php`.
2. Pembuatan permission dan role.
3. Publikasi migrasi ke `database/migrations/sidulur/`, lalu migrasi ke database tenant.

Tanpa `--tenant`, perintah menanyakan tenant yang akan dimigrasi. Tanpa `--all`, installer menampilkan pilihan langkah.

Langkah terpisah bila dibutuhkan:

```bash
php artisan sidulur:publish-config --force
php artisan sidulur:publish-migrations --force
php artisan sidulur:migrate --tenant=<slug-tenant>
```

Setelah instalasi, isi environment:

```dotenv
SIDULUR_INDEX_KEY=
SIDULUR_TICKET_EXPIRY_DAYS=30
SIDULUR_DOCUMENT_LINK_DAYS=30
SIDULUR_DOCUMENT_DISK=sidulur
SIDULUR_DOCUMENT_BUCKET=
SIDULUR_ACCESS_KEY_ID=
SIDULUR_SECRET_ACCESS_KEY=
SIDULUR_WHATSAPP=
SIDULUR_WA_PURPOSE=sidulur
```

`SIDULUR_INDEX_KEY` harus sama dengan kunci yang dipakai aplikasi bale-front. Bila berbeda, pencarian warga tidak menemukan data yang sudah ada.

Di produksi berkas disimpan di bucket MinIO privat (`SIDULUR_DOCUMENT_BUCKET`) memakai kredensial `SIDULUR_ACCESS_KEY_ID`/`SIDULUR_SECRET_ACCESS_KEY`; region, endpoint, dan path-style mengikuti `AWS_DEFAULT_REGION`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`. Di lingkungan lain disk `sidulur` menunjuk `storage/app/sidulur`. Berkas disajikan lewat controller ber-auth, jadi tidak perlu `storage:link`. Aplikasi bale-front membaca bucket yang sama dan menyajikannya lewat signed route.

Berikan role yang sesuai ke pengguna:

| Role | Akses |
|------|-------|
| `bale-sidulur-admin` | Seluruh permission Sidulur |
| `bale-sidulur-pendaftaran` | Warga, layanan, dan pendaftaran |
| `bale-sidulur-bidang` | Pengajuan masuk dan pemrosesan |
| `bale-sidulur-lapangan` | Verifikasi lapangan |

## Perintah

| Perintah | Keterangan |
|----------|------------|
| `sidulur:install` | Config, permission, dan migrasi |
| `sidulur:publish-config` | Publikasi `config/sidulur.php` |
| `sidulur:publish-migrations` | Publikasi stub migrasi ke `database/migrations/sidulur/` |
| `sidulur:migrate` | Migrasi ke database tenant |
| `sidulur:seed` | Data awal (bidang, layanan, formulir) |
| `sidulur:demo` | Data demo untuk satu tenant |
| `sidulur:bersihkan-syarat-master` | Bersihkan syarat master yang tidak lagi dipakai |

## Tabel

| Tabel | Isi |
|-------|-----|
| `sidulur_family_cards` | Kartu keluarga |
| `sidulur_citizens` | Data warga |
| `sidulur_bidangs` | Bidang |
| `sidulur_officers` | Petugas |
| `sidulur_services` | Layanan |
| `sidulur_forms` | Formulir per layanan (JSON schema) |
| `sidulur_pendaftarans` | Pendaftaran dan tiket |
| `sidulur_pelayanan_bidangs` | Pengajuan ke bidang dan status pemrosesan |
| `sidulur_timelines` | Riwayat status |
| `sidulur_documents` | Berkas warga dan persyaratan |
| `sidulur_settings` | Pengaturan tenant |

## Integrasi Vada

Bila `bale/vada` terpasang, paket ini mendaftarkan:

- Halaman chat di `/cms/sidulur/chat`.
- `SidulurContactContext` yang memasok data warga ke panel kanan percakapan.
- Form layanan di panel chat, termasuk pemilihan berkas dari media percakapan.

Item menu Chat hanya tampil bila tabel `vada_conversations` ada di database tenant.

## Rute

Semua rute berada di prefix `/cms/sidulur`, memakai middleware `web`, `auth`, `EnsureBaleSelected`, dan `SwitchBaleConnection`.

## Pengembangan

Tes memakai Pest dari root proyek:

```bash
php vendor/bin/pest packages/sidulur/tests
vendor/bin/pint packages/sidulur
```
