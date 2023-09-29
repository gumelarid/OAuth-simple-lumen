<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AllFunction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;


class EmailController extends Controller
{
    public function sendEmail()
    {

        $data = [
            'name' => 'John Doe',
            'message' => 'This is a test email from Lumen.',
        ];

        Mail::send('mail', $data, function ($message) {
            $message->to('recipient@example.com')->subject('Test Email');
        });

        return 'Email sent successfully!';

        return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
    }
}
