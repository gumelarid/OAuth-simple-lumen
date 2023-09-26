<?php

namespace App\Helpers;


use App\Helpers\SendMail;
use App\Models\Transaction;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Goc
{

    public static function generateDataParse($dataPayment)
    {
        $_merchantId = env('GOC_MERCHANT_ID');
        $_haskey = env('GOC_HASHKEY');
        $urlPayment = env('GOC_URL_DEVELOPMENT');
        $urlReturn = env('FE_URL');
        $methodActionPost = 'POST';

        $currencyIDR = $dataPayment['country'];

        $_trxDateTime = substr(\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'))->format('Y-m-d\TH:i:sP'), 0, -3);
        $plainText = $_merchantId
            . $dataPayment['invoice']
            . $_trxDateTime
            . $dataPayment['channel_id']
            . $dataPayment['total_price']
            . $currencyIDR
            . $_haskey;

        $dataAttribute = [
            ['urlAction' => $urlPayment ?? $dataPayment['url']],
            ['methodAction' => $methodActionPost],
            ['merchantId' => $_merchantId],
            ['trxId' => $dataPayment['invoice']],
            ['trxDateTime' => $_trxDateTime],
            ['channelId' => $dataPayment['channel_id']],
            ['amount' => $dataPayment['total_price']],
            ['currency' => $currencyIDR],
            ['returnUrl' => $urlReturn . 'payment/confirmation?invoice=' . $dataPayment['invoice']],
            ['name' => 'name'],
            ['email' => $dataPayment['email']],
            ['phone' => $dataPayment['phone'] ?? null],
            ['userId' => 'userId'],
            ['sign' => GOC::generateSignature($plainText)],
        ];

        return $dataAttribute;
    }

    public static function generateSignature(string $plainText = null)
    {
        $signature = hash('sha256', $plainText);
        return $signature;
    }

    public static function UpdateStatus($request)
    {
        DB::beginTransaction();
        try {
            if ($request['status'] == 100) {
                $status = 1;
                Log::info('Success Transaction Paid GOC', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with GOC Invoice ' . $request['trxId']]);
            } else {

                $status = 2;
                Log::info('Cancel Transaction Paid GOC', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with GOC Invoice ' . $request['trxId']]);
            };

            $trx = Transaction::where('invoice', $request['trxId'])->update([
                'transaction_status' => $status,
                'paid_time' => $request['paidDate'],
            ]);

            // send invoice
            SendMail::sendMail($request['trxId']);

            DB::commit();

            return "OK";
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Transaction GOC', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST',
                'error' => 'BAD REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
