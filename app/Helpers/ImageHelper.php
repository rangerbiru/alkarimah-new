<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Kompres gambar dari string base64.
     *
     * @param string $base64Image
     * @param int $quality (0 - 100)
     * @return string Base64 hasil kompres
     * @throws Exception
     */

    public static function saveCompressedBase64Image($photoBase64, $employeeId, $prefix = 'attendance', $quality = 70)
    {
        if (!$photoBase64) {
            return null;
        }

        try {
            // === Kompres base64 ===
            $compressedBase64 = self::compressBase64Image($photoBase64, $quality);

            if (!preg_match('/^data:image\/(\w+);base64,/', $compressedBase64, $type)) {
                throw new Exception('Format foto tidak valid.');
            }

            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Format foto harus JPG atau PNG.');
            }

            $image = substr($compressedBase64, strpos($compressedBase64, ',') + 1);

            // === Nama file ===
            $imageName = "{$prefix}_{$employeeId}_" . now()->format('Y-m-d_His') . ".{$type}";

            if (!Storage::disk('public')->exists('attendance_photos')) {
                Storage::disk('public')->makeDirectory('attendance_photos');
            }

            $saved = Storage::disk('public')->put('attendance_photos/' . $imageName, base64_decode($image));

            if (!$saved) {
                throw new Exception('Gagal menyimpan foto ke server.');
            }

            return $imageName;
        } catch (Exception $e) {
            throw new Exception('Upload foto gagal: ' . $e->getMessage());
        }
    }
    public static function compressBase64Image(string $base64Image, int $quality = 70): string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            throw new Exception('Format foto tidak valid.');
        }

        $type = strtolower($type[1]);
        if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
            throw new Exception('Format foto harus JPG atau PNG.');
        }

        $imageData = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));
        if ($imageData === false) {
            throw new Exception('Gagal decode data gambar.');
        }

        $image = imagecreatefromstring($imageData);
        if (!$image) {
            throw new Exception('Gagal memproses gambar.');
        }

        ob_start();
        if ($type === 'png') {
            // Kualitas PNG (0-9), kebalikan dari JPEG
            $pngQuality = (int)((100 - $quality) / 10);
            imagepng($image, null, $pngQuality);
        } else {
            imagejpeg($image, null, $quality);
        }
        $compressedImage = ob_get_clean();

        imagedestroy($image);

        return 'data:image/' . $type . ';base64,' . base64_encode($compressedImage);
    }
}
