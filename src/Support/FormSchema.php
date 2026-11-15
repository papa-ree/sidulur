<?php

namespace Bale\Sidulur\Support;

class FormSchema
{
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'select', 'file'];

    public const SOURCES = ['ktp', 'kk', 'hp'];

    /**
     * @return array{persyaratan: array<int, array<string, mixed>>, fields: array<int, array<string, mixed>>, verifikasi: array<int, array<string, mixed>>}
     */
    public static function normalize(?array $schema): array
    {
        $schema = $schema ?: [];

        return [
            'persyaratan' => self::rows($schema['persyaratan'] ?? [], 'syarat'),
            'fields' => self::rows($schema['fields'] ?? [], 'field'),
            'verifikasi' => self::rows($schema['verifikasi'] ?? [], 'verifikasi'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function rows(mixed $rows, string $prefix): array
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

            $row['type'] = in_array($row['type'] ?? null, self::FIELD_TYPES, true) ? $row['type'] : 'text';

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
     * Nilai awal form dari data master (persyaratan bersumber ktp/kk/hp).
     *
     * @return array<string, bool>
     */
    public static function masterRequirements(array $schema, bool $hasKtp, bool $hasKk, bool $hasPhone): array
    {
        $result = [];

        foreach ($schema['persyaratan'] ?? [] as $row) {
            $source = $row['sumber'] ?? null;

            if (! in_array($source, self::SOURCES, true)) {
                continue;
            }

            $result[$row['kode'] ?? $row['key']] = match ($source) {
                'ktp' => $hasKtp,
                'kk' => $hasKk,
                'hp' => $hasPhone,
            };
        }

        return $result;
    }
}
