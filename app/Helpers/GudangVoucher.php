<?php

namespace App\Helpers;


use App\Helpers\SendMail;
use App\Models\Transaction;
use Illuminate\Http\Response;
use App\Helpers\GudangVoucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GudangVoucher
{

    public static function generateDataParse($dataPayment)
    {


        $_merchantId = env('GV_MERCHANT_ID');
        $_mercahtKey = env('GV_MERCHANT_KEY');
        $urlPayment = env('GV_URL_DEVELOPMENT');
        $urlReturn = env('FE_URL');

        $amount = $dataPayment['total_price'];
        $custom = $dataPayment['invoice'];
        $product = $dataPayment['amount'] . ' ' . $dataPayment['name'];
        $email = $dataPayment['email'];

        $plainText = $_merchantId . $amount . $_mercahtKey . $custom;

        $dataAttribute = [
            ['urlAction' => $urlPayment],
            ['methodAction' => 'GET'],
            ['merchantid' => $_merchantId],
            ['custom' => $custom],
            ['product' => $product],
            ['amount' => $amount],
            ['custom_redirect' => $urlReturn . 'payment/confirmation?invoice=' . $dataPayment['invoice']],
            ['email' => $email],
            ['signature' => GudangVoucher::generateSignature($plainText)],
        ];
        return $dataAttribute;
    }

    public static function generateSignature(string $plainText = null)
    {
        $signature = hash('md5', $plainText);
        return $signature;
    }

    public static function UpdateStatus($request)
    {
        DB::beginTransaction();
        try {
            $dataXML = $request['data'];
            $xmlObject = simplexml_load_string($dataXML);

            $json = json_encode($xmlObject);
            $phpArray = json_decode($json, true);

            Log::info('info', ['data' => $phpArray]);

            if ($phpArray['status'] == "SUCCESS") {
                $status = 1;
                Log::info('Success Transaction Paid Gudang Voucher', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with GV Invoice ' . $phpArray['custom']]);
            } else {
                $status = 2;
                Log::info('Cancel Transaction Paid Gudang Voucher', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with GV Invoice ' . $phpArray['custom']]);
            };
            $trx = Transaction::where('invoice', $phpArray['custom'])->update([
                'transaction_status' => $status,
                'paid_time' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            // send invoice
            SendMail::sendMail($phpArray['custom']);

            DB::commit();

            return "OK";
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Gudang Voucher', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST',
                'error' => 'BAD REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
