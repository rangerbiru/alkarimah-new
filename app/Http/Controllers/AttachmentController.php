<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\TempFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function get(Attachment $attachment)
    {
        if (empty($attachment)) {
            $file = public_path('images/default/file.png');

            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($file));
            readfile($file);
        } else {
            $path = Storage::path($attachment->path . $attachment->filename_hashed);

            if (file_exists($path)) {
                header('Content-Type: ' . $attachment->type);
                header('Content-Length: ' . filesize($path));
                echo file_get_contents($path);
            } else {
                switch ($attachment->extension) {
                    case 'pdf':
                        $type = 'pdf';
                        break;

                    default:
                        $type = 'image';
                }

                $file = public_path('images/default/' . $type . '.png');

                header('Content-Type: image/png');
                header('Content-Length: ' . filesize($file));
                readfile($file);
            }
        }
    }

    public function download(Attachment $attachment)
    {
        $path = Storage::path($attachment->path . '/' . $attachment->filename_hashed);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $attachment->type);
        header('Content-Disposition: attachment; filename=' . $attachment->filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Content-Length: ' . filesize($path));
        ob_clean();
        flush();
        readfile($path);
        exit;
    }

    public function storeTemporary(Request $request)
    {
        $filename = Auth::id() . '-' . date('YmdHis') . '.json';
        $path = 'tmp/' . $filename;

        $temp = TempFile::create(['file' => $filename]);
        Storage::put($path, $request->content);

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'id' => $temp->encrypted_id
            ]
        ];

        return response()->json($response);
    }
}
