<?php

use App\Currency\Currency;
use App\Games\Kernel\ProvablyFair;
use App\Mail\ResetPassword;
use App\Models\PasswordReset;
use App\Models\Settings;
use App\Permission\Permissions;
use App\Utils\APIResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Models\User;

if (!function_exists('createUser')) {
  function createUser($email, $login, $password, $avatar = null, $additionalData = []): User {
    $user = User::create(array_merge([
      'name' => $login,
      'password' => $password == null ? null : Hash::make($password),
      'avatar' => $avatar ?? '/avatar/' . uniqid(),
      'email' => $email,
      'client_seed' => ProvablyFair::generateServerSeed(),
      'roles' => [],
      'name_history' => [['time' => Carbon::now(), 'name' => $login]],
      'register_ip' => User::getIp(),
      'login_ip' => User::getIp(),
      'register_multiaccount_hash' => request()->hasCookie('s') ? request()->cookie('s') : null,
      'login_multiaccount_hash' => request()->hasCookie('s') ? request()->cookie('s') : null
    ], $additionalData));

    if (isset($_COOKIE['c'])) {
      $referrer = User::where('name', $_COOKIE['c'])->first();
      if ($referrer != null) {
        $user->update(['referral' => $referrer->_id]);
        $user->balance(Currency::all()[0])->add(floatval(Currency::all()[0]->option('referral_bonus')), App\Models\Transaction::builder()->message('Registered via referral link')->get());
      }
    }

    auth()->login($user, true);
    return $user;
  }
}

if (!function_exists('validateCaptcha')) {
  function validateCaptcha($payload): bool {
    return true;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
      'secret' => Settings::get('recaptcha_secret_key'),
      'response' => $payload,
      'remoteip' => User::getIp()
    ]);

    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $data['success'];
  }
}

Route::middleware('auth:sanctum')->post('/update', function () {
  return APIResponse::success(userInfo(auth('sanctum')->user()));
});

if(!function_exists('userInfo')) {
  function userInfo(User $user): array {
    return array_merge($user->toArray(), [
      'vipLevel' => $user->vipLevel(),
      'permissions' => Permissions::whitelistedRolesAndPermissions($user->roles)
    ]);
  }
}

Route::post('resetPassword', function (Request $request) {
  if ($request->type) {
    if ($request->type === 'validateToken') return PasswordReset::where('user', $request->user)->where('token', $request->token)->first() ? APIResponse::success() : APIResponse::reject(2, 'Invalid token');
    if ($request->type === 'reset') {
      $user = User::where('_id', $request->user)->first();
      if (!$user || PasswordReset::where('user', $request->user)->where('token', $request->token)->first() == null) return APIResponse::reject(3, 'Invalid token');

      PasswordReset::where('user', $request->user)->where('token', $request->token)->delete();

      $user->update(['password' => Hash::make($request->password)]);
      return APIResponse::success();
    }

    return APIResponse::reject(1, 'Invalid type');
  }

  $user = User::where('email', $request->login)->orWhere('name', $request->login)->first();
  if (!$user) return APIResponse::success();

  $token = ProvablyFair::generateServerSeed();

  PasswordReset::create([
    'user' => $user->_id,
    'token' => $token
  ]);

  Mail::to($user)->send(new ResetPassword($user->_id, $token));

  return APIResponse::success();
});

Route::post('/login', function (Request $request) {
  $request->validate([
    'login' => ['required', 'string'],
    'password' => ['required', 'string', 'min:5'],
    'captcha' => ['required']
  ]);

  if (!validateCaptcha($request->captcha)) return APIResponse::reject(2, 'Invalid captcha');

  $user = User::where('email', $request->login)->orWhere('name', $request->login)->first();
  if (!$user || !Hash::check($request->password, $user->password)) return APIResponse::reject(1, 'Wrong credentials');

  $token = $user->createToken()->plainTextToken;
  auth()->login($user, true);

  $user->update([
    'login_ip' => User::getIp(),
    'login_multiaccount_hash' => request()->hasCookie('s') ? request()->cookie('s') : null,
    'tfa_persistent_key' => null,
    'tfa_onetime_key' => null
  ]);

  return APIResponse::success([
    'user' => userInfo($user),
    'token' => $token
  ]);
});

Route::post('/register', function (Request $request) {
  $request->validate([
    'email' => ['required', 'unique:users', 'email'],
    'name' => ['required', 'unique:users', 'string', 'max:64', 'regex:/^[a-zA-Z0-9]{4,64}$/u'],
    'password' => ['required', 'string', 'min:5'],
    'captcha' => ['required']
  ]);

  if (!validateCaptcha($request->captcha)) return APIResponse::reject(2, 'Invalid captcha');

  $user = createUser($request->email, $request->name, $request->password);

  return APIResponse::success([
    'user' => userInfo($user),
    'token' => $user->createToken()->plainTextToken
  ]);
});

Route::post('/installerRegister', function (Request $request) {
  if (Settings::get('[Installer] Admin account was created', 'false') === 'true') return APIResponse::reject(1, "Access denied");
  Settings::set('[Installer] Admin account was created', 'true');

  Settings::set('[License] Key', $request->license);

  $user = createUser($request->email, $request->login, $request->password);
  $user->addRole(\App\Models\Role::fromId('*'));

  return APIResponse::success();
});

Route::get('/logout', function () {
  auth('sanctum')->user()->tokens()->delete();
  return APIResponse::success();
});

if (!function_exists('curl')) {
  function curl($url, $params = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if ($params != null) curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }
}

if (!function_exists('apiRequest')) {
  function apiRequest($url, $access_token, $auth = 'Bearer', $post = false, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($post === 'PUT') curl_setopt($ch, CURLOPT_PUT, true);
    else if ($post) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

    $headers[] = 'Accept: application/json';

    $headers[] = 'Authorization: ' . $auth . ' ' . $access_token;

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    return json_decode($response);
  }
}
