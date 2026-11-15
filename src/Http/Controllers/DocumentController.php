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

        abort_unless($request->user()?->can(SidulurPermissions::CITIZEN_VIEW), 403);

        return $documents->response(Document::query()->findOrFail($document));
    }
}
