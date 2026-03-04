<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Upload
{
    private static $path = [
        'files' => 'files/',
    ];

    public static function getPath($permission='private', $pathname = 'files')
    {
        return ($pathname == 'files') ? $permission . '/' . self::$path[$pathname] . date('Y-m') . '/' : $permission . '/' . self::$path[$pathname];
    }

    public static function generateFilename()
    {
        $filename = Str::random(5);
        $filename .= date('d') . Str::random(5);
        $filename .= date('m') . Str::random(5);
        $filename .= date('y') . Str::random(5);
        $filename .= date('H') . Str::random(5);
        $filename .= date('i') . Str::random(5);
        $filename .= date('s') . Str::random(5);

        return $filename;
    }

    public static function image($source, $filename, $permission='private', $pathname='files')
    {
        $path = Storage::path(self::getPath($permission, $pathname) . $filename);

        try {
            $source = $source->getPathName();
            $info = getimagesize($source);

            if ($info['mime'] == 'image/png') {
                $x = $info[0];
                $y = $info[1];

                $data = file_get_contents($source);
                $vImg = imagecreatefromstring($data);
                $dstImg = imagecreatetruecolor($x, $y);

                imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                imagecopy($dstImg, $vImg, 0, 0, 0, 0, $x, $y);

                imagepng($dstImg, $path);
                imagedestroy($dstImg);
            } else {
                if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source);
                elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source);

                imagejpeg($image, $path, 75);
            }
        } catch (Exception $e) {
            move_uploaded_file($source, $path);
        }
    }

    public static function base64ToImage($base64, $filename, $permission='private', $pathname='files', $ext='jpg')
    {
        $bin = base64_decode($base64);
        $im = imageCreateFromString($bin);

        if (!$im) {
            Log::alert('-----');
            Log::alert('Convert Base64 to Image Failed');
            Log::alert('Error : Base64 value is not a valid image');

            return false;
        }

        $path = Storage::path(self::getPath($permission, $pathname) . $filename);

        if ($ext == 'jpg')
            imagejpeg($im, $path);
        else
            imagepng($im, $path);

        return $path;
    }
}
