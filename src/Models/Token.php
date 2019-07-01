<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    public $primaryKey = 'token_id';


    //<editor-fold desc="Relations">
    public function user()
    {
        return $this->hasOne('\App\User', 'user_id', 'user_id');
    }

    public function users()
    {
        return $this->hasMany('\App\User', 'user_id', 'user_id');
    }
    //</editor-fold>

    public static function createApiToken($user_id)
    {
        $api_token = static::uniqueApiToken();

        $token = new Token();
        $token->user_id = $user_id;
        $token->api_token = $api_token;
        $token->save();

        return $token;
    }

    protected static function uniqueApiToken()
    {
        do {
            $token = str_random(60);
        } while (static::where('api_token', $token)->count());

        return $token;
    }
}
