<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Whatsapp
{

    /**
     * Pesan hanya benar-benar dikirim di production dan bila WHATSAPP_ENABLED tidak false.
     */
    public static function enabled(): bool
    {
        return app()->environment('production') && filter_var(config('services.whatsapp.enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function send($phone, $message)
    {
        if (! self::enabled()) {
            Log::info('Whatsapp Send skipped (non-production atau WHATSAPP_ENABLED=false)', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return true;
        }

        $data = [
            'phone_no' => Common::phoneAddCountryCode($phone),
            'key' => 'da0826f6603f9729df86a85f6023ee4deb89edf78d381b61',
            'message' => $message
        ];

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.omnichannelmessenger.com/api/send_message',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            Log::alert('-----');
            Log::alert('Whatsapp Send');
            Log::alert('Data : ' . json_encode($data));
            Log::alert('Response : ' . $response);

            return true;
        } catch (\Throwable $th) {
            Log::alert('-----');
            Log::alert('Whatsapp Send Failed');
            Log::alert('Data : ' . json_encode($data));
            Log::alert('Response : ' . $th->getMessage());

            return false;
        }
    }
}
