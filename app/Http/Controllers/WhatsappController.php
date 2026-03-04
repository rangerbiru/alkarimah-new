<?php

namespace App\Http\Controllers;

use App\Enums\BillPeriod;
use App\Helpers\Common;
use App\Helpers\Whatsapp;
use App\Models\Student;
use App\Models\TransactionBill;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function sendBillDueDate(Request $request)
    {
        $student = Student::select('id', 'id_parent', 'name')
            ->with(['parent' => fn($query) => $query->select('id', 'phone')])
            ->whereId($request->student)
            ->first();

        $message = "Bismillah\n";
        $message .= "_Assalamu'alaikum Warohmatullah Wabarokaatuh_\n\n";
        $message .= "Kepada Yth,\n";
        $message .= "*Bapak/Ibu selaku Orangtua/walisantri Pesantren Ibnu Abbas As Salafy Sragen* yang semoga senantiasa dimuliakan Allah Ta'ala,\n\n";
        $message .= "Berikut kami informasikan Data Tagihan Ananda : *" . $student->name . "*\n";

        $total = 0;

        foreach ($request->bill as $b) {
            $total += $b['total'];
            $message .= "- " . $b['name'] . " : Rp. " . number_format($b['total'], 0, '', '.') . "\n";
        }

        $message .= "\n*Total : Rp. " . number_format($total, 0, '', '.') . "*\n\n";
        $message .= "Mohon perhatian dan kerja samanya demikian yang dapat kami sampaikan semoga Allah Ta'ala mudahkan pembayaran sebelum jatuh tempo.\n\n";
        $message .= "_Jazakumullahu khairan_\n\n";
        $message .= "*Informasi :*\n";
        $message .= "Ust Sumidi (wa.me/+6285212223414)\n\n";
        $message .= "Link : https://apps.ppiasragen.org";

        Whatsapp::send($student->parent->phone, $message);

        $response = [
            'status' => true,
            'message' => __('string.whatsapp_bill_sent_successfully')
        ];

        return response()->json($response);
    }
}
