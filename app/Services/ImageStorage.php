<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stockage des images téléversées depuis l'administration (disque « public »).
 *
 * Les photos de téléphone (souvent 4000 px, 5–10 Mo) sont redimensionnées à
 * MAX_SIZE px max, orientées selon l'EXIF et converties en WebP : indispensable
 * pour une boutique consultée surtout sur mobile. Nom de fichier toujours généré.
 */
class ImageStorage
{
    public const MAX_SIZE = 1600;

    private const QUALITY = 82;

    public function store(UploadedFile $file, string $directory): string
    {
        try {
            if ($optimized = $this->optimize($file)) {
                $path = trim($directory, '/').'/'.Str::ulid()->toBase32().'.webp';
                Storage::disk('public')->put($path, $optimized);

                return $path;
            }
        } catch (Throwable $e) {
            Log::warning('Optimisation d’image impossible, stockage de l’original.', ['error' => $e->getMessage()]);
        }

        // Repli : fichier original (déjà validé : jpg/png/webp), nom généré par Laravel.
        return $file->store($directory, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Retourne le binaire WebP optimisé, ou null si GD n'est pas utilisable. */
    private function optimize(UploadedFile $file): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $contents = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return null;
        }

        $image = $this->applyExifOrientation($image, $file);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIZE / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
            imagedestroy($image);
            $image = $resized;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        imagedestroy($image);

        return ob_get_clean() ?: null;
    }

    /** Les appareils photo enregistrent la rotation dans l'EXIF : on l'applique aux pixels. */
    private function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
