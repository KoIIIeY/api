<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use App\Account;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AuthController
 * @package App\Http\Controllers
 */
class AuthController extends \Illuminate\Routing\Controller
{

    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        $user = new User();

        $data = $request->all();

        $user->fill($data);
        $user->save();

        \App\Log::out('timeline', 'user', null, $user ? $user->user_id : null, 'Регистрация на сайте');


        //// КОДЫ ПОДТВЕРЖДЕНИЯ ЕМЭЙЛ И ТЕЛЕФОН
        //        \App\Email::sendEmail($user->email, 'Код подтверждения емэйл', 'Ваш код подтвержения - ' . substr(md5(($user->email . $user->user_id)), 0 , 5));

        //        if(env('SEND_EMAIL')){
        //            \App\Email::sendVerifyEmail($user);
        //
        //            $sms = new \App\Sms();
        //            $sms->to = $user->phone;
        //            $sms->user_id = $user->user_id;
        //            $sms->message = 'Код: '. substr(md5(($user->phone . $user->user_id)), 0, 5);
        //            $sms->save();
        //
        //        }
        ////

        return new JsonResponse(['api_token' => $user->api_token]);
    }

    public function checkToken(){
        $token = \App\User::where('api_token', '=', \Request::input('token'))->count();
        return response()->json(['verified' => $token ? true : false]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
        ]);
        session()->flush();
        $credentials = $request->only('email', 'password');

        if (!auth('web')->attempt($credentials)) {
            return new JsonResponse(['message' => ['Неверный логин или пароль']], 401);
        }
        $user = auth('web')->user();

        return new JsonResponse(['api_token' => $user->api_token]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrent(Request $request)
    {
        $user_id = auth()->id();

        if ($user_id) {
            $user = auth()->user();

            $globalPermissions = [];

            $userRoleReferences =
                \App\UserRole::withoutGlobalScopes()
                ->where('user_id', '=', $user->user_id)
                ->get();

            $userRoles = [];
            if (count($userRoleReferences))
            {
                $roles = \App\Role::withoutGlobalScopes()
                    ->whereIn('role_id', $userRoleReferences->pluck('role_id'))
                    ->with('parent')
                    ->get();
                $userRoles = $roles->toArray();
            }

            foreach ($userRoles as $userRole)
            {
                $currentRole = $userRole;

                while($currentRole)
                {
                    $permissions =
                        \App\RolePermission::withoutGlobalScopes()
                            ->where('role_id', '=', $currentRole['role_id'])
                            ->get();

                    foreach ($permissions as $permission)
                    {
                        $entity = $permission->entity;
                        $permission = collect($permission)->toArray();

                        if (!isset($globalPermissions[$entity]))
                        {
                            $globalPermissions[$entity] = $permission;
                        }
                        else
                        {
                            foreach ($permission as $key => $value)
                            {
                                if (!isset($globalPermissions[$entity][$key]) ||
                                    $globalPermissions[$entity][$key] == 'inherit' &&
                                    ($value == 'allow' || $value == 'deny'))
                                {
                                    $globalPermissions[$entity][$key] = $value;
                                }
                            }
                        }
                    }

                    if ($currentRole['parent_role_id'] == 0)
                    {
                        break;
                    }

                    $parentRole = \App\Role::withoutGlobalScopes()
                        ->where('role_id', '=', $currentRole['parent_role_id'])
                        ->first();
                    $parentRole = collect($parentRole)->toArray();
                    if ($parentRole['role_id'] == $currentRole['role_id'])
                    {
                        $currentRole = null;
                        continue;
                    }
                    $currentRole = $parentRole;
                }
            }

            $userPermissions =
                \App\UserEntityPermission::withoutGlobalScopes()
                ->where('user_id', '=', $user->user_id)
                ->get();

            foreach ($userPermissions as $userPermission)
            {
                $entity = $userPermission->entity;
                $userPermission = collect($userPermission)->toArray();

                if (!isset($globalPermissions[$entity]))
                {
                    $globalPermissions[$entity] = $userPermission;
                }
                else
                {
                    foreach ($userPermission as $key => $value)
                    {
                        if ($value == 'allow' || $value == 'deny')
                        {
                            $globalPermissions[$entity][$key] = $value;
                        }
                    }
                }
            }

            $canAdmin = false;

            foreach ($globalPermissions as $permission)
            {
                if ($permission['admin'])
                {
                    $canAdmin = true;
                    break;
                }
            }

            $user->globalPermissions = $globalPermissions;
            $user->canAdmin = $canAdmin;
            $user->roles = $userRoles;

            $selectedRole = request()->selectedRole;

            if ($selectedRole != null)
            {
                foreach ($userRoles as $userRole)
                {
                    if ($userRole['role_id'] == intval($selectedRole))
                    {
                        session(['selectedRole' => $selectedRole]);
                        break;
                    }
                }
                if (intval($selectedRole) == 0)
                {
                    session(['selectedRole' => $selectedRole]);
                }
            }

            if (session('selectedRole') != null)
            {
                $user->selectedRole = session('selectedRole');
            }
            else if (count($userRoles) > 1)
            {
                $selectedRole = strval($userRoles[0]['role_id']);
                $user->selectedRole = $selectedRole;
                session(['selectedRole' => $selectedRole]);
            }

            return response()->json($user);
        } else {
            $user = (new User())->newQuery()->where('user_id', $user_id);

            $with = $request->get('with', []);

            if (is_array($with)) {
                foreach ($with as $relation) {
                    $user->with($relation);
                }
            }
            $user = $user->first();

            if (!$user) {
                return new JsonResponse([], 401);
                //                return new JsonResponse(null);
            }
        }

        return new JsonResponse($user, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function postSocial(Request $request, $provider)
    {
        $config = config('services.' . $provider);

        if (!$config || !array_key_exists('client_id', $config) || $request->get('clientId') != $config['client_id']) {
            abort(404, 'Invalid service configuration, check config for service: ' . $provider);
        }
        $request->merge(['state' => 'socialite_fix']);
        $request->session()->put('state', 'socialite_fix');

        /**
         * @var \Laravel\Socialite\Contracts\User $providerUser
         */
        $providerUser = Socialite::driver($provider)->user();

        if (!$providerUser) {
            abort(400);
        }
        /**
         * @var User|null $existsUser
         */
        $existsUser = User::whereSocial($provider, $providerUser->getId())->first();

        if ($existsUser) {
            $existsUser->updateApiToken(true);
            return new JsonResponse(['api_token' => $existsUser->api_token]);
        }
        $data = array_reduce(['id', 'nickname', 'avatar', 'email', 'name'], function ($result, $v) use ($providerUser) {
            $result[$v] = call_user_func([$providerUser, 'get' . studly_case($v)]);
            return $result;
        }, []);

        if ($data['avatar']) {
            $destPath = 'uploads/images';
            $ext = /*last(explode('.', head(explode('?', $data['avatar'])))) ?:*/
                'jpg'; // todo: remove hardcode

            do {
                $filename = str_random(20) . '.' . $ext;
                $path = implode('/', [$destPath, $filename]);
            } while (file_exists(public_path($path)));

            file_put_contents(public_path($path), file_get_contents($data['avatar']));
            $data['avatar'] = $path;
        }
        list($firstname, $lastname) = explode(' ', $data['name']);

        if (!$data['nickname']) {
            $data['nickname'] = $data['id'];
        }
        while (User::where('username', $data['nickname'])->count()) {
            $data['nickname'] .= '_' . rand(100, 999);
        }

        $user = new User();
        $user->fill([
            'username' => $data['nickname'],
            'avatar' => $data['avatar'],
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);
        $user->social_type = $provider;
        $user->social_id = $data['id'];
        $user->save();

        return new JsonResponse(['api_token' => $user->api_token], 200, [], JSON_NUMERIC_CHECK);
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

}
