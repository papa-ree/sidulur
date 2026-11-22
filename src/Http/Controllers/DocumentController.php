<?php

namespace Bale\Sidulur\Http\Controllers;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Document;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\SidulurPermissions;
use Illuminate\Http\Request;

class DocumentController
{
    /**
     * Resolusi dokumen dilakukan manual: implicit route binding berjalan di
     * middleware `web`, sebelum SwitchBaleConnection, sehingga query jatuh ke
     * DB landlord.
     */
    public function __invoke(Request $request, string $document, DocumentService $documents)
    {
        TenantConnectionService::ensureActive();

        $document = Document::query()->findOrFail($document);

        // Dokumen hasil/surat milik pengajuan: pembaca pelayanan/verlap juga berhak.
        $permissions = $document->isPublic()
            ? [
                SidulurPermissions::CITIZEN_VIEW,
                SidulurPermissions::PENDAFTARAN_VIEW,
                SidulurPermissions::PELAYANAN_VIEW,
                SidulurPermissions::VERLAP_VIEW,
            ]
            : [SidulurPermissions::CITIZEN_VIEW];

        $user = $request->user();
        abort_unless(collect($permissions)->contains(fn (string $permission) => $user?->can($permission)), 403);

        return $documents->response($document);
    }
}
