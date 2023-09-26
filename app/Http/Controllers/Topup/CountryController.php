<?php

namespace App\Http\Controllers\Topup;

use App\Models\Country;
use App\Helpers\AllFunction;
use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    public function getCountry()
    {
        try {
            $country = Country::select('country_id', 'code_currency', 'country_code', 'country')->where('is_active', '1')->get();

            return AllFunction::response(200, 'OK', 'Success Get Country List', $country);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
