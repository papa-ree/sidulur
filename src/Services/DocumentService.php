<?php

namespace Bale\Sidulur\Services;

use Bale\Core\Services\ImageService;
use Bale\Sidulur\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
     * Bucket dokumen bersifat privat, jadi selalu lewat route media yang
     * memeriksa auth dan permission; tidak ada URL CDN publik.
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
            'url' => route('sidulur.media', $document->getKey()),
            'is_image' => str_starts_with((string) $document->mime, 'image/'),
        ];
    }

    public function response(Document $document): StreamedResponse
    {
        $disk = Storage::disk($document->disk);

        // Satu GET saja: exists() + response() memicu HEAD tambahan yang bisa
        // gagal di MinIO walau GET-nya sehat.
        $stream = $disk->readStream($document->path);
        abort_unless(is_resource($stream), 404);

        $mime = (string) ($document->mime ?: 'application/octet-stream');

        return response()->stream(fn () => fpassthru($stream), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                str_starts_with($mime, 'image/') ? 'inline' : 'attachment',
                (string) $document->original_name,
                Str::ascii((string) $document->original_name),
            ),
            'X-Content-Type-Options' => 'nosniff',
            // Path dokumen unik per unggahan, isinya tidak pernah berubah.
            'Cache-Control' => 'private, max-age=604800, immutable',
        ]);
    }
}
