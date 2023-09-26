<?php

namespace App\Helpers;


use Exception;
use GuzzleHttp\Client;
use App\Helpers\SendMail;
use App\Models\Transaction;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Unipin
{
    private $_guid, $_secretKey, $_urlPayment, $_methodActionPost, $_currencyIDR, $_urlNotify;

    public function __construct()
    {
        $this->_methodActionPost = 'POST';
        $this->_urlNotify = 'https://esi-paymandashboard.azurewebsites.net/api/v1/transaction/notify';
        $this->_guid = env('UNIPIN_GUID_DEVELOPMENT');
        $this->_secretKey = env('UNIPIN_SECRET_KEY_DEVELOPMENT');
        $this->_urlPayment = env('UNIPIN_URL_DEVELPOMENT');
        $this->_currencyIDR = 'IDR';
    }

    public static function generateDataParse($dataPayment, $dataGame = null)
    {
        dd($dataPayment);
        $dataAttribute = [
            ['methodAction' => $this->_methodActionPost],
            ['urlAction' => route('payment.parse.vendor', strtolower($dataPayment['code_payment']))],
            ['reference' => $dataPayment['invoice']],
            ['remark' => $dataGame['game_title']],
            ['total_price' => $dataPayment['total_price']],
            ['description' => $dataPayment['amount'] . ' ' . $dataPayment['name']],
        ];

        return $dataAttribute;
    }

    public function generateSignature(string $plainText = null)
    {
        $signature = hash('sha256', $plainText);
        return $signature;
    }

    public static function urlRedirect($dataParse)
    {
        $guid = $this->_guid;
        $secretKey = $this->_secretKey;
        $currency = $this->_currencyIDR;
        $reference =  $dataParse['reference'];
        $urlAck = $this->_urlNotify;
        $amount = $dataParse['total_price'];
        $denominations = $amount . $dataParse['description'];
        $signature = $this->generateSignature($guid . $reference . $urlAck . $currency . $denominations . $secretKey);

        $payload = [
            'guid' => $guid,
            'reference' => $reference,
            'urlAck' => $urlAck,
            'currency' => $currency,
            'remark' => $dataParse['remark'],
            'signature' => $signature,
            'denominations' => [
                [
                    'amount' => $amount,
                    'description' => $dataParse['description']
                ]
            ]
        ];

        try {
            $dataResponse = $this->_doRequestToApi($payload);

            if (!$this->checkSignature($dataResponse)) throw new Exception('Invalid Signature', 403);
            if ($dataResponse['url']) return $dataResponse['url'];
        } catch (RequestException $error) {
            echo 'Error message: ' . $error->getCode() . ' ' . $error->getResponse()->getReasonPhrase();
        }
    }

    private function _doRequestToApi(array $payload)
    {
        $client = new Client();
        $response = $client->request($this->_methodActionPost, $this->_urlPayment, [
            'headers' => ['Content-type' => 'application/json'],
            'body' => json_encode($payload),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function checkSignature($dataResponse)
    {
        $signature = $this->generateSignature($dataResponse['status'] . $dataResponse['message'] . $dataResponse['url'] . $this->_secretKey);

        if ($signature == $dataResponse['signature']) return true;

        return false;
    }

    public static function UpdateStatus($request)
    {
        DB::beginTransaction();
        try {
            if ($request['transaction']['status'] == 0) {
                $status = 1;
                Log::info('Success Transaction Paid Unipin', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Success Transaction Paid with GOC Invoice ' . $request['transaction']['reference']]);
            } else {

                $status = 2;
                Log::info('Cancel Transaction Paid Unipin', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | INFO ' . ' | Cancel Transaction Paid with GOC Invoice ' . $request['transaction']['reference']]);
            };

            $trx = Transaction::where('invoice', $request['transaction']['reference'])->update([
                'transaction_status' => $status,
                'paid_time' => date('d-m-Y H:i', $request['transaction']['time'])
            ]);

            // send invoice
            SendMail::sendMail($request['transaction']['reference']);

            DB::commit();

            return \response()->json([
                'status' => $request['transaction']['status'],
                'message' => 'Reload Successful',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Error Notify TopUp Motion Pay', ['DATA' => Carbon::now()->format('Y-m-d H:i:s') . ' | ERR ' . ' | Error Notify TopUp Transaction']);

            return \response()->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => 'BAD_REQUEST',
                'error' => 'BAD REQUEST',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
