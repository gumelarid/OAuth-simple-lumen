<?php

namespace App\Http\Controllers\Topup;

use App\Models\Payment;
use App\Models\PpiList;
use App\Models\Category;
use App\Models\GameList;
use App\Models\PricePoint;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    // get price list
    public function priceList(Request $request)
    {
        try {
            $country = $request->query('country_id');
            $game = $request->query('game_id');
            $image_url = env('Image_URL');

            $ppi = PricePoint::select('pricepoint_id', 'ppi', 'price', 'amount', 'name_currency', 'img')->where('country_id', $country)->where('game_id', $game)->where('is_active', '1')->get();

            $gm = GameList::where('id', $game)->first();

            $result = array();

            foreach ($ppi as $val) {
                $data = [
                    'pricepoint_id' => $val->pricepoint_id,
                    'ppi' => $val->ppi,
                    'price' => $val->price,
                    'amount' => $val->amount,
                    'name_currency' => $val->name_currency,
                    'img' =>  $image_url . '/image/items/' . $gm->slug_game . '/' . $val->img
                ];

                \array_push($result, $data);
            };


            return AllFunction::response(200, 'OK', 'Success Get Price Point List', $result);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function index(Request $request)
    {
        try {
            $image_url = env('Image_URL');
            $country = $request->query('country');
            $pricepoint = $request->query('pricepoint_id');


            $payment = Payment::with('category', 'codePayment')->where('country_id', $country)->where('is_active', '1')->get();


            if (count($payment) == 0) {
                return AllFunction::response(404, 'NOT FOUND', 'Data Payment Not Found');
            };


            $categories = Category::all(); // Fetch all categories from the database

            $result = array();

            foreach ($payment as $pay) {
                $price = PpiList::where('pricepoint_id', $pricepoint)->where('payment_id', $pay->payment_id)->first();

                $data = [
                    'payment_id' => $pay->payment_id,
                    'code_payment' => $pay->codePayment->code_payment,
                    'payment_name' => $pay->payment_name,
                    'channel_id' => $pay->channel_id,
                    'category' => $pay->category->category,
                    'logo' => $image_url . '/image/' . $pay->logo,
                    'status' => ($price) ? true : false
                ];

                foreach ($categories as $category) {
                    if ($pay->category->category_id == $category->category_id) {
                        // Dynamically push data to the appropriate array
                        $categoryName = $category->category;
                        $$categoryName[] = $data;
                    }
                }
            }

            // ...

            // Create a loop to process each category and push to $result
            foreach ($categories as $category) {
                $categoryName = $category->category;

                if (!empty($$categoryName)) {
                    usort($$categoryName, function ($a, $b) {
                        return $a['status'] === $b['status'] ? 0 : ($a['status'] ? -1 : 1);
                    });

                    $categoryData = [
                        'category' => $category->category,
                        'payment' => $$categoryName
                    ];

                    array_push($result, $categoryData);
                }
            }

            // ...

            return AllFunction::response(200, 'OK', 'Success Get Payment List', $result);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
