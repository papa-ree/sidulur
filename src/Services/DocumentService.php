<?php

namespace Bale\Sidulur\Services;

use Bale\Sidulur\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    public function upload(Model $owner, UploadedFile $file, string $kind): Document
    {
        $disk = config('sidulur.storage.disk', 'sidulur');
        $path = $file->store('sidulur/'.$kind.'/'.now()->format('Y/m'), $disk);

        return $owner->documents()->create([
            'kind' => $kind,
            'disk' => $disk,
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->user()?->uuid,
        ]);
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    public function response(Document $document): StreamedResponse
    {
        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
        ]);
    }
}
