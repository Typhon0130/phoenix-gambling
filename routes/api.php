<?php

use App\ActivityLog\ChatClearLog;
use App\ActivityLog\ChatUnmutelog;
use App\Models\Chat;
use App\Currency\Currency;
use App\Events\ChatMessage;
use App\Events\NewQuiz;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Utils\APIResponse;
use App\WebSocket\WebSocketWhisper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Facades\Image;
use Symfony\Component\Process\Process;

Route::post('/whisper', function (Request $request) {
  $whisper = WebSocketWhisper::find($request->event);
  if ($whisper == null) return APIResponse::reject(1);

//    $whisper->id = $request->data->id;
  $whisper->user = auth('sanctum')->user();

  $response = $whisper->process((object)$request->data);
  //$whisper->sendResponse($response);
  return APIResponse::success($response);
});

Route::post('commerce/callback', function(Request $request) {
  Log::info($request);
  \App\Currency\Commerce\Utils\CoinbaseCommerce::handle($request->event->payments);
  return APIResponse::success();
});

Route::get('walletNotify/{currency}/{txid}', function ($currency, $txid) {
  return APIResponse::success(['result' => Currency::find($currency)->process($txid)]);
});

Route::get('blockNotify/{currency}/{blockId}', function ($currency, $blockId) {
  Currency::find($currency)->processBlock($blockId);
  return APIResponse::success();
});

Route::prefix('data')->group(function () {
  Route::post('/latestGames', function (Request $request) {
    $result = [];

    if ($request->mode === 'casino') {
      switch ($request->type) {
        case 'third-party':
          $games = \App\Models\Game::latest()->where('demo', '!=', true)->where('user', '!=', null)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->where('type', 'third-party')->take($request->count)->get()->reverse();
          break;
        case 'mine':
          $games = \App\Models\Game::latest()->where('demo', '!=', true)->where('user', auth('sanctum')->user()->_id)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->take($request->count)->get()->reverse();
          break;
        case 'all':
          $games = \App\Models\Game::latest()->where('demo', '!=', true)->where('user', '!=', null)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->take($request->count)->get()->reverse();
          break;
        case 'lucky_wins':
          $games = \App\Models\Game::latest()->where('multiplier', '>=', 10)->where('demo', '!=', true)->where('user', '!=', null)->where('status', 'win')->take($request->count)->get()->reverse();
          break;
        case 'high_rollers':
          $hrResult = [];
          $games = \App\Models\Game::latest()->where('demo', '!=', true)->where('user', '!=', null)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->take($request->count)->get()->reverse();
          foreach ($games as $game) {
            if ($game->wager < floatval(\App\Currency\Currency::find($game->currency)->option('high_roller_requirement'))) continue;
            array_push($hrResult, $game);
          }
          $games = $hrResult;
          break;
      }

      foreach ($games as $game) {
        $gameObject = Game::find($game->game);
        if ($gameObject == null) continue;
        $result[] = [
          'game' => $game->toArray(),
          'user' => User::where('_id', $game->user)->first()->toArray(),
          'metadata' => $gameObject->metadata()->toArray()
        ];
      }
    } else if ($request->mode === 'sport') {
      switch ($request->type) {
        case 'mine':
          $games = \App\Models\SportBet::latest()->where('demo', '!=', true)->where('user', auth('sanctum')->user()->_id)->take($request->count)->get()->reverse();
          break;
        default:
          $games = \App\Models\SportBet::latest()->where('demo', '!=', true)->where('user', '!=', null)->take($request->count)->get()->reverse();
          break;
      }

      foreach ($games as $game) $result[] = [
        'game' => $game->toArray(),
        'user' => User::where('_id', $game->user)->first()->toArray()
      ];
    }

    return APIResponse::success($result);
  });
  Route::post('/notifications', function () {
    $notifications = \App\Models\GlobalNotification::get()->toArray();

    if(!auth('sanctum')->guest()) {
      if (env('APP_DEBUG') && !str_contains(request()->url(), 'localhost') && auth('sanctum')->user()->checkPermission(new \App\Permission\RootPermission()))
        $notifications[] = [
          '_id' => 'debugMode',
          'icon' => 'fal fa-exclamation-triangle',
          'text' => 'Debug mode is enabled. Disable it by setting APP_DEBUG in .env file to false.'
        ];

      if (auth('sanctum')->user()->checkPermission(new \App\Permission\DashboardPermission()) && file_exists(storage_path('/framework/down')))
        $notifications[] = [
          '_id' => 'maintenance',
          'icon' => 'fal fa-exclamation-triangle',
          'text' => 'Maintenance mode is enabled.'
        ];
    }

    return APIResponse::success($notifications);
  });
  Route::post('/popularGames', function() {
    if(Cache::has('game:popularGamesList')) return APIResponse::success(Cache::get('game:popularGamesList'));

    if(!Cache::has('game:list')) return [];
    $list = Cache::get('game:list');
    $games = [];

    foreach (\App\Models\GameStats::orderBy('launches', 'desc')->limit(50)->get() as $item) {
      foreach ($list as $game) {
        if($game['id'] === $item->game) {
          $game['popularity'] = $item->launches;

          $games[] = $game;
        }
      }
    }

    Cache::put('game:popularGamesList', $games, now()->addMinutes(5));
    return APIResponse::success($games);
  });
  Route::post('/games', function () {
    if (Cache::has('game:list')) {
      $games = Cache::get('game:list');
      $g = [];

      foreach ($games as $game) {
        $stats = \App\Models\GameStats::where('game', $game['id'])->first();
        $game['popularity'] = $stats == null ? 0 : $stats->launches;
        $g[] = $game;
      }

      return APIResponse::success($g);
    }

    $games = [];

    foreach (Game::list() as $game) {
      $houseEdgeModule = new HouseEdgeModule($game, null, null, null);

      if ($game->metadata()->releasedAt() !== '-' && Carbon::parse($game->metadata()->releasedAt())->getTimestamp() > Carbon::now()->getTimestamp()) continue;

      $games[] = [
        'type' => $game instanceof ThirdPartyGame ? $game->provider() : 'Originals (Classic)',
        'isDisabled' => $game->isDisabled(),
        'isHidden' => $game->isHidden(),
        'isPlaceholder' => $game->metadata()->isPlaceholder(),
        'name' => $game->metadata()->name(),
        'id' => $game->metadata()->id(),
        'icon' => $game->metadata()->icon(),
        'category' => $game->metadata()->category(),
        'releasedAt' => $game->metadata()->releasedAt(),
        'isMobile' => $game->metadata()->isMobile(),
        'image' => $game->metadata()->image(),
        'houseEdge' => !\App\Models\Modules::get($game, false)->isEnabled($houseEdgeModule) ? null : floatval(\App\Models\Modules::get($game, false)->get($houseEdgeModule, 'house_edge_option'))
      ];
    }

    Cache::put('game:list', $games, Carbon::now()->addDays(1));
    return APIResponse::success($games);
  });
  Route::post('/currencies', function () {
    return APIResponse::success(Currency::toCurrencyArray(Currency::all()));
  });
});

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
  Route::post('exchange', function (Request $request) {
    if (!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->reset2FAOneTimeToken();

    $currencyFrom = Currency::find($request->from);
    $currencyTo = Currency::find($request->to);

    if (!$currencyFrom || !$currencyTo) return APIResponse::reject(1, 'Invalid currency');

    if ($currencyFrom->isToken() || $currencyTo->isToken()) return APIResponse::reject(3, 'Tokens are not supported');

    $amount = floatval($request->amount);

    if ($amount < 0.00000001) return APIResponse::reject(2, 'Invalid amount');

    if (auth()->user()->balance($currencyFrom)->get() < $amount) return APIResponse::reject(2, 'Invalid amount');

    auth()->user()->balance($currencyFrom)->subtract($amount, Transaction::builder()->message('Exchange')->get());
    auth()->user()->balance($currencyTo)->add($currencyTo->convertUSDToToken($currencyFrom->convertTokenToUSD($amount)), Transaction::builder()->message('Exchange')->get());

    return APIResponse::success();
  });
  Route::post('deposit', function (Request $request) {
    $minimal = floatval(Currency::find('local_rub')->option('min_deposit'));
    if (auth()->user()->vipLevel() >= 4) $minimal /= 2;

    if (floatval($request->sum) < $minimal) return APIResponse::reject(1, 'Invalid deposit value');
    $aggregator = Aggregator::find($request->aggregator);
    if ($aggregator == null) return APIResponse::reject(2, 'Invalid aggregator');

    $invoice = \App\Models\Invoice::create([
      'method' => $request->type,
      'sum' => floatval($request->sum),
      'user' => auth()->user()->_id,
      'aggregator' => $aggregator->id(),
      'currency' => 'local_rub',
      'status' => 0
    ]);

    return APIResponse::success([
      'url' => $aggregator->invoice($invoice)
    ]);
  });
  Route::prefix('history')->group(function () {
    Route::post('deposits', function (Request $request) {
      return APIResponse::success(\App\Models\Invoice::where('user', auth()->user()->_id)->latest()->get()->toArray());
    });
    Route::post('withdraws', function (Request $request) {
      return APIResponse::success(\App\Models\Withdraw::where('user', auth()->user()->_id)->latest()->get()->toArray());
    });
  });

  Route::post('getDepositWallet', function (Request $request) {
    $currency = Currency::find($request->currency);
    $wallet = auth('sanctum')->user()->depositWallet($currency, $request->isFeeWallet ?? false);
    if ($currency == null || !$currency->isRunning() || $wallet === 'Error') return APIResponse::reject(1);
    return APIResponse::success([
      'currency' => $request->currency,
      'wallet' => $wallet
    ]);
  });
  Route::post('withdraw', function(Request $request) {
    if(!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->reset2FAOneTimeToken();

    $currency = Currency::find($request->currency);

    $vip = (new \App\VIP\VIP())->level(auth('sanctum')->user()->vipLevel());
    $fee = ($vip->withdrawFee / 100) * $request->sum;

    $sumUSD = $currency->convertTokenToUSD($request->sum);

    if($sumUSD < floatval($currency->option('minWithdraw')) || $sumUSD > $vip->maxWithdrawal) return APIResponse::reject(1, "Invalid withdraw value");
    if(auth('sanctum')->user()->balance($currency)->get() < $request->sum) return APIResponse::reject(2, 'Not enough balance');
    if(\App\Models\Withdraw::where('user', auth('sanctum')->user()->_id)->where('status', 0)->count() > 0) return APIResponse::reject(3, 'Moderation is still in process');

    if (auth('sanctum')->user()->withdraw_limit_reset == null || auth('sanctum')->user()->withdraw_limit_reset->isPast()) {
      auth('sanctum')->user()->update([
        'withdraw_limit_reset' => Carbon::now()->addHours(24)->format('Y-m-d H:i:s'),
        'withdraw_limit' => 0
      ]);
    }

    if (auth('sanctum')->user()->withdraw_limit != null && auth('sanctum')->user()->withdraw_limit >= (new \App\VIP\VIP())->level(auth('sanctum')->user()->vipLevel())->numberOfWithdrawals) return APIResponse::reject(5, 'Withdraw timeout');
    auth('sanctum')->user()->update([
      'withdraw_limit' => auth('sanctum')->user()->promocode_limit == null ? 1 : auth('sanctum')->user()->withdraw_limit + 1
    ]);

    auth('sanctum')->user()->balance($currency)->subtract($request->sum, \App\Models\Transaction::builder()->message('Withdraw')->get());

    \App\Models\Withdraw::create([
      'user' => auth('sanctum')->user()->_id,
      'sum' => $request->sum - $fee,
      'sum_original' => $request->sum,
      'currency' => $currency->id(),
      'address' => $request->wallet,
      'status' => 0,
      'type' => $request->type,
      'usd_converted' => $currency->convertTokenToFiat($request->sum - $fee)
    ]);

    return APIResponse::success();
  });
  Route::post('cancel_withdraw', function(Request $request) {
    $withdraw = \App\Withdraw::where('_id', $request->id)->where('user', auth('sanctum')->user()->_id)->where('status', 0)->first();
    if($withdraw == null) return APIResponse::reject(1, 'Hacking attempt');
    if($withdraw->auto) return APIResponse::reject(2, 'Auto-withdrawals cannot be cancelled');
    $withdraw->update([
      'status' => 4
    ]);
    auth('sanctum')->user()->balance(Currency::find($withdraw->currency))->add($withdraw->sum_original, \App\Transaction::builder()->message('Withdraw cancellation')->get());
    return APIResponse::success();
  });
});

Route::middleware('auth:sanctum')->prefix('subscription')->group(function () {
  Route::post('update', function (Request $request) {
    $request->validate([
      'endpoint' => 'required'
    ]);

    auth('sanctum')->user()->updatePushSubscription(
      $request->endpoint,
      $request->publicKey,
      $request->authToken,
      $request->contentEncoding
    );

    if (auth('sanctum')->user()->notification_bonus != true) {
      auth('sanctum')->user()->update([
        'notification_bonus' => true
      ]);
      auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->add(floatval(auth('sanctum')->user()->clientCurrency()->option('referral_bonus')), App\Models\Transaction::builder()->message('Referral bonus')->get());
    }
    return APIResponse::success();
  });
});

Route::prefix('user')->group(function () {
  Route::get('sportGames/{id}/{page}/{type}', function ($id, $page, $type) {
    $p = [];
    foreach (\App\Models\SportBet::latest()->where('user', $id)->where('status', $type === 'ongoing' ? '=' : '!=', 'ongoing')->skip(intval($page) * 15)->take(15)->get() as $game) {
      if($game->multiBetId == null) $p['single|'.$game->_id] = [ $game->toArray() ];
      else {
        if(isset($p['multi|'.$game->multiBetId])) $p['multi|'.$game->multiBetId][] = $game->toArray();
        else $p['multi|'.$game->multiBetId] = [ $game->toArray() ];
      }
    }
    return APIResponse::success($p);
  });
  Route::get('games/{id}/{page}', function ($id, $page) {
    $p = [];
    foreach (\App\Models\Game::orderBy('id', 'desc')->where('demo', '!=', true)->where('user', $id)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->skip(intval($page) * 15)->take(15)->get() as $game) {
      $engineGame = Game::find($game->game);
      if ($engineGame == null) continue;
      $p[] = [
        'game' => $game->toArray(),
        'metadata' => $engineGame->metadata()->toArray()
      ];
    }
    return APIResponse::success($p);
  });
});

Route::middleware('auth:sanctum')->prefix('game')->group(function () {
  Route::post('find', function (Request $request) {
    $game = \App\Models\Game::where('id', intval($request->id))->first();
    if ($game == null) return APIResponse::reject(1, 'Unknown ID ' . $request->id);
    return APIResponse::success([
      'id' => $game->_id,
      'game' => $game->game
    ]);
  });
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
  Route::post('supportChats', function() {
    $array = \App\Models\SupportChat::where('user', auth('sanctum')->user()->_id)->get()->toArray();
    usort($array, function($a, $b) {
      $latest = function($array) {
        return $array['messages'][count($array['messages']) - 1]['created_at'];
      };
      return $latest($b) > $latest($a) ? 1 : -1;
    });
    return APIResponse::success($array);
  });
  Route::post('supportChat', function(Request $request) {
    $chat = \App\Models\SupportChat::where('_id', $request->id)->first();
    if(auth('sanctum')->guest() || ($chat->user !== auth('sanctum')->user()->_id && !auth('sanctum')->user()->checkPermission(new \App\Permission\ManageTicketsPermission()))) return APIResponse::reject(1, 'Access denied');

    $chat->update([
      !auth('sanctum')->user()->checkPermission(new \App\Permission\ManageTicketsPermission()) ? 'user_read' : 'support_read' => count($chat->messages) == 0 ? -1 : $chat->toArray()['messages'][count($chat->toArray()['messages']) - 1]['created_at']
    ]);
    return APIResponse::success($chat->toArray());
  });
  Route::post('affiliates', function () {
    $result = [];
    foreach (\App\Models\User::where('referral', auth('sanctum')->user()->id)->get() as $user) {
      $percent = ($user->games() / floatval(\App\Models\Settings::get('referrer_activity_requirement', 100))) * 100;
      if ($percent > 100) $percent = 100;
      $percent = number_format($percent, 2, '.', '');

      $result[] = [
        'user' => $user->toArray(),
        'percent' => $percent,
        'done' => in_array($user->_id, auth('sanctum')->user()->referral_wager_obtained ?? [])
      ];
    }
    return APIResponse::success([
      'affiliates' => $result,
      'total' => \App\Models\User::where('referral', auth('sanctum')->user()->_id)->count(),
      'bonus' => count(auth('sanctum')->user()->referral_wager_obtained ?? [])
    ]);
  });
  Route::post('find', function (Request $request) {
    $user = User::where('name', 'like', "%{$request->name}%")->first();
    if ($user == null) return APIResponse::reject(1, 'Unknown username');
    return APIResponse::success(['id' => $user->_id]);
  });
  Route::post('ignore', function (Request $request) {
    $user = User::where('name', 'like', "%{$request->name}%")->first();
    if ($user == null || $user->_id === auth('sanctum')->user()->_id) return APIResponse::reject(1, 'Unknown username');

    $ignore = auth('sanctum')->user()->ignore ?? [];
    if (in_array($user->_id, $ignore)) return APIResponse::reject(2, 'Already ignored');

    array_push($ignore, $user->_id);
    auth('sanctum')->user()->update(['ignore' => $ignore]);
    return APIResponse::success(['id' => $user->_id]);
  });
  Route::post('unignore', function (Request $request) {
    $user = User::where('name', 'like', "%{$request->name}%")->first();
    if ($user == null) return APIResponse::reject(1, 'Unknown username');

    $ignore = auth('sanctum')->user()->ignore ?? [];
    if (!in_array($user->_id, $ignore)) return APIResponse::reject(2, 'User is not ignored');

    $index = array_search($user->_id, $ignore);
    unset($ignore[$index]);
    auth('sanctum')->user()->update(['ignore' => $ignore]);
    return APIResponse::success(['id' => $user->_id]);
  });
  Route::post('changePassword', function (Request $request) {
    $request->validate([
      'new' => ['required', 'string', 'min:8']
    ]);

    if (!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->reset2FAOneTimeToken();

    if (!Hash::check($request->old, auth('sanctum')->user()->password)) return APIResponse::reject(1, 'Invalid old password');

    auth('sanctum')->user()->update(['password' => Hash::make($request->new)]);
    return APIResponse::success();
  });
  Route::post('updateEmail', function (Request $request) {
    if (filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) return APIResponse::reject(1, 'Invalid email');

    if (!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->reset2FAOneTimeToken();

    auth('sanctum')->user()->update(['email' => $request->email]);
    return APIResponse::success();
  });
  Route::post('client_seed_change', function (Request $request) {
    $request->validate([
      'client_seed' => ['required', 'string', 'min:1']
    ]);

    auth('sanctum')->user()->update([
      'client_seed' => $request->client_seed
    ]);
    return APIResponse::success();
  });
  Route::post('name_change', function (Request $request) {
    $request->validate([
      'name' => ['required', 'unique:users', 'string', 'max:12', 'regex:/^\S*$/u']
    ]);

    if (!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->reset2FAOneTimeToken();

    $history = auth('sanctum')->user()->name_history;
    array_push($history, [
      'time' => Carbon::now(),
      'name' => $request->name
    ]);

    auth('sanctum')->user()->update([
      'name' => $request->name,
      'name_history' => $history
    ]);
    return APIResponse::success();
  });
  Route::post('2fa_validate', function () {
    $client = auth('sanctum')->user()->tfaInstance();
    if (request('code') == null || $client->verifyCode(auth('sanctum')->user()->tfa, request('code')) !== true) return APIResponse::reject(2, 'Invalid 2fa code');

    auth('sanctum')->user()->update([
      'tfa_onetime_key' => now()->addSeconds(15),
      'tfa_persistent_key' => now()->addDays(1)
    ]);

    return APIResponse::success();
  });
  Route::post('2fa_enable', function () {
    $client = auth('sanctum')->user()->tfaInstance();

    if (request('2faucode') == null || $client->verifyCode(request('2facode'), request('2faucode')) !== true) return APIResponse::reject(2, 'Invalid 2fa code');

    auth('sanctum')->user()->update([
      'tfa_enabled' => true,
      'tfa' => request('2facode')
    ]);
    return APIResponse::success();
  });
  Route::post('2fa_disable', function () {
    if (!auth('sanctum')->user()->validate2FA(false)) return APIResponse::invalid2FASession();
    auth('sanctum')->user()->update([
      'tfa_enabled' => false,
      'tfa' => null
    ]);
    auth('sanctum')->user()->reset2FAOneTimeToken();
    return APIResponse::success();
  });
});

Route::post('leaderboard', function (Request $request) {
  return APIResponse::success(\App\Models\Leaderboard::getLeaderboard($request->type));
});

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
  Route::post('mark', function (Request $request) {
    auth('sanctum')->user()->notifications()->where('id', $request->id)->first()->markAsRead();
    return APIResponse::success();
  });
  Route::post('unread', function () {
    return APIResponse::success([
      'notifications' => auth('sanctum')->user()->notifications()->get()->toArray()
    ]);
  });
});

Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
  Route::get('privacy_toggle', function () {
    auth('sanctum')->user()->update([
      'private_profile' => auth('sanctum')->user()->private_profile ? false : true
    ]);
    return APIResponse::success();
  });
  Route::get('privacy_bets_toggle', function () {
    auth('sanctum')->user()->update([
      'private_bets' => auth('sanctum')->user()->private_bets ? false : true
    ]);
    return APIResponse::success();
  });
  Route::post('avatar', function (Request $request) {
    $request->validate([
      'image' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048'
    ]);

    $path = auth('sanctum')->user()->_id . time();
    $request->image->move(public_path('img/avatars'), $path . '.' . $request->image->getClientOriginalExtension());

    $img = Image::make(public_path('img/avatars/' . $path . '.' . $request->image->getClientOriginalExtension()));
    $img->resize(100, 100);
    $img->encode('jpg', 75);
    $img->save(public_path('img/avatars/' . $path . '.jpg'), 75, 'jpg');

    auth('sanctum')->user()->update([
      'avatar' => '/img/avatars/' . $path . '.jpg'
    ]);
    return APIResponse::success();
  });
});

Route::post('chat/history', function (Request $request) {
  $history = Chat::latest()->limit(35)->where('channel', $request->channel)->orWhere('channel', 'all')->where('deleted', '!=', true)->get()->toArray();

  if (Settings::get('quiz_active') === 'true')
    array_unshift($history, [
      "data" => [
        "question" => Settings::get('quiz_question'),
      ],
      "type" => "quiz"
    ]);
  return APIResponse::success($history);
});

Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
  Route::middleware('moderator')->prefix('moderate')->group(function () {
    Route::post('support', function() {
      if(!auth('sanctum')->user()->checkPermission(new \App\Permission\ManageTicketsPermission()))
        return APIResponse::reject(1, 'Access denied');

      $array = \App\Models\SupportChat::get()->toArray();
      usort($array, function($a, $b) {
        $latest = function($array) {
          return $array['messages'][count($array['messages']) - 1]['created_at'];
        };
        return $latest($b) > $latest($a) ? 1 : -1;
      });
      return APIResponse::success($array);
    });
    Route::post('/unmute', function (Request $request) {
      $user = User::where('name', $request->name)->first();
      if ($user == null) return APIResponse::reject(1, 'Unknown username');

      $user->update([
        'mute' => Carbon::now()
      ]);

      (new ChatUnmuteLog())->insert(['id' => $user->_id]);
      return APIResponse::success();
    });
    Route::post('/quiz', function (Request $request) {
      Settings::set('quiz_question', $request->question);
      Settings::set('quiz_answer', $request->answer);
      Settings::set('quiz_active', 'true');
      Settings::set('quiz_created_by', auth('sanctum')->user()->name);

      event(new NewQuiz($request->question));
      return APIResponse::success();
    });
    Route::post('/removeAllFrom', function (Request $request) {
      $messages = Chat::where('user', 'like', "%{$request->id}%")->get();
      Chat::where('user', 'like', "%{$request->id}%")->update([
        'deleted' => true
      ]);

      $ids = [];
      foreach ($messages as $message) array_push($ids, $message->_id);
      event(new \App\Events\ChatRemoveMessages($ids));
      (new ChatClearLog())->insert(['type' => 'all', 'id' => $message->user['_id']]);
      return APIResponse::success($ids);
    });
    Route::post('/removeMessage', function (Request $request) {
      $message = Chat::where('_id', $request->id)->first();
      $message->update([
        'deleted' => true
      ]);
      event(new \App\Events\ChatRemoveMessages([$request->id]));
      (new ChatClearLog())->insert(['type' => 'one', 'id' => $message->user['_id']]);
      return APIResponse::success();
    });
    Route::post('/mute', function (Request $request) {
      \App\Models\User::where('_id', $request->id)->update([
        'mute' => Carbon::now()->addMinutes($request->minutes)->format('Y-m-d H:i:s')
      ]);
      (new \App\ActivityLog\MuteLog())->insert(['id' => $request->id, 'minutes' => $request->minutes]);
      return APIResponse::success();
    });
  });
  Route::post('sendSticker', function(Request $request) {
    $user = auth('sanctum')->user();
    if($user->mute != null && !$user->mute->isPast()) return ['code' => 2, 'message' => 'User is banned'];

    $message = \App\Models\Chat::create([
      'user' => $user->toArray(),
      'vipLevel' => $user->vipLevel(),
      'data' => $request->url,
      'type' => 'gif',
      'channel' => $user->channel
    ]);

    event(new \App\Events\ChatMessage($message));
    return APIResponse::success();
  });
  Route::post('send', function(Request $request) {
    if(strlen($request->message) < 1 || strlen($request->message) > 10000) return ['code' => 1, 'message' => 'Message is too short or long'];
    $user = auth('sanctum')->user();
    if($user->mute != null && !$user->mute->isPast()) return ['code' => 2, 'message' => 'User is banned'];

    //if(Chat::where('user_id', $user->_id)->where('created_at', '>=', now()->subSeconds(5))->first() != null) return [];

    $message = \App\Models\Chat::create([
      'user' => $user->toArray(),
      'user_id' => $user->_id,
      'vipLevel' => $user->vipLevel(),
      'data' => mb_substr($request->message, 0, 400),
      'type' => 'message',
      'channel' => $request->channel
    ]);

    event(new \App\Events\ChatMessage($message));

    if(\App\Models\Settings::get('quiz_active') === 'true') {
      $sanitize = function ($input) {
        return mb_strtolower(preg_replace("/[^A-Za-zА-Яа-я0-9\-]/u", '', $input));
      };

      if($sanitize($request->message) === $sanitize(\App\Models\Settings::get('quiz_answer'))) {
        Settings::set('quiz_active', false);
        $user->balance($user->clientCurrency())->add(floatval($user->clientCurrency()->option('quiz')), App\Models\Transaction::builder()->message('Quiz')->get());
        event(new \App\Events\QuizAnswered($user, \App\Models\Settings::get('quiz_question'), \App\Models\Settings::get('quiz_answer')));
      }
    }

    return APIResponse::success();
  });
  Route::post('tip', function (Request $request) {
    return APIResponse::reject(3, 'Disabled temporarily');

    $user = User::where('name', 'like', str_replace('.', '', $request->user) . '%')->first();
    if ($user == null || $user->name === auth('sanctum')->user()->name) return APIResponse::reject(1);
    if (floatval($request->amount) < floatval(auth('sanctum')->user()->clientCurrency()->option('quiz')) || auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->get() < floatval($request->amount)) return APIResponse::reject(2);
    auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->subtract(floatval($request->amount), App\Models\Transaction::builder()->message('Tip to ' . $user->_id)->get());
    $user->balance(auth('sanctum')->user()->clientCurrency())->add(floatval($request->amount), App\Models\Transaction::builder()->message('Tip from ' . auth('sanctum')->user()->_id)->get());
    $user->notify(new \App\Notifications\TipNotification(auth('sanctum')->user(), auth('sanctum')->user()->clientCurrency(), number_format(floatval($request->amount), 8, '.', '')));
    if (filter_var($request->public, FILTER_VALIDATE_BOOLEAN)) {
      $message = Chat::create([
        'data' => [
          'to' => $user->toArray(),
          'from' => auth('sanctum')->user()->toArray(),
          'amount' => number_format(floatval($request->amount), 8, '.', ''),
          'currency' => auth('sanctum')->user()->clientCurrency()->id()
        ],
        'type' => 'tip',
        'vipLevel' => auth('sanctum')->user()->vipLevel(),
        'channel' => $request->channel
      ]);

      event(new ChatMessage($message));
    }
    return APIResponse::success();
  });
  Route::post('rain', function (Request $request) {
    $usersLength = intval($request->users);
    if ($usersLength < 5 || $usersLength > 25) return APIResponse::reject(1, 'Invalid users length');
    if (auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->get() < floatval($request->amount) || floatval($request->amount) < floatval(auth('sanctum')->user()->clientCurrency()->option('rain')) / 3) return APIResponse::reject(2);
    auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->subtract(floatval($request->amount), App\Models\Transaction::builder()->message('Rain')->get());

    $all = \App\ActivityLog\ActivityLogEntry::onlineUsers()->toArray();
    if (count($all) < $usersLength) {
      $a = User::get()->toArray();
      shuffle($a);
      $all += $a;
    }

    shuffle($all);

    $dub = [];
    $users = [];
    foreach ($all as $user) {
      $user = User::where('_id', $user['_id'])->first();
      if ($user['_id'] == auth('sanctum')->user()->_id || $user == null || in_array($user['_id'], $dub)) continue;
      array_push($dub, $user['_id']);
      array_push($users, $user);
    }

    $users = array_slice($users, 0, $usersLength);
    $result = [];

    foreach ($users as $user) {
      $user->balance(auth('sanctum')->user()->clientCurrency())->add(floatval($request->amount) / $usersLength, App\Models\Transaction::builder()->message('Rain')->get());
      array_push($result, $user->toArray());
    }

    $message = Chat::create([
      'data' => [
        'users' => $result,
        'reward' => floatval($request->amount) / $usersLength,
        'currency' => auth('sanctum')->user()->clientCurrency()->id(),
        'from' => auth('sanctum')->user()->toArray()
      ],
      'type' => 'rain',
      'vipLevel' => auth('sanctum')->user()->vipLevel(),
      'channel' => $request->channel
    ]);

    event(new ChatMessage($message));
    return APIResponse::success();
  });
  Route::post('link_game', function (Request $request) {
    if (auth('sanctum')->user()->mute != null && !auth('sanctum')->user()->mute->isPast()) return APIResponse::reject(2, 'Banned');

    $game = \App\Models\Game::where('_id', $request->id)->first();
    if ($game == null) return APIResponse::reject(1, 'Invalid game id');
    if ($game->status === 'in-progress' || $game->status === 'cancelled') return APIResponse::reject(2, 'Tried to link unfinished extended game');

    $message = Chat::create([
      'user' => auth('sanctum')->user()->toArray(),
      'vipLevel' => auth('sanctum')->user()->vipLevel(),
      'data' => array_merge($game->toArray(), ['icon' => Game::find($game->game)->metadata()->icon()]),
      'type' => 'game_link',
      'channel' => $request->channel
    ]);

    event(new \App\Events\ChatMessage($message));
    return APIResponse::success([]);
  });
});

Route::post('/profile/getUser', function(Request $request) {
  $user = User::where('_id', $request->id)->first();
  if(!$user) return APIResponse::reject(1, 'Unknown user');

  $isOwner = !auth('sanctum')->guest() && auth('sanctum')->user()->_id === $user->_id;
  if($isOwner) {
    $tfa = $user->tfaInstance();
    $secret = $tfa->createSecret(160);
  }

  return APIResponse::success(array_merge([
    'user' => $user->toArray(),
    'vipLevel' => $user->vipLevel(),
    'wagered' => $user->wagered(),
    'games' => $user->games()
  ], $isOwner ? [
    'isOwner' => true,
    'secret' => $secret,
    'qr' => $tfa->getQRText('betdino.io', $secret)
  ] : []));
});

Route::middleware('auth:sanctum')->prefix('promocode')->group(function () {
  Route::post('verifyVkGroup', function () {
    $arr = json_decode(file_get_contents("https://api.vk.com/method/groups.isMember?access_token=" . Settings::get('vk_group_access_token') . "&group_id=" . Settings::get('vk_group_id') . "&user_id="
      . auth('sanctum')->user()->vk . "&v=5.103"), true);
    $subscribed = isset($arr['error']) ? false : $arr['response'] === 1;
    return APIResponse::success([
      'subscribed' => $subscribed
    ]);
  });
  Route::post('activate', function (Request $request) {
    $promocode = \App\Models\Promocode::where('code', $request->code)->first();
    if ($promocode == null) return APIResponse::reject(1, 'Invalid promocode');
    if ($promocode->expires->timestamp != Carbon::minValue()->timestamp && $promocode->expires->isPast()) return APIResponse::reject(2, 'Expired (time)');
    if ($promocode->usages != -1 && $promocode->times_used >= $promocode->usages) return APIResponse::reject(3, 'Expired (usages)');
    if (($promocode->vip ?? false) && auth('sanctum')->user()->vipLevel() == 0) return APIResponse::reject(7, 'VIP only');
    if (in_array(auth('sanctum')->user()->_id, $promocode->used)) return APIResponse::reject(4, 'Already activated');
    //if(auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->get() > floatval(auth('sanctum')->user()->clientCurrency()->option('withdraw')) / 2) return APIResponse::reject(8, 'Invalid balance');

    if (auth('sanctum')->user()->vipLevel() < 3 || ($promocode->vip ?? false) == false) {
      if (auth('sanctum')->user()->promocode_limit_reset == null || auth('sanctum')->user()->promocode_limit_reset->isPast()) {
        auth('sanctum')->user()->update([
          'promocode_limit_reset' => Carbon::now()->addHours(auth('sanctum')->user()->vipLevel() >= 5 ? 6 : 12)->format('Y-m-d H:i:s'),
          'promocode_limit' => 0
        ]);
      }

      if (auth('sanctum')->user()->promocode_limit != null && auth('sanctum')->user()->promocode_limit >= (auth('sanctum')->user()->vipLevel() >= 2 ? 8 : 5)) return APIResponse::reject(5, 'Promocode timeout');
    }

    if (auth('sanctum')->user()->vipLevel() < 3 || ($promocode->vip ?? false) == false) {
      auth('sanctum')->user()->update([
        'promocode_limit' => auth('sanctum')->user()->promocode_limit == null ? 1 : auth('sanctum')->user()->promocode_limit + 1
      ]);
    }

    $used = $promocode->used;
    array_push($used, auth('sanctum')->user()->_id);

    $promocode->update([
      'times_used' => $promocode->times_used + 1,
      'used' => $used
    ]);

    auth('sanctum')->user()->balance(Currency::find($promocode->currency))->add($promocode->sum, App\Models\Transaction::builder()->message('Promocode')->get());
    return APIResponse::success();
  });

  Route::post('demo', function () {
    if (auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->demo()->get() > auth('sanctum')->user()->clientCurrency()->minBet()) return APIResponse::reject(1, 'Demo balance is higher than zero');
    auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->demo()->add(auth('sanctum')->user()->clientCurrency()->option('demo'), App\Models\Transaction::builder()->message('Demo')->get());
    return APIResponse::success();
  });

  Route::post('bonus', function () {
    if (auth('sanctum')->user()->bonus_claim != null && !auth('sanctum')->user()->bonus_claim->isPast()) return APIResponse::reject(1, 'Timeout');
    if (auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->get() > auth('sanctum')->user()->clientCurrency()->minBet() * 2) return APIResponse::reject(2, 'Balance is greater than zero');

    $currency = auth('sanctum')->user()->clientCurrency();
    if ($currency->isToken()) return APIResponse::reject(3, 'Tokens are not supported');

    $slices = [
      [40, $currency->convertUSDToToken(0.30)],
      [30, $currency->convertUSDToToken(0.40)],
      [21, $currency->convertUSDToToken(0.50)],
      [15, $currency->convertUSDToToken(0.80)],
      [15, $currency->convertUSDToToken(1)],
      [7, $currency->convertUSDToToken(2)],
      [0.80, $currency->convertUSDToToken(3)],
      [0.50, $currency->convertUSDToToken(5)],
      [0.35, $currency->convertUSDToToken(7)],
      [0.20, $currency->convertUSDToToken(10)],
      [0.05, $currency->convertUSDToToken(50)]
    ];

    $slice = 0;

    foreach ($slices as $index => $bonusData) {
      if (mt_rand(1, 101) / 100 < $bonusData[0] / 100) {
        $slice = $index;
        break;
      }
    }

    auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->add($slices[$slice][1], App\Models\Transaction::builder()->message('Faucet')->get(), 3000);
    auth('sanctum')->user()->update(['bonus_claim' => Carbon::now()->addMinutes(20)]);

    return APIResponse::success([
      'slice' => $slice,
      'next' => Carbon::now()->addHours(10)->timestamp
    ]);
  });

  Route::post('vipBonus', function () {
    if (auth('sanctum')->user()->vipLevel() == 0) return APIResponse::reject(1, 'Invalid VIP level');
    if (auth('sanctum')->user()->weekly_bonus < 0.00000001) return APIResponse::reject(2, 'Weekly bonus is too small');
    if (auth('sanctum')->user()->weekly_bonus_obtained) return APIResponse::reject(3, 'Already obtained in this week');
    auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->add(((auth('sanctum')->user()->weekly_bonus ?? 0) / 100) * auth('sanctum')->user()->vipBonus(), App\Models\Transaction::builder()->message('Weekly VIP bonus')->get());
    auth('sanctum')->user()->update([
      'weekly_bonus_obtained' => true
    ]);
    return APIResponse::success();
  });
});

Route::post('/slotegratorCallback', function (Request $request) {
  return ThirdPartyGame::findProvider('external:sg')->processCallback($request->all(), $request->all()['action'],
    $request->header('X-Merchant-Id') ?? '', $request->header('X-Timestamp') ?? '', $request->header('X-Nonce') ?? '', $request->header('X-Sign') ?? '');
});

Route::post('/softswissCallback/{uType?}', function ($uType, Request $request) {
  $type = '';
  $data = $request->json()->all();

  if ($uType === 'play') {
    if (isset($data['user_id']) && isset($data['currency']) && isset($data['game']) && isset($data['game_id']) && isset($data['actions']))
      $type = 'play';
    else if (isset($data['user_id']) && isset($data['currency']) && isset($data['game']))
      $type = 'balance';
  } else $type = $uType;

  // Log::info(json_encode($data) . " " . $type . " " . $uType);

  $response = ThirdPartyGame::findProvider('external:ss')->processCallback($data, $type);

  //Log::info("RESPONSE: " . json_encode($response));

  return $response;
});

Route::post('/phoenixCallback', function (Request $request) {
  return (new \App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame())->processCallback($request);
});

Route::post('/serverStats', function () {
  if (Cache::has('serverStats')) return Cache::get('serverStats');

  $stats = [
    'allTimeBets' => DB::table('games')->count(),
    'registered' => DB::table('users')->count(),
    'playingLive' => count(\App\ActivityLog\ActivityLogEntry::onlineUsers())
  ];

  Cache::put('serverStats', $stats, Carbon::now()->addMinutes(1));
  return APIResponse::success($stats);
});

Route::get('/version', function () {
  $packageJson = json_decode(file_get_contents(base_path('@web/package.json')), true);

  return APIResponse::success([
    'version' => [
      'phoenix' => $packageJson['version'],
      'laravel' => laravel_version(),
      'php' => phpversion(),
      'vue' => str_replace("^", "", $packageJson['dependencies']['vue'])
    ]
  ]);
});

Route::post('/onlineUsers', function() {
  $users = [];
  foreach (\App\ActivityLog\ActivityLogEntry::onlineUsers() as $user) $users[] = $user->name;
  return APIResponse::success($users);
});

Route::post('banner', function() {
  return APIResponse::success([
    'enabled' => Settings::get('[Banner] Enabled', 'false') === 'true',
    'title' => Settings::get('[Banner] Title', 'Banner Title'),
    'image' => Settings::get('[Banner] Image URL', '/img/phoenix_preview.png'),
    'content' => Settings::get('[Banner] Content', "<div>This text will show for everyone after page is loaded.</div>
<div><a href=\"https://example.com\">You can insert links</a> and other HTML elements.</div>")
  ]);
});

Route::post('vip', function() {
  $result = [];

  for($i = 0; $i <= 10; $i++) {
    $level = (new \App\VIP\VIP())->level($i);

    $result[] = [
      'level' => $i,
      'name' => $level->name,
      'oneTimeBonus' => $level->oneTimeBonus,
      'withdrawFee' => $level->withdrawFee,
      'maxWithdrawal' => $level->maxWithdrawal,
      'numberOfWithdrawals' => $level->numberOfWithdrawals,
      'wagerRequirement' => $level->wagerRequirement,
      'depositRequirement' => $level->depositRequirement,
      'referralDepositFee' => $level->referralDepositFee,
      'inviteBonus' => $level->inviteBonus,
      'levelProtection' => $level->levelProtection
    ];
  }

  if(auth('sanctum')->user() != null) {
    $user = auth('sanctum')->user();

    $result[] = [
      'type' => 'data',
      'wagered' => $user->wagered(),
      'deposited' => $user->deposited()
    ];
  }

  return APIResponse::success($result);
});

Route::post('claimOneTimeVipBonus', function(Request $request) {
  $level = $request->level;
  $user = auth('sanctum')->user();

  if($user->vipLevel() < $level) return APIResponse::reject(1, 'Level is not obtained');
  if(isset($user->toArray()['vip_' . + $level . '_bonus_claimed'])) return APIResponse::reject(2, 'Already obtained');

  $user->update(['vip_' . $level . '_bonus_claimed' => true]);
  $user->balance($user->clientCurrency())->add($user->clientCurrency()->convertFiatToToken((new \App\VIP\VIP())->level($level)->oneTimeBonus), \App\Models\Transaction::builder()->message('VIP one-time bonus')->get());

  return APIResponse::success();
});

Route::post('/myIp', function() {
  return User::getIp();
});

Route::get('/version', function () {
  $packageJsonWeb = json_decode(file_get_contents(base_path('@web/package.json')), true);
  $packageJsonAdmin = json_decode(file_get_contents(base_path('@admin/package.json')), true);

  return APIResponse::success([
    'version' => [
      'phoenix' => $packageJsonWeb['version'],
      'laravel' => laravel_version(),
      'php' => phpversion(),
      'vue' => str_replace("^", "", $packageJsonWeb['devDependencies']['vue']),
      'vueAdmin' => str_replace("^", "", $packageJsonAdmin['dependencies']['vue'])
    ]
  ]);
});

Route::post('/websiteName', function() {
  return APIResponse::success([
    'name' => Settings::get('Website Name', 'Phoenix') ?? 'Phoenix'
  ]);
});
