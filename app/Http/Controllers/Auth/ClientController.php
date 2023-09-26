<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Str;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $this->validate($request, [
                'user_id' => 'required',
                'name'    => 'required|string',
            ]);

            $user = User::where('user_id', $request->input('user_id'))->first();

            if (!$user) {
                return Response('User Not Found', 404);
            }

            $user_id  = $request->input('user_id');
            $name     = $request->input('name');
            $redirect = $request->input('redirect');

            $id_client = Str::random(16);
            $secret    = Str::random(64);

            Client::create([
                'id'        =>  $id_client,
                'user_id'   =>  $user_id,
                'name'      =>  $name,
                'secret'    =>  $secret,
                'redirect'  =>  $redirect,
                'revoke'    =>  0
            ]);

            DB::commit();
            return Response([
                'message'   =>  'Success Create Client',
                'data'      => [
                    'name_app'      => $name,
                    'id'        => $id_client,
                    'secret'    => $secret,
                    'redirect'  => $redirect
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function revoke(Request $request)
    {
    }
}
