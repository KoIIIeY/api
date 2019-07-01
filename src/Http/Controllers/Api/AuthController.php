<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use App\Token;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;

use Laravel\Socialite\Facades\Socialite;

/**
 * Class AuthController
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * AuthController constructor.
     */
    public function __construct()
    {
        $this->middleware(StartSession::class, ['only' => 'postSocial']); // socialite fix
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postRegister(Request $request)
    {
        $data = $request->all();

        if(!isset($data['email']) || User::where('email', '=', $data['email'])->first()){
            return new JsonResponse(['email' => ['Емэйл уже занят']], 422);
        }

        $user = new User();
        $user->fill($data);
        $user->save();

        $token = Token::createApiToken($user->user_id);

        \App\Log::out('timeline', 'user', null, $user ? $user->user_id : null, 'Регистрация на сайте');

        return new JsonResponse(['api_token' => $token->api_token]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postLogin(Request $request)
    {
//        $this->validate($request, [
//            'email' => 'required',
//            'password' => 'required'
//        ]);
        $credentials = $request->only('email', 'password');

        if (!auth('web')->attempt($credentials)) {
            return new JsonResponse(['email' => ['Неверный email или пароль']], 401);
        }
        $user = auth('web')->user();

        $token = Token::createApiToken($user->user_id);

        return new JsonResponse(['api_token' => $token->api_token]);
    }



    public function getLogout(Request $request)
    {
        $api_token = '';

        if (isset(\Request::all()['api_token'])) {
            $api_token = \Request::all()['api_token'];
        }

        if (\Request::bearerToken()) {
            $api_token = \Request::bearerToken();
        }

        if ($api_token == '') {
            return;
        }

        Token::where('api_token', $api_token)->delete();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrent(Request $request)
    {
        $data = $request->all();
        $api_token = '';
        $ec = new EntityController();

        if (!Auth::user()) {
            if (isset($data['api_token'])) {
                $api_token = $data['api_token'];
            }

            if (!isset($data['api_token'])) {
                $api_token = $request->bearerToken();
            }

            if ($api_token != '') {
                $token = Token::where('api_token', $api_token)->first();
                if ($token) {
                    $token->updated_at = Carbon::now();
                    $token->save();

                    $user = $ec->show($request, '\App\User', $token->user_id);
                    return $user;
                }
            }
        }

        if (Auth::check() && Auth::user()) {
            $user = $ec->show($request, '\App\User', Auth::user()->user_id);
            return $user;
        }

        return new JsonResponse([], 401);
    }

    public function postMail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $mail = $request->email;
        $user = \App\User::where('email', $mail)->first();
        if ($user) {
            $reset = \App\Password_Reset::where('user_id', $user->user_id)->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-5 minute')))->first();
            if ($reset) {
                return response()->json(['messages' => ['Вы уже сделали попытку сброса пароля, следующая доступна через 5 минут']]);
            }
            if (!$reset) {
                $reset = new \App\Password_Reset;
            }
            $reset->user_id = $user->user_id;
            $reset->code = str_random(6);
            $reset->save();
            \App\Email::sendEmail($mail, 'Сброс пароля', 'Код для смены пароля - ' . $reset->code);
            //            \Mail::to($mail)->send(new \App\Mail\PasswordReset($mail));
            return response()->json(['messages' => ['Инструкции высланы на ' . $mail]]);
        }

        return response()->json(['messages' => ['Инструкции высланы на ' . $mail]]);
    }

    public function verify()
    {
        $data = \Request::all();

        if (!\Auth::check()) {
            return response()->json(['message' => 'Вы не авторизованы'], 422);
        }

        if ($data['type'] == 'email') {
            //            \Mail::to(auth()->user()->email)->send(new \App\Mail\VerifyEmail(auth()->user()));
            $user = auth()->user();

            //            \App\Email::sendEmail($user->email, 'Код подтверждения емэйл', 'Ваш код подтвержения - ' . substr(md5(($user->email . $user->user_id)), 0 , 5));

            \App\Email::sendVerifyEmail($user);

            return response()->json(['message' => 'Код отправлен на ваш емэйл']);
        }

        if ($data['type'] == 'sms') {
            if (strlen(auth()->user()->phone) < 10) {
                return response()->json(['message' => 'Введите верный телефон']);
            }

            $sms = new \App\Sms();
            $sms->to = auth()->user()->phone;
            $sms->user_id = auth()->user()->user_id;
            $sms->message = 'Код: ' . substr(md5((auth()->user()->phone . auth()->user()->user_id)), 0, 5);
            $sms->save();
            return response()->json(['message' => 'Код отправлен на ваш телефон']);
        }
    }

    public function changePassword(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_repeat' => 'required|same:password|min:6'
        ]);

        $code = $request->input('code');
        if ($code) {

            $user = \App\User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['messages' => ['Произошла ошибка']]);
            }

            $resetter = \App\Password_Reset::where('code', $code)->where('user_id', $user->user_id)->where('updated_at', '>', date('Y-m-d H:i:s', strtotime('-30 min')))->orderBy('created_at', 'desc')->first();
            if ($resetter) {
                $user = \App\User::where('email', $request->email)->first();
                $user->password = $request->password;
                $user->save();
                $resetter->delete();
                return response()->json(['messages' => ['Ваш пароль изменен']]);
            }
        }
        return response()->json(['messages' => ['Произошла ошибка']]);
    }


    public static function goToSoc($type){
        return Socialite::with($type)->stateless()->redirect();
    }

    public static function social($type = null)
    {
//        dd(Socialite::with($type)->stateless()->user());
        try {
            $u = Socialite::with($type)->stateless()->user();
            \Cache::put('user-'.$type.\Request::input('code'), $u, 1);
        } catch(\Exception $e){
            $u = \Cache::pull('user-'.$type.\Request::input('code'));
        }
        if(!$u){
            return redirect('/');
        }
//        dd($u);

        $email = '';
        try {
            $email = $u->getEmail();
        } catch(\Exception $e){

        }

        if(!$email){
            $email = $u->accessTokenResponseBody['email'] ?? '';
        }

        if($user = \App\User::where($type.'_id', '=', $u->getId())->first()){
            $token = Token::createApiToken($user->user_id);
            return redirect('/login/'.$token->api_token);
        } else {
            $user = new User();
            $user->email = $email;
            $user->{$type.'_id'} = $u->getId();
            $user->save();

            $token = Token::createApiToken($user->user_id);

            \App\Log::out('timeline', 'user', null, $user ? $user->user_id : null, 'Регистрация на сайте');
            return redirect('/login/'.$token->api_token);
        }
    }


}
