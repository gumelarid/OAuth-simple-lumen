<?php

namespace App\Helpers;

use DateTime;
use Exception;
use DateInterval;
use Carbon\Carbon;
use App\Helpers\Coda;
use App\Helpers\Razor;
use App\Helpers\Unipin;
use App\Helpers\Payment;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Response;


class HelperPayment
{
    public static function getInvoice($data, $codepayment, $country, $category)
    {

        $url = env('Image_URL');

        $result['invoice'] = $data->toArray();
        $result['game'] = $data->gamelist;
        $result['payment'] = $data->payment;
        $result['payment']['country'] = $country->code_currency;
        $result['payment']['code_payment'] = $codepayment->code_payment;
        $result['payment']['invoice'] = $data->invoice;
        $result['payment']['user'] = $data->transactionDetail->player_id;
        $result['payment']['email'] = $data->email;
        $result['payment']['phone'] = $data->phone;
        $result['payment']['amount'] = $data->transactionDetail->amount;
        $result['payment']['name'] = $data->pricepoints->name_currency;
        $result['payment']['logo'] = $url . '/image/' . $data->payment->logo;
        $result['payment']['logo_ppi'] = $url . '/image/items/' . $data->gamelist->slug_game . '/' . $data->pricepoints->img;
        $result['payment']['game_thumbnail'] = $url . '/cover/' . $data->gamelist->thumbnail;
        $result['payment']['category_item'] = $data->pricepoints->category;
        $result['payment']['expired'] = $data->expired_time;
        $result['payment']['leftTime'] = HelperPayment::calculateLeftTime($data->expired_time);
        // $result['payment']['ppn'] = $this->_invoiceRepository->getAllDataPpn()[0]['ppn'];
        $result['payment']['total_price'] = $data->total_price;

        // $dataTransactions = Transaction::with('payment', 'pricepoints', 'gamelist', 'transactionDetail')->where('invoice', $data->invoice)->first();


        // dd($result);

        $result['attribute'] = HelperPayment::_getPaymentAttribute($result['payment'], $result['game']);


        return $result;
    }

    public static function calculateLeftTime($expireDate)
    {

        $expireTime = Carbon::createFromFormat('Y-m-d H:i:s', $expireDate);
        $current = Carbon::now();
        $leftTimeInSeconds = $current->diffInSeconds($expireTime);

        // Check if the expiration time is already reached or expired
        if ($current >= $expireTime) {
            // Set left time to negative value to indicate expiration
            $leftTimeInSeconds = 0;
        }

        return $leftTimeInSeconds;
    }

    public static function addOneHourToDateTime($dateTimeStr, $timeleft)
    {
        /// Konversi string tanggal dan waktu menjadi objek DateTime
        $dateTime = new DateTime($dateTimeStr);

        // Tambahkan 900 detik (15 menit) ke objek DateTime
        $dateTime->add(new DateInterval('PT' . $timeleft . 'S'));

        // Kembalikan hasil dalam format yang diinginkan (misalnya: 'Y-m-d H:i:s')
        return $dateTime->format('Y-m-d H:i:s');
    }

    public static function redirectToPayment(string $codePayment = null, array $dataParse = null)
    {
        if (empty($dataParse)) return 'Prosess can not be continued, no value.';

        switch (Str::upper(($codePayment))) {
            case env("CODA_CODE_PAYMENT"):
                return Coda::urlRedirect($dataParse);
                break;
            case env("RAZOR_CODE_PAYMENT"):
                return Razor::urlRedirect($dataParse);
                break;
            case env("UNIPIN_CODE_PAYMENT"):
                return Unipin::urlRedirect($dataParse);
                break;
            default:
                echo 'No code payment';
                break;
        }
    }

    public function confrimInfo(array $dataRequest)
    {
        $data = [
            'message' => 'No info'
        ];

        if ($dataRequest['trans_id']) {
            $data['message'] = ($dataRequest['status_desc'] == 'failed') ? 'Payment ' . $dataRequest['status_desc'] . ', please try again.' : 'Payment success, thanks';
            $data['payment'] = 'Motionpay';
            return $data;
        }

        return $data;
    }

    public function checkInvoice(string $id)
    {
        try {

            $invoices = Transaction::with('transactionDetail')->where('invoice', $data->invoice)->first();

            if (!$invoices || !$this->_checkExpireInvoice($invoices['date'])) return false;
            // if (!$this->_checkExpireInvoice($invoices['date'])) throw new Exception('No data', 404);

            return $invoices;
        } catch (\Throwable $error) {
            // dd($error);
            throw $error;
        }
    }

    private static function _getPaymentAttribute($dataPayment = null, $dataGame = null)
    {
        if (empty($dataPayment) || empty($dataGame)) return 'data is null';

        switch (Str::upper($dataPayment['code_payment'])) {
            case env('CODA_CODE_PAYMENT'):

                $dataAttribute = Coda::generateDataParse($dataPayment);
                return json_encode($dataAttribute);
                break;

            case env('GOC_CODE_PAYMENT'):

                $dataAttribute = Goc::generateDataParse($dataPayment);

                return json_encode($dataAttribute);
                break;

            case env('GV_CODE_PAYMENT'):

                $dataAttribute = GudangVoucher::generateDataParse($dataPayment);

                return json_encode($dataAttribute);
                break;

            case env('MOTIONPAY_CODE_PAYMENT'):

                $dataAttribute = MotionPay::generateDataParse($dataPayment);

                return $dataAttribute;
                break;

            case env('UNIPIN_CODE_PAYMENT'):

                $dataAttribute = Unipin::generateDataParse($dataPayment, $dataGame);

                return json_encode($dataAttribute);
                break;

            case env('RAZOR_CODE_PAYMENT'):

                $dataAttribute = Razor::generateDataParse($dataPayment);

                return json_encode($dataAttribute);
                break;

            default:
                return abort(404, 'Payment can\'t find.');
                break;
        }
    }

    private function _checkExpireInvoice(string $date)
    {
        $expireInvoiceTimeMinute = 5;
        $now = Carbon::createFromTimeString(Carbon::now());
        $expireInvoice = Carbon::createFromFormat('Y-m-d H:i:s', $date)->addMinutes($expireInvoiceTimeMinute);

        if ($now >= $expireInvoice->toDateTimeString()) return false;

        return true;
    }
}
