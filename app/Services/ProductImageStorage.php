<?php

namespace App\Services;

use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores product images safely. The upload is never saved as-is: it is decoded
 * by GD and written out as a brand new WebP file with a random name. Anything
 * hidden inside the original file (scripts, metadata, polyglot payloads) is
 * discarded, and nothing the user controls ends up in the file name.
 */
class ProductImageStorage
{
    public function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring($file->getContent());

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages(['image' => 'The image could not be read. Upload a valid JPG, PNG or WebP file.']);
        }

        $image = $this->resize($source, (int) config('inventory.product_images.max_stored_dimension'));

        ob_start();
        imagewebp($image, null, 82);
        $contents = ob_get_clean();

        $path = config('inventory.product_images.directory').'/'.Str::random(40).'.webp';
        $this->disk()->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }

    private function resize(GdImage $source, int $maxDimension): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));

        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        return $target;
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('inventory.product_images.disk'));
    }
}
