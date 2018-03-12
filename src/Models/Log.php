<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public $primaryKey = 'log_id';
    protected $fillable = [
        'user_id', 'method', 'model', 'message', 'user_ip', 'user_agent', 'created_at', 'updated_at'
    ];


    public static function out($method, $model, $message, $user_id = null, $stringMessage = '') {

        if(!$user_id){
            $user_id = \Auth::check() ? \Auth::user()->user_id : null;
        }

        if($stringMessage){
//            dd($stringMessage, $message);
        }

        $log = new self();

        if ($user_id) {
            $log->user_id = $user_id;
            $log->user_ip = request()->ip();
            $log->user_agent = isset($_SERVER) && isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        } else {
            $log->user_id = null;
            $log->user_ip = null;
            $log->user_agent = null;
        }
        $log->method = $method;
        $log->model = $model;
        $log->message = $message ? json_encode($message) : $stringMessage;
        $log->save();
    }

    protected static function boot() {
        parent::boot();
        if(\Auth::check()){
//            static::addGlobalScope(new \App\Scopes\MyLogs());
        }
    }

    public function getMessageAttribute(){
        $mess = json_decode($this->attributes['message']);
        if(!$mess){
            return $this->attributes['message'];
        }
        return $mess;
    }

    public $with = [
        'user'
    ];

    public function rel(){

    }

    public function user() {
        return $this->hasOne('App\User', 'user_id', 'user_id');
    }
}
