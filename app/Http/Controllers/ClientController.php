<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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

        return Response([
            'message'   =>  'Success Create Client',
            'data'      => [
                'name_app'      => $name,
                'id'        => $id_client,
                'secret'    => $secret,
                'redirect'  => $redirect
            ]
        ], 200);
    }

    public function revoke(Request $request)
    {
    }
}
