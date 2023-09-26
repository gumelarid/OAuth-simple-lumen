<?php

namespace App\Helpers;

use Exception;
use GuzzleHttp\Client;
use App\Helpers\Payment;
use App\Helpers\SendMail;
use App\Models\Reference;
use App\Helpers\MotionPay;
use App\Models\Transaction;
use Illuminate\Http\Response;
use App\Models\VirtualAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MotionPay
{


    public static function generateDataParse($dataPayment)
    {
        try {
            $_methodActionPost = 'POST';
            $_urlNotify = 'https://esigameshop.co.id/api/v1/transaction/notify';
            $_currencyIDR = 'IDR';
            $_merchantCode = env("MOTIONPAY_MERCHANT_CODE");
            $_secretKey = env("MOTIONPAY_SECRET_KEY");
            $_urlPayment = env("MOTIONPAY_URL_DEVELOPMENT");
            $_timeLimitVa = 60;
            $_statusPending = 'Waiting Paid';
            $_statusSuccess = 'Success';
            $_statusFailed = null;

            // if (MotionPay::checkInvoice($dataPayment['invoice'])) {
            //     $dataVa = MotionPay::checkInvoice($dataPayment['invoice']);
            //     $dataVa['leftTime'] = MotionPay::calculateLeftTime($dataVa['expired_time']);

            //     return $dataVa;
            // }

            $response = MotionPay::getDataToRedirect($dataPayment);

            if (!empty($response['va_number'])) {
                if (!MotionPay::checkSignature($response, $response['va_number'])) {
                    return \response()->json([
                        'code' => Response::HTTP_BAD_REQUEST,
                        'status' => 'Signature VA Not Valid',
                        'error' => 'Signature VA Not Valid',
                    ], Response::HTTP_BAD_REQUEST);
                };




                if ($response['expired_time'] == null) {
                    $time = $dataPayment['expired'];
                } else {
                    $time = $response['expired_time'];
                }

                MotionPay::saveReferenceVa($response);

                $response['leftTime'] = MotionPay::calculateLeftTime($time);
                return $response;
            }

            if (!MotionPay::checkSignature($response)) {
                return \response()->json([
                    'code' => Response::HTTP_BAD_REQUEST,
                    'status' => 'Signature Not Valid',
                    'error' => 'Signature Not Valid',
                ], Response::HTTP_BAD_REQUEST);
            };

            $dataAttribute = [
                ['methodAction' => $_methodActionPost],
                ['urlAction' => $response['frontend_url']],
                ['trans_id' => $response['trans_id']],
                ['merchant_code' => $response['merchant_code']],
                ['order_id' => $response['order_id']],
                ['signature' => $response['signature']],
            ];


            return json_encode($dataAttribute);
        } catch (\Exception $error) {
            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD REQUEST 1',
                'error' => 'BAD REQUEST 1',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public static function generateSignature(string $plainText = null)
    {
        $signature = hash('sha1', md5($plainText));
        return $signature;
    }
    public static function getDataToRedirect($dataParse)
    {
        $_methodActionPost = 'POST';
        $_urlNotify = 'https://esigameshop.co.id/api/v1/transaction/notify';
        $_currencyIDR = 'IDR';

        $_merchantCode = env("MOTIONPAY_MERCHANT_CODE");
        $_secretKey = env("MOTIONPAY_SECRET_KEY");
        $_urlPayment = env("MOTIONPAY_URL_DEVELOPMENT");
        $_timeLimitVa = 60;
        $_statusPending = 'Waiting Paid';
        $_statusSuccess = 'Success';
        $_statusFailed = null;
        $_FE_URL = env('FE_URL');
        try {
            $_dateTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'))->format('YmdHis');
            $merchantCode = $_merchantCode;
            $firstName = $dataParse['user'];
            $lastName = $dataParse['user'];
            $email = $dataParse['email'];
            $phone = $dataParse['phone'] ?? '085105000000';
            $orderId = $dataParse['invoice'];
            $numberReference = $dataParse['phone'] ?? $dataParse['invoice'];
            $amount = (string)$dataParse['total_price'];
            $currency = $_currencyIDR;
            $itemDetails =  $dataParse['amount'] . ' ' . $dataParse['name'];
            $paymentMethod = $dataParse['channel_id'] ?? 'ALL';
            $thanksUrl = $_FE_URL . 'thanks';
            $plainText = $merchantCode
                . $firstName
                . $lastName
                . $email
                . $phone
                . $orderId
                . $numberReference
                . $amount
                . $currency
                . $itemDetails
                . $_dateTime
                . $paymentMethod
                . $_timeLimitVa
                . $_urlNotify
                . $thanksUrl
                . $_secretKey;

            $payload = [
                'merchant_code' => $merchantCode,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'order_id' => $orderId,
                'no_reference' => $numberReference,
                'amount' => $amount,
                'currency' => $currency,
                'item_details' => $itemDetails,
                'datetime_request' => $_dateTime,
                'payment_method' => $paymentMethod,
                'time_limit' => $_timeLimitVa,
                'notif_url' => $_urlNotify,
                'thanks_url' => $thanksUrl,
                'signature' => MotionPay::generateSignature($plainText)
            ];

            $client = new Client();
            $response = $client->request($_methodActionPost, $_urlPayment, [
                'headers' => ['Content-type' => 'application/json'],
                'body' => json_encode($payload),
            ]);

            $dataResponse = json_decode($response->getBody()->getContents(), true);


            MotionPay::saveReference($dataResponse['trans_id'], $dataResponse['order_id']);


            return $dataResponse;
        } catch (RequestException $error) {
            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD REQUEST 2',
                'error' => 'BAD REQUEST 2',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public static function checkSignature($dataResponse, $vaNumber = null)
    {
        $_methodActionPost = 'POST';
        $_urlNotify = 'https://esigameshop.co.id/api/v1/transaction/notify';
        $_currencyIDR = 'IDR';

        $_merchantCode = env("MOTIONPAY_MERCHANT_CODE");
        $_secretKey = env("MOTIONPAY_SECRET_KEY");
        $_urlPayment = env("MOTIONPAY_URL_DEVELOPMENT");
        $_timeLimitVa = 60;
        $_statusPending = 'Waiting Paid';
        $_statusSuccess = 'Success';
        $_statusFailed = null;
        $plainText = null;

        if (empty($vaNumber)) {
            $plainText = $dataResponse['trans_id']
                . $dataResponse['merchant_code']
                . $dataResponse['order_id']
                . $dataResponse['no_reference']
                . $dataResponse['amount']
                . $dataResponse['frontend_url'];
        } else {
            $plainText = $dataResponse['trans_id']
                . $dataResponse['merchant_code']
                . $dataResponse['order_id']
                . $dataResponse['no_reference']
                . $dataResponse['amount']
                . $dataResponse['frontend_url']
                . $dataResponse['fm_refnum']
                . $dataResponse['payment_method']
                . $dataResponse['va_number']
                . $dataResponse['expired_time']
                . $dataResponse['status_code']
                . $dataResponse['status_desc'];
        }

        $signatureMerchat = MotionPay::generateSignature($plainText . $_secretKey);

        if ($dataResponse['signature'] == $signatureMerchat) return true;

        return false;
    }
    public static function saveReference(string $trasnId, string $orderId)
    {
        DB::beginTransaction();
        try {
            if (MotionPay::checkDataReference($orderId)) return;
            MotionPay::saveDataReference($trasnId, $orderId);
            DB::commit();
            return;
        } catch (\Throwable $th) {
            DB::rollback();
            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'FAILED SAVE REFERENCE',
                'error' => 'FAILED SAVE REFERENCE',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public static function saveReferenceVa($data)
    {
        DB::beginTransaction();
        try {
            if (MotionPay::checkDataReferenceVa($data['order_id'])) return;
            MotionPay::saveDataReferenceVa($data);
            DB::commit();
            return;
        } catch (\Throwable $th) {
            DB::rollback();
            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'FAILED SAVE REFERENCE VA',
                'error' => 'FAILED SAVE REFERENCE VA',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public static function calculateLeftTime($expireDate)
    {
        // $expireTime = Carbon::createFromFormat('Y-m-d H:i:s', $expireDate);
        // $current =  Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now());
        // $leftTime = Carbon::parse($current)->diffForHumans($expireTime);

        $expireTime = Carbon::createFromFormat('Y-m-d H:i:s', $expireDate);
        $current = Carbon::now();
        $leftTimeInSeconds = $current->diffInSeconds($expireTime);

        // Convert seconds to other units if needed
        $leftTimeInMinutes = $leftTimeInSeconds / 60;
        $leftTimeInHours = $leftTimeInSeconds / 3600;
        $leftTimeInDays = $leftTimeInSeconds / 86400;

        // If you need the absolute value of the difference, you can use the abs() function
        $leftTime = abs($leftTimeInSeconds);


        return $leftTime;
    }
    public static function checkInvoice(string $id)
    {
        $_methodActionPost = 'POST';
        $_urlNotify = 'https://esigameshop.co.id/api/v1/transaction/notify';
        $_currencyIDR = 'IDR';

        $_merchantCode = env("MOTIONPAY_MERCHANT_CODE");
        $_secretKey = env("MOTIONPAY_SECRET_KEY");
        $_urlPayment = env("MOTIONPAY_URL_DEVELOPMENT");
        $_timeLimitVa = 60;
        $_statusPending = 'Waiting Paid';
        $_statusSuccess = 'Success';
        $_statusFailed = null;
        $plainText = null;

        $dataStatusTransaction = MotionPay::getDataStatusTransaction($id);
        $dataInvoice = MotionPay::getDataInvoceVa($id);

        if (!empty($dataInvoice['expired_time'])) {
            $now = Carbon::createFromTimeString(Carbon::now());
            $expired_time = Carbon::createFromTimeString($dataInvoice['expired_time']);

            if ($now > $expired_time) {
                $dataInvoice['status_desc'] = $_statusFailed;
                return $dataInvoice;
            }

            $dataInvoice['status_desc'] = $_statusPending;

            return $dataInvoice;
        }

        if (!empty($dataInvoice['number_va']) && $dataStatusTransaction['transaction_status'] == 0) {
            $dataInvoice['status_desc'] = $_statusPending;
            return $dataInvoice;
        }

        if (!empty($dataInvoice['number_va']) && $dataStatusTransaction['transaction_status'] == 1) {
            $dataInvoice['status_desc'] = $_statusSuccess;
            return $dataInvoice;
        }

        return;
    }

    // repository
    private static function checkDataReference(string $id)
    {
        return Transaction::where('invoice', $id)->first();
    }

    private static function checkDataReferenceVa(string $id)
    {
        return VirtualAccount::where('invoice', $id)->first();
    }

    private static function saveDataReference(string $trasnId, string $orderId)
    {
        Transaction::where('invoice', $orderId)->update([
            'refence_no' => $trasnId
        ]);

        return;
    }
    private static function saveDataReferenceVa(array $data)
    {
        VirtualAccount::create(['invoice' => $data['order_id'], 'VA' => $data['va_number'], 'expired_time' => $data['expired_time']]);
        return;
    }

    private static function getDataInvoceVa(string $id)
    {
        return VirtualAccount::select(
            'VA as va_number',
            'expired_time'
        )->where('invoice', $id)->first();
    }

    private static function getDataStatusTransaction(string $id)
    {
        return Transaction::select(
            'transaction_status'
        )->where('invoice', $id)->first();
    }

    // end repository


    public static function UpdateStatus($request)
    {
        DB::beginTransaction();
        try {


            if ($request['status_code'] == 200) {
                $status = 1;
                Log::info('Success Transaction Paid Motion Pay', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with Motion Pay Invoice ' . $request['order_id']]);
            } else {

                $status = 2;
                Log::info('Cancel Transaction Paid Motion Pay', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with Motion Pay Invoice ' . $request['order_id']]);
            };


            $trx = Transaction::where('invoice', $request['order_id'])->update([
                'transaction_status' => $status,
                'paid_time' => !$request['datetime_payment'] ? Carbon::now()->format('Y-m-d H:i:s') : $request['datetime_payment']
            ]);

            // send invoice
            SendMail::sendMail($request['order_id']);

            DB::commit();

            return "OK";
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Motion Pay', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST 3',
                'error' => 'BAD REQUEST 3',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
