<?php

namespace App\Http\Controllers\Topup;

use Carbon\Carbon;
use App\Helpers\Goc;
use App\Models\User;
use App\Helpers\Coda;
use App\Helpers\Razor;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Category;
use App\Models\GameList;
use App\Helpers\MotionPay;
use App\Models\PricePoint;
use App\Models\CodePayment;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\GudangVoucher;
use App\Helpers\HelperPayment;

use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Player;

class TransactionController extends Controller
{


    public function transaction(Request $request)
    {

        try {

            if ($request->username == null) {
                return AllFunction::response(400, 'Input Not Valid', $request->all());
            };

            DB::beginTransaction();
            $game = GameList::where('slug_game', $request->game)->first();

            if (!$game) {
                return AllFunction::response(404, 'NOT_FOUND', 'Game List Not Found');
            };


            $payment = Payment::where('payment_id', $request->payment_id)->first();


            if (!$payment) {
                return AllFunction::response(404, 'NOT_FOUND', 'Payment Not Found');
            };

            $pricepoint = PricePoint::where('pricepoint_id', $request->pricepoint_id)->first();

            if (!$pricepoint) {
                return AllFunction::response(404, 'NOT_FOUND', 'Price Point Not Found');
            };

            // check apakah price dibypass
            if ($pricepoint->price != $request->price || $pricepoint->amount != $request->amount) {
                return AllFunction::response(401, 'Something Wrongs', 'your input not valid');
            };


            $category = Category::select('category_id', 'category', 'expired_time')->where('category_id', $payment->category_id)->first();

            $timeleft = HelperPayment::addOneHourToDateTime(Carbon::now(), $category->expired_time);


            $last = Transaction::latest()->first();


            $invoice = AllFunction::generateInvoice($payment->category_id, $last);

            $transaction_id = Str::uuid();
            Transaction::create([
                'transaction_id' => $transaction_id,
                'invoice' => $invoice,
                'payment_id' => $request->payment_id,
                'email' => $request->email,
                'phone' => $request->phone,
                'game_id' => $game->game_id,
                'total_price' => $request->price,
                'pricepoint_id' => $request->pricepoint_id,
                'transaction_status' => '0',
                'expired_time' => $timeleft,
                'user_id' => $request->user_id ? $request->user_id : null,
                'refence_no' => null
            ]);

            // check player id
            $player = Player::where('user_id', $request->user_id)->first();

            if ($player) {
                if ($player->player_id !== $request->player_id) {
                    Player::create([
                        'player_Id' => $request->player_id,
                        'user_id'   => $request->user_id,
                        'game_id'   => $game->game_id
                    ]);
                }
            } else {
                if ($request->user_id) {
                    Player::create([
                        'player_Id' => $request->player_id,
                        'user_id'   => $request->user_id,
                        'game_id'   => $game->game_id
                    ]);
                }
            }

            // check user
            $user = User::where('user_id', $request->user_id);
            if ($user->first()) {

                if ($user->first()->email == null) {
                    $user->update([
                        'email' => $request->email
                    ]);
                }

                if ($user->first()->phone == null) {
                    $user->update([
                        'phone' => $request->phone
                    ]);
                }
            }


            TransactionDetail::create([
                'transaction_id' => $transaction_id,
                'player_id' => $request->player_id,
                'amount' => $request->amount,
                'username' => $request->username,
                'server' => $request->game_server,
                'shipping_status' => '0',
            ]);

            DB::commit();
            return AllFunction::response(200, 'OK', 'Success Checkout Transaction', $invoice);
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }


    public function confirmation(Request $request)
    {
        try {

            $invoice = $request->query('invoice');

            $data = Transaction::with('payment', 'pricepoints', 'gamelist', 'transactionDetail')->where('invoice', $invoice)->first();



            if (!$data) {

                return AllFunction::response(404, 'NOT_FOUND', 'Invoice Not Found');
            };

            $category = Category::select('category_id', 'category', 'expired_time')->where('category_id', $data->payment->category_id)->first();

            // get country code
            $country = Country::where('country_id', $data->payment->country_id)->first();

            // get code_payment
            $codepayment = CodePayment::where('id', $data->payment->code_payment)->first();


            $result = HelperPayment::getInvoice($data, $codepayment, $country, $category);

            return AllFunction::response(200, 'OK', 'Success get Invoice', $result);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }


    public function expired(Request $request)
    {

        try {
            DB::beginTransaction();
            $orderId = $request->query('invoice');
            $checkInvoice = Transaction::where('invoice', $orderId)->first();

            if (!$checkInvoice) {
                return AllFunction::response(404, 'NOT_FOUND', 'Invoice Not Found');
            };

            Transaction::where('invoice', $orderId)->update([
                'transaction_status' => 3
            ]);

            DB::commit();
            return AllFunction::response(200, 'OK', 'Transaction Expired');
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function saveReverence(Request $request)
    {

        try {
            DB::beginTransaction();
            $orderId = $request->query('order_id');
            $paymentId = $request->query('reference');
            $checkInvoice = Transaction::where('invoice', $orderId)->first();

            if (!$checkInvoice) {
                return AllFunction::response(404, 'NOT_FOUND', 'Invoice Not Found');
            };

            Transaction::where('invoice', $orderId)->update([
                'refence_no' => $paymentId
            ]);

            DB::commit();
            return AllFunction::response(200, 'OK', 'Save Reference success');
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function pay(Request $request)
    {

        $data = Transaction::where('invoice', $request->invoice)->first();

        Transaction::where('invoice', $request->invoice)->update([
            'transaction_status' => $request->status
        ]);

        return AllFunction::response(200, 'OK', 'Payment success');
    }

    public function notify(Request $request)
    {


        $trx = null;
        $status = null;

        $result = null;

        Log::info('info', ['data' => $request->all()]);

        if ($request->trans_id) {
            // Motion Pay
            $result =  MotionPay::UpdateStatus($request->all());
        } else if ($request->data) {
            // GV
            $result = GudangVoucher::UpdateStatus($request->all());
        } else if ($request->applicationCode) {
            // Razor
            $result = Razor::UpdateStatus($request->all());
        } else if ($request->TxnId) {
            // Coda
            $result = Coda::UpdateStatus($request->all());
        } else {
            // GOC ;
            $result = Goc::UpdateStatus($request->all());
        };


        return $result;
    }
}
