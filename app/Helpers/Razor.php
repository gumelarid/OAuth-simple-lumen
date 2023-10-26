<?php

namespace App\Helpers;

use Exception;
use App\Models\Price;
use App\Helpers\Razor;
use GuzzleHttp\Client;
use App\Helpers\SendMail;
use App\Models\Reference;
use App\Jobs\SendEmailJob;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Razor
{
    private $_urlFrontend, $_applicationCode, $_version, $_hashType, $_addZero, $_urlPayment, $_urlReturn, $_methodActionPost;

    public function __construct()
    {
        $this->_methodActionPost = 'POST';
        $this->_version = 'v1';
        $this->_hashType = 'hmac-sha256';
        $this->_applicationCode = env("RAZOR_MERCHANT_CODE");
        $this->_urlPayment = env("RAZOR_URL_DEVELPOMENT");
        $this->_urlReturn = route('home');
        $this->_addZero = "00";
        $this->_urlFrontend = env('FE_URL');
    }

    public static function generateDataParse($dataPayment)
    {
        $_methodActionPost = 'POST';
        $_version = 'v1';
        $_hashType = 'hmac-sha256';
        $_applicationCode = env("RAZOR_MERCHANT_CODE");
        $_urlPayment = env("RAZOR_URL_DEVELPOMENT");
        $_urlReturn = env('FE_URL');
        $_addZero = "00";
        $_urlFrontend = env('FE_URL');

        $urlAction = $_urlFrontend . 'payment-vendor/' . strtolower($dataPayment['code_payment']);
        $referenceId = $dataPayment['invoice'];
        $amount = $dataPayment['total_price'];
        $customerId = $dataPayment['user'];
        $currencyCode = 'IDR';
        $description = $dataPayment['amount'] . ' ' . $dataPayment['name'];
        $dataAttribute = [
            ['methodAction' => $_methodActionPost],
            ['urlAction' => $urlAction],
            ['referenceId' => $referenceId],
            ['amount' => $amount],
            ['currencyCode' => $currencyCode],
            ['description' => $description],
            ['customerId' => $customerId]
        ];

        return $dataAttribute;
    }

    public static function urlRedirect(array $dataParse)
    {
        $_methodActionPost = 'POST';
        $_version = 'v1';
        $_hashType = 'hmac-sha256';
        $_applicationCode = env("RAZOR_MERCHANT_CODE");
        $_urlPayment = env("RAZOR_URL_DEVELPOMENT");
        $_urlReturn = env('FE_URL');
        $_addZero = "00";
        $_urlFrontend = env('FE_URL');

        try {
            $plainText = $dataParse['amount'] . $this->_addZero
                . $_applicationCode
                . $dataParse['currencyCode']
                . $dataParse['customerId']
                . $dataParse['description']
                . $_hashType
                . $dataParse['referenceId']
                . $_urlReturn . 'payment/confirmation?invoice=' . $referenceId
                . $_version;

            $response = Razor::_doRequestToApi($dataParse, $plainText);
            $dataResponse = json_decode($response->getBody()->getContents(), true);

            if (!Razor::checkSignature($dataResponse)) {
                throw new Exception('Invalid Signature', 403);
            }

            if ($dataResponse['paymentUrl']) {
                Razor::saveReference($dataResponse['paymentId'], $dataResponse['referenceId']);
                return $dataResponse['paymentUrl'];
            }
        } catch (RequestException $error) {
            $responseError = json_decode($error->getResponse()->getBody()->getContents(), true);
            echo 'Error message ' . $responseError['message'];
        }
    }

    public static function generateSignature(string $plainText = null)
    {
        $signature = hash_hmac('sha256', $plainText, env("RAZOR_SECRET_KEY"));
        return $signature;
    }

    public static function checkSignature($dataResponse)
    {
        $plainText = $dataResponse['amount']
            . $dataResponse['applicationCode']
            . $dataResponse['currencyCode']
            . $dataResponse['hashType']
            . $dataResponse['paymentId']
            . $dataResponse['paymentUrl']
            . $dataResponse['referenceId']
            . $dataResponse['version'];
        $signatureMerchat = Razor::generateSignature($plainText);

        if ($dataResponse['signature'] == $signatureMerchat) return true;

        return false;
    }

    public static function saveReference(string $paymentId, string $orderId)
    {
        DB::beginTransaction();
        try {
            $checkInvoice = Transaction::where('invoice', $orderId)->first();
            if ($checkInvoice) return;
            Transaction::where('invoice', $orderId)->update([
                'refence_no' => $paymentId
            ]);
            DB::commit();
            return;
        } catch (\Throwable $th) {
            DB::rollback();
            abort(500, 'Internal error, please try again');
        }
    }

    private static function _doRequestToApi(array $dataParse, string $plainText)
    {
        $_methodActionPost = 'POST';
        $_version = 'v1';
        $_hashType = 'hmac-sha256';
        $_applicationCode = env("RAZOR_MERCHANT_CODE");
        $_urlPayment = env("RAZOR_URL_DEVELPOMENT");
        $_urlReturn = env('FE_URL');
        $_addZero = "00";
        $_urlFrontend = env('FE_URL');


        $client = new Client();
        $response = $client->request($_methodActionPost, $_urlPayment, [
            'headers' => ['Content-type' => 'application/x-www-form-urlencoded'],
            'form_params' => [
                "applicationCode" => $_applicationCode,
                "referenceId" => $dataParse['referenceId'],
                "version" => $_version,
                "amount" => $dataParse['amount'] . $_addZero,
                "currencyCode" => $dataParse['currencyCode'],
                "returnUrl" => $_urlReturn,
                "description" => $dataParse['description'],
                "customerId" => $dataParse['customerId'],
                "hashType" => $_hashType,
                "signature" => Razor::generateSignature($plainText),
            ]
        ]);

        return $response;
    }

    public static function UpdateStatus($request)
    {
        DB::beginTransaction();
        try {

            $plainText = $request['amount']
                . $request['applicationCode']
                . $request['currencyCode']
                . $request['customerId']
                . $request['description']
                . $request['hashType']
                . $request['referenceId']
                . $request['returnUrl']
                . $request['version'];

            $signature = Razor::generateSignature($plainText);

            if ($signature == $request['signature']) {
                if ($request['paymentStatusCode'] == 00) {
                    $status = 1;
                    Log::info('Success Transaction Paid Razor', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with Razor GOLD Invoice ' . $request['referenceId']]);
                } else {
                    $status = 2;
                    Log::info('Cancel Transaction Paid Razor', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with Razor GOLD Invoice ' . $request['referenceId']]);
                };
            } else {
                $status = 2;
                Log::info('Razor signature not valid', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Razor signature not valid with Razor GOLD Invoice ' . $request['referenceId']]);
            }





            Transaction::where('invoice', $request['referenceId'])->update([
                'transaction_status' => $status,
                'paid_time' => $request['paymentStatusDate']
            ]);


            // send invoice
            SendMail::sendMail($request['referenceId']);
            // SendEmailJob::dispatch($request['referenceId']);
            DB::commit();

            return \response()->json([
                'status' => 200,
                'message' => '200 OK',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Razor', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST',
                'error' => 'BAD REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
