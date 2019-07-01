<?php

namespace App\Support;

use App\Token;
use Illuminate\Support\Facades\Auth;

class Util
{
    public static function currentUser()
    {
        $api_token = '';

        if (isset(\Request::all()['api_token'])) {
            $api_token = \Request::all()['api_token'];
        }

        if (\Request::bearerToken()) {
            $api_token = \Request::bearerToken();
        }

        if ($api_token != '') {
            $token = Token::where('api_token', $api_token)->first();
            if ($token) {
//                dd($token->user, $token->users);
                return \App\User::where('user_id', '=', $token->user_id)->first();
            }
        }

        return null;
    }
}