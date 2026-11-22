<?php

namespace Bale\Sidulur\Services;

use Bale\Core\Services\ImageService;
use Bale\Core\Support\Cdn;
use Bale\Sidulur\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    public function upload(Model $owner, UploadedFile $file, string $kind): Document
    {
        $disk = config('sidulur.storage.disk', 'sidulur');
        $directory = 'sidulur/'.$kind.'/'.now()->format('Y/m');

        // Dokumen warga bersifat pribadi: disk privat, gambar dikompres ke WebP.
        $webp = str_starts_with((string) $file->getMimeType(), 'image/')
            ? app(ImageService::class)->encodeWebp($file, quality: 80, maxWidth: 1600)
            : null;

        if ($webp) {
            $path = $directory.'/'.Str::uuid().'.webp';

            Storage::disk($disk)->put($path, (string) $webp);

            $mime = 'image/webp';
            $size = Storage::disk($disk)->size($path);
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.webp';
        } else {
            $path = $file->store($directory, $disk);

            $mime = $file->getClientMimeType();
            $size = $file->getSize();
            $originalName = $file->getClientOriginalName();
        }

        return $owner->documents()->create([
            'kind' => $kind,
            'disk' => $disk,
            'path' => $path,
            'mime' => $mime,
            'size' => $size,
            'original_name' => $originalName,
            'uploaded_by' => auth()->user()?->uuid,
        ]);
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    /**
     * Ganti dokumen sejenis: unggah baru dulu, baru hapus lama, supaya berkas
     * lama tidak hilang bila unggahan gagal.
     */
    public function replace(Model $owner, UploadedFile $file, string $kind): Document
    {
        $document = $this->upload($owner, $file, $kind);

        $old = $owner->documents()->where('kind', $kind)->whereKeyNot($document->getKey())->get();

        foreach ($old as $existing) {
            $this->delete($existing);
        }

        return $document;
    }

    /**
     * Data pratinjau untuk view; null bila belum ada dokumen.
     *
     * Di production berkas disajikan lewat CDN core; di lokal (dan saat CDN
     * mati) tetap lewat route media yang ber-auth.
     *
     * @return array{name: string, url: string, is_image: bool}|null
     */
    public function preview(?Document $document): ?array
    {
        if (! $document) {
            return null;
        }

        return [
            'name' => $document->original_name,
            'url' => app()->isProduction() && Cdn::enabled()
                ? Cdn::url($document->path)
                : route('sidulur.media', $document->getKey()),
            'is_image' => str_starts_with((string) $document->mime, 'image/'),
        ];
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
