<?php

namespace App\Helpers;

use Firebase\JWT\JWT;

class AllFunction
{

    // response
    public static function response($code = 200, $status = 'OK', $message = null, $data = null)
    {

        return Response([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    // generate Access Token
    public static function generateAccessToken($secret, $user_id, $app_id, $expired)
    {
        $payload = array(
            "sub" => $user_id,
            "iss" => $app_id,
            "iat" => time(),
            "exp" => $expired->timestamp
        );

        return JWT::encode($payload, $secret, 'HS256');
    }

    // generetae refresh Token
    public static function generateRefreshToken($secret, $user_id, $app_id, $refreshExpired)
    {
        $payload = array(
            "sub" => $user_id,
            "iss" => $app_id,
            "exp" => $refreshExpired->timestamp
        );

        return JWT::encode($payload, $secret, 'HS256');
    }


    public static function generateId($data)
    {

        $currentYear = date('y');
        $currentMonth = date('m');

        $id = '1' . $currentYear . $currentMonth;

        // Retrieve the last generated ID from your data source (e.g., database)
        $lastGeneratedId = $data; // Replace with your own logic

        // Check if the ID has been generated before
        if ($lastGeneratedId === null || substr($lastGeneratedId, 1, 4) !== $id) {
            // If the ID has not been generated before or it's a new month,
            // reset the increment to 1
            $increment = 1;
        } else {
            // If the ID has been generated before in the same month,
            // increment the last generated ID
            $increment = intval(substr($lastGeneratedId, -4)) + 1;
        }

        // Pad the increment with leading zeros
        $increment = str_pad($increment, 4, '0', STR_PAD_LEFT);

        // Combine the ID pattern with the increment
        $generatedId = $id . $increment;

        // Store or update the generated ID in your data source

        return $generatedId;
    }
}
