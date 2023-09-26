<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use App\Helpers\AllFunction;

class AllFunction
{

    public static function checkCategory($data)
    {

        if ($data == "c82020f4-90d1-4e20-977f-3ab1a4ba34ea") {
            return '01';
        } else if ($data == "6a44b8c6-e460-4327-bf7a-237cd174bf69") {
            return '02';
        } else {
            return '03';
        }
    }


    // Fungsi untuk mendapatkan kode invoice berikutnya
    public static function generateInvoice($category, $currentInvoiceNumber)
    {
        // Misalkan kodeCategory diambil dari input atau database
        $kodeCategory = AllFunction::checkCategory($category); // Contoh: e-wallet

        $randomDigits = mt_rand(0, 99);

        // Mendapatkan bulan dan tahun saat ini (yymm)
        $currentMonthYear = date('ym');

        if ($currentInvoiceNumber !== null) {
            // Memisahkan kodeCategory, tahun, dan bulan dari invoice terakhir
            list($invoice, $lastKodeCategory, $lastMonthYear, $lastAutoIncrement) = explode('-', $currentInvoiceNumber->invoice);


            $last = substr($lastAutoIncrement, 2, 10);


            $autoIncrement = intval($last) + 1;

            // Generate invoice berikutnya
            $nextInvoiceNumber = $invoice . '-' . $kodeCategory . '-' . $currentMonthYear . '-' . $randomDigits . str_pad($autoIncrement, 5, '0', STR_PAD_LEFT);
        } else {
            $autoIncrement = 1;
            $invoice = "INV";
            $nextInvoiceNumber = $invoice . '-' . $kodeCategory . '-' . $currentMonthYear . '-' . $randomDigits . str_pad($autoIncrement, 5, '0', STR_PAD_LEFT);
        }

        return $nextInvoiceNumber;
    }

    // response
    public static function response($code = 200, $status = 'OK', $message = null, $data = null)
    {

        return Response([
            'code'    => $code,
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    // generate Access Token
    public static function generateAccessToken($secret, $user_id, $app_id, $expired)
    {
        $payload = array(
            "sub" => $user_id,
            "iss" => $app_id,
            "iat" => time(),
            "exp" => $expired->timestamp
        );

        return JWT::encode($payload, $secret, 'HS256');
    }

    // generetae refresh Token
    public static function generateRefreshToken($secret, $user_id, $app_id, $refreshExpired)
    {
        $payload = array(
            "sub" => $user_id,
            "iss" => $app_id,
            "exp" => $refreshExpired->timestamp
        );

        return JWT::encode($payload, $secret, 'HS256');
    }


    public static function generateId($data)
    {
        $id = '101' . \strtoupper(Str::random(8));

        return $id;
    }
}
