<?php

namespace Bale\Sidulur\Support;

class FormSchema
{
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'select', 'file'];

    /**
     * Tipe isian yang boleh dipakai sebuah persyaratan. Berkas adalah
     * bawaan supaya schema lama (yang belum punya `type`) tetap meminta
     * unggahan seperti sebelumnya.
     */
    public const REQUIREMENT_TYPES = ['file', 'text', 'textarea', 'number', 'date'];

    /**
     * Sumber master lama. Identitas warga kini syarat otomatis, jadi daftar ini
     * hanya dipakai untuk membuang baris warisan di `normalize()` dan command
     * pembersih schema tersimpan.
     */
    public const SOURCES = ['ktp', 'kk', 'hp'];

    /** Kunci checklist "identitas & berkas warga" (bukan baris Form Builder). */
    public const IDENTITY_KEY = 'identitas';

    /** Kode/nama syarat yang sudah otomatis dan tidak boleh dibuat manual. */
    public const RESERVED_NAMES = [
        'ktp', 'kk', 'hp', 'nik', 'nama', 'nomor_kk', 'nomor kk', 'alamat',
        'kartu keluarga', 'nomor hp',
    ];

    /**
     * @return array{persyaratan: array<int, array<string, mixed>>, fields: array<int, array<string, mixed>>, verifikasi: array<int, array<string, mixed>>}
     */
    public static function normalize(?array $schema): array
    {
        $schema = $schema ?: [];

        $persyaratan = array_values(array_filter(
            self::rows($schema['persyaratan'] ?? [], 'syarat', self::REQUIREMENT_TYPES, 'file'),
            fn (array $row) => ! in_array($row['sumber'] ?? null, self::SOURCES, true),
        ));

        return [
            'persyaratan' => $persyaratan,
            'fields' => self::rows($schema['fields'] ?? [], 'field'),
            'verifikasi' => self::rows($schema['verifikasi'] ?? [], 'verifikasi'),
        ];
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, array<string, mixed>>
     */
    protected static function rows(mixed $rows, string $prefix, array $types = self::FIELD_TYPES, string $defaultType = 'text'): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $result = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = trim((string) ($row['key'] ?? $row['kode'] ?? ''));
            $label = trim((string) ($row['label'] ?? $row['nama'] ?? ''));

            if ($label === '' && $key === '') {
                continue;
            }

            $row['type'] = in_array($row['type'] ?? null, $types, true) ? $row['type'] : $defaultType;

            $result[] = $row + [
                'key' => $key !== '' ? $key : $prefix.'_'.(count($result) + 1),
                'label' => $label !== '' ? $label : $key,
                'required' => (bool) ($row['required'] ?? $row['wajib'] ?? false),
                'options' => array_values((array) ($row['options'] ?? [])),
                'order' => (int) ($row['order'] ?? $row['urutan'] ?? $index + 1),
            ];
        }

        usort($result, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $result;
    }

    /**
     * Aturan validasi field dinamis: `required` hanya bila ditandai wajib.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int, string>>
     */
    public static function fieldRules(array $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = trim((string) ($field['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $rules[$prefix.'.'.$key] = [($field['required'] ?? false) ? 'required' : 'nullable'];
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, string>
     */
    public static function fieldLabels(array $fields, string $prefix): array
    {
        $labels = [];

        foreach ($fields as $field) {
            $key = trim((string) ($field['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $labels[$prefix.'.'.$key] = (string) ($field['label'] ?? '') ?: $key;
        }

        return $labels;
    }

    /**
     * Persyaratan bertanda `wajib` harus dicentang petugas.
     *
     * @param  array<int, array<string, mixed>>  $requirements
     * @return array<string, array<int, string>>
     */
    public static function requirementRules(array $requirements): array
    {
        $rules = [];

        foreach ($requirements as $row) {
            $kode = trim((string) ($row['kode'] ?? $row['key'] ?? ''));

            if ($kode === '') {
                continue;
            }

            $rules['requirements.'.$kode] = [($row['wajib'] ?? $row['required'] ?? false) ? 'accepted' : 'nullable'];
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $requirements
     * @return array<string, string>
     */
    public static function requirementLabels(array $requirements, string $prefix = 'requirements'): array
    {
        $labels = [];

        foreach ($requirements as $row) {
            $kode = trim((string) ($row['kode'] ?? $row['key'] ?? ''));

            if ($kode === '') {
                continue;
            }

            $labels[$prefix.'.'.$kode] = (string) ($row['nama'] ?? $row['label'] ?? '') ?: $kode;
        }

        return $labels;
    }

    /**
     * Aturan validasi isian persyaratan, sesuai `type` masing-masing baris.
     * `$files` memilih hanya baris bertipe berkas; sebaliknya hanya non-berkas.
     *
     * @param  array<int, array<string, mixed>>  $requirements
     * @return array<string, array<int, string>>
     */
    public static function requirementValueRules(array $requirements, string $prefix, bool $files = false): array
    {
        $rules = [];

        foreach ($requirements as $row) {
            $kode = trim((string) ($row['kode'] ?? $row['key'] ?? ''));

            if ($kode === '') {
                continue;
            }

            $key = $prefix.'.'.$kode;
            $required = (bool) ($row['wajib'] ?? $row['required'] ?? false);
            $isFile = ($row['type'] ?? 'file') === 'file';

            if ($isFile !== $files) {
                continue;
            }

            if ($isFile) {
                $rules[$key] = $required
                    ? ['required', 'array', 'min:1', 'max:3']
                    : ['nullable', 'array', 'max:3'];
                $rules[$key.'.*'] = ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'];

                continue;
            }

            $rules[$key] = match ($row['type'] ?? 'text') {
                'number' => [$required ? 'required' : 'nullable', 'numeric'],
                'date' => [$required ? 'required' : 'nullable', 'date'],
                'textarea' => [$required ? 'required' : 'nullable', 'string', 'max:2000'],
                default => [$required ? 'required' : 'nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Kolom syarat yang bentrok dengan identitas otomatis, atau null bila aman.
     */
    public static function reservedRequirement(array $row): ?string
    {
        foreach (['kode', 'nama'] as $column) {
            $value = mb_strtolower(trim((string) ($row[$column] ?? '')));

            if ($value !== '' && in_array($value, self::RESERVED_NAMES, true)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Sumber identitas yang wajib ada sebelum pengajuan disimpan.
     *
     * @return array<int, string> kunci sumber yang belum lengkap
     */
    public static function identityMissing(bool $hasKtp, bool $hasKk, bool $hasPhone): array
    {
        return array_keys(array_filter([
            'ktp' => ! $hasKtp,
            'kk' => ! $hasKk,
            'hp' => ! $hasPhone,
        ]));
    }

    public static function identityLabel(string $source): string
    {
        return match ($source) {
            'ktp' => 'KTP',
            'kk' => 'Kartu Keluarga',
            'hp' => 'Nomor HP',
            default => $source,
        };
    }

    /**
     * Checklist tetap untuk identitas & berkas warga.
     *
     * @return array<string, array<int, string>>
     */
    public static function identityRule(): array
    {
        return ['requirements.'.self::IDENTITY_KEY => ['accepted']];
    }

    public static function identityTitle(): string
    {
        return 'Identitas & berkas warga';
    }
}
