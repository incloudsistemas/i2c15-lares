<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;

/**
 * Generates and caches a thumbnail for an image, applying optional transformations.
 *
 * @param string|null $src The source image path.
 * @param int $width Thumbnail width.
 * @param int $height Thumbnail height.
 * @param string $disk Storage disk to use (default: "public").
 * @param string $type Type of transformation: 'fit', 'resize', 'scale', 'pad', 'resizeCanvas' (default: 'fit').
 * @param bool $watermark Whether to apply a watermark (default: false).
 * @param string $watermarkPosition Position of the watermark (default: 'center').
 * @param int $quality Image quality (default: 100).
 * @return string URL of the generated thumbnail.
 */

if (!function_exists('CreateThumb')) {
    function CreateThumb(
        ?string $src,
        int $width,
        int $height,
        string $disk = 'public',
        string $type = 'fit',
        bool $watermark = false,
        string $watermarkPosition = 'center',
        int $quality = 100
    ) {
        // Validate mandatory parameters
        if (!$src || empty($src)) {
            return PlaceholderImg(width: $width, height: $height);
        }

        // Adjust source path
        $src = ltrim($src, '/');

        // Check if it's a complete URL and adjust accordingly
        if (strpos($src, 'storage') !== false) {
            $src = parse_url($src, PHP_URL_PATH);
            $src = str_replace('/storage/', '', $src);
        }

        // Try to get the last modified date.
        try {
            $lastModified = Storage::disk($disk)->lastModified($src);
        } catch (\Exception $e) {
            // If it fails, you can set a default value or a fallback strategy.
            $lastModified = now()->timestamp;
        }

        // Create a unique key for the cache based on src, width, height, type, quality and watermark.
        $cacheKey = 'thumb_' . md5($src . $width . $height . $type . $quality . $watermark . $watermarkPosition) . "_$lastModified";
        // Cache::forget($cacheKey);

        // Try getting the thumbnail URL from cache first.
        return Cache::remember(
            $cacheKey,
            now()->addDay(),
            function () use ($src, $width, $height, $disk, $type, $watermark, $watermarkPosition, $quality) {
                // Get paths and names
                $filePartials = explode('/', $src);
                $fileName = end($filePartials);
                $dirPath = str_replace("/$fileName", '', $src);
                $thumbSrc = "{$dirPath}/thumbs/{$width}x{$height}/{$fileName}";

                // If original image doesn't exist, return a placeholder image
                if (!Storage::disk($disk)->exists($src)) {
                    return PlaceholderImg(width: $width, height: $height);
                }

                // If thumbnail exists, return it
                if (Storage::disk($disk)->exists($thumbSrc)) {
                    return asset(Storage::url($thumbSrc));
                }

                // Get the original file
                $file = Storage::disk($disk)->get($src);
                if ($file === false) {
                    throw new \RuntimeException("Unable to read file: $src");
                }

                // Process the image
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);

                if ($watermark) {
                    $image->place(public_path('build/web/images/watermark.png'), $watermarkPosition);
                }

                // Perform the desired image transformation
                match ($type) {
                    'resize'       => $image->resizeDown($width, $height),
                    'scale'        => $image->scale($width, $height),
                    'fit'          => $image->cover($width, $height),
                    'pad'          => $image->pad($width, $height, '666'),
                    'resizeCanvas' => $image->resizeCanvas($width, $height, '666'),
                    default        => throw new \UnexpectedValueException("Invalid type: $type"),
                };

                $encoded = $image->encode(new AutoEncoder(quality: $quality));

                // Store the thumbnail file
                if (!Storage::disk($disk)->put($thumbSrc, $encoded)) {
                    throw new \RuntimeException("Failed to store thumbnail: $thumbSrc");
                }

                return asset(Storage::url($thumbSrc));
            }
        );
    }
}

/**
 * Generates a placeholder image URL with customizable dimensions and text.
 *
 * @param int $width Placeholder image width.
 * @param int $height Placeholder image height.
 * @param string $text Placeholder text (default: 'S/ Img').
 * @param string $background Background color in hex (default: 'EFEFEF').
 * @param string $textColor Text color in hex (default: 'AAAAAA').
 * @return string URL of the generated placeholder image.
 */

if (!function_exists('PlaceholderImg')) {
    function PlaceholderImg(
        int $width,
        int $height,
        string $text = 'S/ Img',
        string $background = 'EFEFEF',
        string $textColor = 'AAAAAA'
    ): string {
        $text = str_replace(' ', '+', $text);

        return "https://dummyimage.com/{$width}x{$height}/{$background}/{$textColor}.png&text={$text}";
        // return "https://via.placeholder.com/{$width}x{$height}/{$background}/{$textColor}?text={$text}";
        // return "https://fakeimg.pl/{$width}x{$height}/{$background}/{$textColor}?text={$text}";
    }
}
