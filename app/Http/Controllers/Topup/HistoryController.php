<?php

namespace App\Http\Controllers\Topup;

use Carbon\Carbon;
use App\Models\Transaction;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HistoryController extends Controller
{
    public function index(Request $request)
    {

        try {

            $url = env('FE_URL');
            $user_id = $request->user_id;

            $data = Transaction::with('transactionDetail', 'gamelist', 'pricepoints')->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

            $result = [];
            foreach ($data as $key) {
                $dt = [
                    'date'  => Carbon::parse($key->created_at)->format('d/m/Y H:i'),
                    'game' => $key->gamelist->game_title,
                    'slug' => $url . 'payment/' . $key->gamelist->slug_game,
                    'item'  => $key->pricepoints->amount . ' ' . $key->pricepoints->name_currency,
                    'user_id' => $key->user_id,
                    'invoice' => $key->invoice,
                    'status' => $key->transaction_status
                ];

                \array_push($result, $dt);
            };


            return AllFunction::response(200, 'OK', 'Success get history',  $result);
        } catch (\Throwable $th) {

            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
