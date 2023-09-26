<?php

namespace App\Helpers;

use App\Helpers\Coda;
use App\Models\Price;
use GuzzleHttp\Client;
use App\Helpers\SendMail;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Coda
{



    public static function generateDataParse($dataPayment)
    {
        $_methodActionPost = "POST";
        $_urlPayment = env('CODA_URL_DEVELOPMENT');
        $_urlRedirect = 'https://sandbox.codapayments.com/airtime/begin';
        $_urlFrontend = env('FE_URL');

        $dataAttribute = [
            ['methodAction' => $_methodActionPost],
            ['urlAction' => $_urlFrontend . 'payment-vendor/' . strtolower($dataPayment['code_payment'])],
            ['orderId' => $dataPayment['invoice']],
            ['codePayment' => $dataPayment['code_payment']],
            ['channelId' => $dataPayment['channel_id']],
            ['name' => $dataPayment['amount'] . ' ' . $dataPayment['name']],
            ['priceId' => $dataPayment['price_id']],
            ['price' => $dataPayment['total_price']],
            ['user_id' => $dataPayment['user']],
        ];

        return $dataAttribute;
    }

    public static function urlRedirect(array $dataParse)
    {

        $_methodActionPost = "POST";
        $_urlPayment = env('CODA_URL_DEVELOPMENT');
        $_urlRedirect = 'https://sandbox.codapayments.com/airtime/begin';
        $_urlFrontend = env('FE_URL');

        $initRequest['initRequest'] = [
            'country' => 360,
            'currency' => 360,
            'orderId' => $dataParse['orderId'],
            'apiKey' => env("CODA_API_KEY"),
            'payType' => $dataParse['channelId'],
            'items' => [
                "code" => $dataParse['priceId'],
                "name" => $dataParse['name'],
                "price" => number_format($dataParse['price'], 2, '.', ''),
                "type" =>  1,
            ],
            'profile' => [
                'entry' => [
                    [
                        "key" => 'user_id',
                        "value" => $dataParse['user_id'],
                    ],
                    [
                        "key" => 'need_mno_id',
                        "value" => 'yes',
                    ],
                ]
            ],
        ];

        $response = Coda::_doRequestToApi($initRequest);

        if ($response['initResult']['resultCode'] == 0) {
            $url = $_urlRedirect . '?type=3&txn_id=' . $response['initResult']['txnId'];
            return $url;
        }

        return json_encode($initRequest);
    }

    private static function _doRequestToApi(array $payload)
    {
        $_methodActionPost = "POST";
        $_urlPayment = env('CODA_URL_DEVELOPMENT');
        $_urlRedirect = 'https://sandbox.codapayments.com/airtime/begin';
        $_urlFrontend = env('FE_URL');

        $client = new Client();
        $response = $client->request($_methodActionPost, $_urlPayment, [
            'headers' => ['Content-type' => 'application/json'],
            'body' => json_encode($payload),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }


    public static function UpdateStatus($request)
    {

        try {
            DB::beginTransaction();

            $TxnID = $request['TxnId'];
            $ApiKey = '5a8ca8f31f19a23c41edd14b29a74fd2';
            $OrderID = $request['OrderId'];
            $ResultCode = $request['ResultCode'];
            $checkSumString = $TxnID . $ApiKey . $OrderID . $ResultCode;
            $resultChecksum = bin2hex(md5($checkSumString, true));


            $checkSum = bin2hex(md5($request['Checksum'], true));

            if (!hash_equals($resultChecksum, $checkSum)) {
                Log::info('Coda Checksum Match', ['DATA' => hash_equals($resultChecksum, $checkSum)]);
                return \response()->json([
                    'code' => 403,
                    'status' => 'CHECKSUM_NOT_MATCH',
                    'error' => 'CHECKSUM_NOT_MATCH',
                ], 403);
            }

            if ($ResultCode == 0) {
                $status = 1;
                Log::info('Success Transaction Paid CODA', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with CODA Invoice ' . $OrderID]);
            } else {

                $status = 2;
                Log::info('Cancel Transaction Paid CODA', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with CODA Invoice ' . $OrderID]);
            };

            $trx = Transaction::where('invoice', $OrderID)->update([
                'transaction_status' => $status,
                'paid_time' => Carbon::now()->format('Y-m-d H:i:s')
            ]);

            // send invoice
            SendMail::sendMail($TxnID);

            DB::commit();

            return "ResultCode = 0";
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Transaction Coda', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST',
                'error' => 'BAD REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
