<?php

namespace App\Helpers;

use App\Mail\SendInvoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;

class SendMail
{
    public static function sendMail($invoice)
    {
        $check = Transaction::with('payment', 'pricepoints', 'gamelist', 'transactionDetail')->where('invoice', $invoice)->where('transaction_status', '1')->first();
        if ($check) {
            $data = [
                'invoice' => $check->invoice,
                'date'    => $check->created_at,
                'email'   => $check->email,
                'phone'   => $check->phone,
                'game_title' => $check->gamelist->game_title,
                'payment' => $check->payment->payment_name,
                'product' => $check->transactionDetail->amount . ' ' . $check->pricepoints->name_currency,
                'player_id' => $check->transactionDetail->player_id . ' - ' . $check->transactionDetail->username,
                'total_price' => $check->total_price,
            ];

            Mail::to($check->email)->queue(new SendInvoice($data));
        };

        return;
    }


    public static function sendToken($token, $email)
    {
        if ($email) {
            $data = [
                'token' => $token,
            ];

            Mail::send('mail', $data, function ($message) use ($email) {
                $message->to($email)->subject('OTP login - ESI GAMESHOP');
            });

            return 'Email sent successfully to ' . $email . '!';
        } else {
            return 'Invalid email address provided.';
        }
    }
}
