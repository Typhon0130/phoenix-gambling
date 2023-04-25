<?php

use App\ActivityLog\ActivityLogEntry;
use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\Models\AdminActivity;
use App\Currency\Currency;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\Module;
use App\Games\Kernel\ProvablyFair;
use App\Models\SportBet;
use App\Permission\RootPermission;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Utils\APIResponse;
use App\Utils\OperatingSystem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use MongoDB\BSON\Decimal128;
use Myoutdeskllc\LaravelAnalyticsV4\Period;
use Myoutdeskllc\LaravelAnalyticsV4\PrebuiltRunConfigurations;
use Myoutdeskllc\LaravelAnalyticsV4\RunReportConfiguration;
use Symfony\Component\Process\Process;

Route::post('/info', function () {
  $get = function ($type) {
    $total = App::make(\Arcanedev\LogViewer\Contracts\LogViewer::class)->total($type);
    return $total > 999 ? 999 : $total;
  };

  return APIResponse::success([
    'withdraws' => \App\Models\Withdraw::where('status', 0)->count(),
    'version' => json_decode(file_get_contents(base_path('package.json')))->version,
    'logs' => [
      'critical' => $get('critical'),
      'error' => $get('error')
    ]
  ]);
});

Route::post('checkDuplicates', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    'user' => [],
    'same_register_hash' => [],
    'same_login_hash' => [],
    'same_register_ip' => [],
    'same_login_ip' => []
  ]);

  $user = User::where('_id', $request->id)->first();
  if ($user->bot) return APIResponse::reject(1, 'Can\'t verify bots');

  return APIResponse::success([
    'user' => $user->makeVisible('register_multiaccount_hash')->makeVisible('login_multiaccount_hash')->toArray(),
    'same_register_hash' => \App\Models\User::where('register_multiaccount_hash', $user->register_multiaccount_hash)->get()->toArray(),
    'same_login_hash' => \App\Models\User::where('login_multiaccount_hash', $user->login_multiaccount_hash)->get()->toArray(),
    'same_register_ip' => \App\Models\User::where('register_ip', $user->register_ip)->get()->toArray(),
    'same_login_ip' => \App\Models\User::where('login_ip', $user->login_ip)->get()->toArray()
  ]);
});

Route::post('users/{page}', function (string $page) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    'maxPages' => 1,
    'users' => [
      '_id' => 'example',
      'name' => 'example_user',
      'avatar' => '/avatar/example_user',
      'email' => 'user@example.com',
      'vipLevel' => 1,
      'depositedTotal' => 100,
      'withdrawnUsdTotal' => 50,
      'numberOfInvites' => 1,
      'created_at' => now()->subDay()
    ]
  ]);

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);

  $result = [];

  $page = intval($page);
  $pageSize = 20;

  $users = User::where('bot', '!=', true)->skip(($page - 1) * $pageSize)->take($pageSize)->get()->makeVisible([
    'email'
  ]);

  foreach ($users as $user) {
    $result[] = array_merge([
      'numberOfInvites' => User::where('referral', $user->_id)->count(),
      'vipLevel' => $user->vipLevel(),
      'depositedTotal' => \App\Models\Invoice::where('user', $user->_id)->where('status', 1)->sum('usd_converted'),
      'withdrawnUsdTotal' => \App\Models\Withdraw::where('user', $user->_id)->sum('usd_converted')
    ], $user->toArray());
  }

  return APIResponse::success([
    'maxPages' => ceil(DB::table('users')->where('bot', '!=', true)->count() / $pageSize),
    'users' => $result
  ]);
});

Route::post('/searchUsers', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    [
      '_id' => 'example',
      'name' => 'example_user',
      'avatar' => '/avatar/example_user',
      'email' => 'user@example.com',
      'vipLevel' => 1,
      'depositedTotal' => 100,
      'withdrawnUsdTotal' => 50,
      'numberOfInvites' => 1,
      'created_at' => now()->subDay()
    ]
  ]);

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);
  $result = [];
  $users = User::where('name', 'like', "%{$request->search}%")
    ->orWhere('email', 'like', "%{$request->search}%")->get()->makeVisible([
      'email'
    ]);

  foreach ($users as $user) {
    $result[] = array_merge([
      'numberOfInvites' => User::where('referral', $user->_id)->count(),
      'vipLevel' => $user->vipLevel(),
      'depositedTotal' => \App\Models\Invoice::where('user', $user->_id)->where('status', 1)->sum('usd_converted'),
      'withdrawnUsdTotal' => \App\Models\Withdraw::where('user', $user->_id)->sum('usd_converted')
    ], $user->toArray());
  }

  return APIResponse::success($result);
});
Route::post('transactions/{user}/{page}', function (string $user, string $page) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    [
      'demo' => false,
      'currency' => 'native_btc',
      'data' => [
        'message' => 'Example transaction'
      ],
      'new' => .5,
      'old' => 0,
      'amount' => .5,
      'created_at' => now()
    ]
  ]);

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);
  return APIResponse::success(\App\Models\Transaction::latest()->where('user', $user)->where('demo', '!=', true)->skip(intval($page) * 30)->take(30)->get()->toArray());
});

Route::post('transactionsSearch', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);
  return APIResponse::success(Transaction::latest()->where('user', $request->user)->where('data', 'like', '%' . $request->search . '%')->get()->toArray());
});

Route::post('userAdvanced', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    'games' => 120,
    'wins' => 60,
    'losses' => 60,
    'currencies' => [
      'native_btc' => [
        'games' => 120,
        'wins' => 60,
        'losses' => 60,
        'wagered' => 0.3,
        'deposited' => 0.1
      ]
    ]
  ]);

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);

  $user = User::where('_id', $request->id)->first();

  $currencies = [];
  foreach (Currency::all() as $currency) {
    $currencies = array_merge($currencies, [
      $currency->id() => [
        'games' => DB::table('games')->where('demo', '!=', true)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->where('user', $user->_id)->where('currency', $currency->id())->count(),
        'wins' => DB::table('games')->where('demo', '!=', true)->where('status', 'win')->where('user', $user->_id)->where('currency', $currency->id())->count(),
        'losses' => DB::table('games')->where('demo', '!=', true)->where('status', 'lose')->where('user', $user->_id)->where('currency', $currency->id())->count(),
        'wagered' => DB::table('games')->where('demo', '!=', true)->where('status', '!=', 'cancelled')->where('user', $user->_id)->where('currency', $currency->id())->sum('wager'),
        'deposited' => DB::table('invoices')->where('status', 1)->where('user', $user->_id)->where('currency', $currency->id())->sum('sum')
      ]
    ]);
  }

  return APIResponse::success([
    'games' => DB::table('games')->where('user', $user->_id)->where('demo', '!=', true)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->count(),
    'wins' => DB::table('games')->where('demo', '!=', true)->where('status', 'win')->where('user', $user->_id)->count(),
    'losses' => DB::table('games')->where('demo', '!=', true)->where('status', 'lose')->where('user', $user->_id)->count(),
    'currencies' => $currencies
  ]);
});

Route::post('user', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::success([
    'user' => [
      '_id' => 'example_user',
      'avatar' => '/avatar/example_user',
      'name' => 'example_user',
      'register_ip' => '127.0.0.1',
      'login_ip' => '127.0.0.1',
      'login_date' => now(),
      'name_history' => [ [ 'time' => now()->subDay(), 'name' => 'example_user' ] ],
      'roles' => [
        [ 'id' => '*' ]
      ],
      'created_at' => now()->subDay()
    ],
    'currencies' => [
      'native_btc' => [
        'balance' => 0.4
      ]
    ]
  ]);

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission())) return APIResponse::reject(1);
  $user = User::where('_id', $request->id)->first();

  $currencies = [];
  foreach (Currency::all() as $currency) {
    $currencies = array_merge($currencies, [
      $currency->id() => [
        'balance' => $user->balance($currency)->get()
      ]
    ]);
  }

  return APIResponse::success([
    'user' => $user->makeVisible($user->hidden)->toArray(),
    'currencies' => $currencies
  ]);
});

Route::prefix('wallet')->group(function () {
  Route::post('invoices', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      'maxPages' => 1,
      'invoices' => [
        [
          'data' => [
            '_id' => 'example',
            'status' => 0,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'status' => 1,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'status' => 2,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ]
      ]
    ]);

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission())) return APIResponse::reject(1);

    $page = intval($request->page);
    $pageSize = 20;

    $invoices = [];

    foreach (\App\Models\Invoice::latest()->skip(($page - 1) * $pageSize)->take($pageSize)->get() as $invoice) {
      $invoices[] = [
        'data' => $invoice->toArray(),
        'user' => User::where('_id', $invoice->user)->first()->toArray()
      ];
    }

    return [
      'maxPages' => ceil(\App\Models\Invoice::count() / $pageSize),
      'invoices' => $invoices
    ];
  });
  Route::post('withdraws', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      'maxPages' => 1,
      'withdraws' => [
        [
          'data' => [
            '_id' => 'example',
            'address' => 'Example',
            'status' => 0,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'address' => 'Example',
            'status' => 1,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'address' => 'Example',
            'status' => 2,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc',
            'decline_reason' => 'Example'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'address' => 'Example',
            'status' => 3,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ],
        [
          'data' => [
            '_id' => 'example',
            'address' => 'Example',
            'status' => 4,
            'created_at' => now(),
            'sum' => 0.1,
            'currency' => 'native_btc'
          ],
          'user' => [
            'name' => 'example_user',
            'avatar' => '/avatar/example_user'
          ]
        ]
      ]
    ]);

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission())) return APIResponse::reject(1);

    $page = intval($request->page);
    $pageSize = 20;

    $withdraws = [];

    foreach (\App\Models\Withdraw::latest()->skip(($page - 1) * $pageSize)->take($pageSize)->get() as $withdraw) {
      $withdraws[] = [
        'data' => $withdraw->toArray(),
        'user' => User::where('_id', $withdraw->user)->first()->toArray()
      ];
    }

    return [
      'maxPages' => ceil(\App\Models\Withdraw::count() / $pageSize),
      'withdraws' => $withdraws
    ];
  });
  Route::prefix('invoice')->group(function () {
    Route::post('accept', function (Request $request) {
      if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

      $invoice = \App\Models\Invoice::where('_id', $request->id)->first();
      if ($invoice->status !== 0) return APIResponse::reject(1, 'Invalid deposit status, must be 0');

      $user = User::where('_id', $invoice->user)->first();
      $currency = Currency::find($invoice->currency);
      $user->balance($currency)->add($invoice->sum, Transaction::builder()->message('Deposit manual confirmation')->get());

      $invoice->update(['status' => 1]);
      return [];
    });
    Route::post('cancel', function (Request $request) {
      if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

      $invoice = \App\Models\Invoice::where('_id', $request->id)->first();

      if ($invoice->status === 1)
        User::where('_id', $invoice->user)->first()->balance(Currency::find($invoice->currency))->subtract($invoice->sum, Transaction::builder()->message('Deposit manual cancellation')->get());

      $invoice->update(['status' => 2]);
      return [];
    });
  });
  Route::post('accept', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission(), 'edit')) return APIResponse::reject(1);

    $withdraw = \App\Models\Withdraw::where('_id', $request->id)->first();
    if ($withdraw == null || $withdraw->status != 0) return APIResponse::reject(1, 'Invalid state');

    \App\Models\User::where('_id', $withdraw->user)->first()->notify(new \App\Notifications\WithdrawAccepted($withdraw));

    $withdraw->update([
      'status' => 1
    ]);

    return APIResponse::success();
  });
  Route::post('decline', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission(), 'edit')) return APIResponse::reject(1);

    $withdraw = \App\Models\Withdraw::where('_id', request('id'))->first();
    if ($withdraw == null || $withdraw->status != 0) return APIResponse::reject(1, 'Invalid state');

    $withdraw->update([
      'decline_reason' => request('reason'),
      'status' => 2
    ]);
    \App\Models\User::where('_id', $withdraw->user)->first()->notify(new \App\Notifications\WithdrawDeclined($withdraw));
    \App\Models\User::where('_id', $withdraw->user)->first()->balance(Currency::find($withdraw->currency))->add($withdraw->sum_original, Transaction::builder()->message('Declined withdraw')->get());
    return APIResponse::success();
  });
  Route::post('ignore', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission(), 'edit')) return APIResponse::reject(1);

    $withdraw = \App\Models\Withdraw::where('_id', request('id'))->first();
    if ($withdraw == null || $withdraw->status != 0) return APIResponse::reject(1, 'Invalid state');
    $withdraw->update([
      'status' => 3
    ]);
    return APIResponse::success();
  });
  Route::post('unignore', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WithdrawsPermission(), 'edit')) return APIResponse::reject(1);

    $withdraw = \App\Models\Withdraw::where('_id', request('id'))->first();
    if ($withdraw == null || $withdraw->status != 3) return APIResponse::reject(1, 'Invalid state');
    $withdraw->update([
      'status' => 0
    ]);
    return APIResponse::success();
  });
  Route::middleware('superadmin')->get('autoSetup', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    foreach (Currency::all() as $currency) $currency->setupWallet();
    return APIResponse::success();
  });
  Route::middleware('superadmin')->post('setup', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    Currency::find($request->id)->setupWallet();
    return APIResponse::success();
  });

  Route::post('/transfer', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission())) return APIResponse::reject(1);

    try {
      $currency = Currency::find(request('currency'));
      $currency->send($currency->option('transfer_address'), request('address'), floatval(request('amount')));
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::critical($e);
      return APIResponse::reject(1);
    }
    return APIResponse::success();
  });
});

Route::post('/notifications/data', function () {
  if(\App\Utils\Demo::isDemo())
    return APIResponse::success([
      'global' => [
        [
          'icon' => 'fal fa-fw fa-exclamation-triangle',
          'text' => 'Example notification'
        ]
      ]
    ]);

  return APIResponse::success([
    'global' => \App\Models\GlobalNotification::get()->toArray()
  ]);
});


Route::prefix('notifications')->group(function () {
  Route::post('/global', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\NotificationPermission(), 'create')) return APIResponse::reject(1);

    \App\Models\GlobalNotification::create([
      'icon' => request('icon'),
      'text' => request('text')
    ]);
    (new \App\ActivityLog\GlobalNotificationLog())->insert(['state' => true, 'text' => request('text'), 'icon' => request('icon')]);
    return APIResponse::success();
  });
  Route::post('/global_remove', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\NotificationPermission(), 'delete')) return APIResponse::reject(1);

    $n = \App\Models\GlobalNotification::where('_id', request('id'));
    (new \App\ActivityLog\GlobalNotificationLog())->insert(['state' => false, 'text' => $n->first()->text, 'icon' => $n->first()->icon]);
    $n->delete();
    return APIResponse::success();
  });
});

Route::post('/ban', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission(), 'delete')) return APIResponse::reject(1);

  $user = \App\Models\User::where('_id', request('id'))->first();
  (new \App\ActivityLog\BanUnbanLog())->insert(['type' => $user->ban ? 'unban' : 'ban', 'id' => $user->_id]);
  $user->update([
    'ban' => $user->ban ? false : true
  ]);
  return APIResponse::success();
});

Route::middleware('superadmin')->post('/toggle_module', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  $game = Game::find(request('api_id'));
  $module = Module::find(request('module_id'));
  \App\Models\Modules::get($game, filter_var(request('demo'), FILTER_VALIDATE_BOOLEAN))->toggleModule($module)->save();
  return APIResponse::success();
});

Route::middleware('superadmin')->post('/option_value', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  $game = Game::find(request('api_id'));
  $module = Module::find(request('module_id'));
  \App\Models\Modules::get($game, filter_var(request('demo'), FILTER_VALIDATE_BOOLEAN))->set($module, request('option_id'), request('value') ?? '')->save();
  return APIResponse::success();
});

Route::post('/toggle', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new RootPermission())) return APIResponse::reject(-1, 'Access denied');

  Cache::forget('game:list');

  if (\App\Models\DisabledGame::where('name', request('name'))->first() == null) {
    \App\Models\DisabledGame::create(['name' => request('name')]);
    (new \App\ActivityLog\DisableGameActivity())->insert(['state' => true, 'api_id' => request('name')]);
  } else {
    \App\Models\DisabledGame::where('name', request('name'))->delete();
    (new \App\ActivityLog\DisableGameActivity())->insert(['state' => false, 'api_id' => request('name')]);
  }
  return APIResponse::success();
});

Route::post('/toggleVisibility', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new RootPermission())) return APIResponse::reject(-1, 'Access denied');

  Cache::forget('game:list');

  if (\App\Models\HiddenGame::where('name', request('name'))->first() == null) {
    \App\Models\HiddenGame::create(['name' => request('name')]);
    (new \App\ActivityLog\HideGameActivity())->insert(['state' => true, 'api_id' => request('name')]);
  } else {
    \App\Models\HiddenGame::where('name', request('name'))->delete();
    (new \App\ActivityLog\HideGameActivity())->insert(['state' => false, 'api_id' => request('name')]);
  }
  return APIResponse::success();
});

Route::post('/balance', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission(), 'edit')) return APIResponse::reject(1);

  \App\Models\User::where('_id', request('id'))->update([
    request('currency') => new Decimal128(strval(request('balance')))
  ]);

  (new \App\ActivityLog\BalanceChangeActivity())->insert(['currency' => request('currency'), 'balance' => request('balance'), 'id' => request('id')]);
  return APIResponse::success();
});

Route::post('/currencyOption', function () {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission(), 'edit')) return APIResponse::reject(1);

  Currency::find(request('currency'))->option(request('option'), request('value'));
  return APIResponse::success();
});

Route::prefix('nodes')->group(function() {
  Route::prefix('commerce')->group(function() {
    Route::post('settings', function() {
      if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
      if(!auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission())) return APIResponse::reject(1);

      return APIResponse::success([
        'commerceApiKey' => Settings::get('[Coinbase Commerce] API Key', '')
      ]);
    });
    Route::post('setApiKey', function(Request $request) {
      if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

      Settings::set('[Coinbase Commerce] API Key', $request->key);
      return APIResponse::success();
    });
  });
  Route::post('list', function () {
    if(!\App\Utils\Demo::isDemo() && !auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission())) return APIResponse::reject(1);
    $response = [];

    foreach (Currency::getAllSupportedCoins() as $currency) {
      $settings = [];

      foreach ($currency->getOptions() as $option) $settings[] = [
        'id' => $option->id(),
        'readOnly' => $option->readOnly(),
        'value' => $currency->option($option->id()),
        'description' => $option->description(),
        'name' => $option->name()
      ];

      $chains = [];
      foreach ($currency->chains() as $chain)
        $chains[] = [
          'id' => $chain->id(),
          'name' => $chain->name()
        ];

      $response[] = [
        'id' => $currency->id(),
        'name' => $currency->name(),
        'shortName' => $currency->displayName(),
        'walletId' => $currency->walletId(),
        'zeros' => $currency->decimals(),
        'enabled' => $currency->isEnabled(),
        'type' => str_starts_with($currency->id(), "local_") ? 'local' : 'coin',
        'icon' => $currency->icon(),
        'style' => $currency->style(),
        'price' => $currency->tokenPrice(),
        'isToken' => $currency->isToken(),
        'chains' => $chains,
        'wallet' => [
          'address' => $currency->option('transfer_address'),
          'balance' => $currency->coldWalletBalance(),
          'url' => $currency->url()
        ],
        'settings' => $settings
      ];
    }

    return APIResponse::success($response);
  });
});

Route::post('/toggleCurrency', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission(), 'edit')) return APIResponse::reject(1);

  $currenciesJson = json_decode(Settings::get('currencies', '["commerce_btc"]'));
  $currencies = [];

  foreach ($currenciesJson as $id) {
    $currency = Currency::find($id);
    if ($currency->id() == $request->walletId) continue;
    $currencies[] = $currency->id();
  }

  if ($request->type !== 'disabled') $currencies[] = $request->walletId;

  Settings::set('currencies', json_encode($currencies));
  return APIResponse::success();
});

Route::middleware('superadmin')->post('/activity', function () {
  if(\App\Utils\Demo::isDemo())
    return APIResponse::success([
      [
        'user' => [
          'name' => 'You',
          'avatar' => '/avatar/you'
        ],
        'time' => now()->diffForHumans(),
        'html' => 'Viewing "Activity" page'
      ]
    ]);

  $activity = [];
  foreach (AdminActivity::latest()->get()->reverse() as $log) {
    if (ActivityLogEntry::find($log->type) == null) continue;
    $user = \App\Models\User::where('_id', $log->user)->first();
    if (!$user) continue;

    $activity[] = [
      'user' => $user->toArray(),
      'entry' => $log->toArray(),
      'time' => Carbon::parse($log->time)->diffForHumans(),
      'html' => ActivityLogEntry::find($log->type)->display($log)
    ];
  }

  return APIResponse::success($activity);
});

Route::middleware('superadmin')->prefix('settings')->group(function () {
  Route::post('get', function () {
    return APIResponse::success([
      'mutable' => \App\Models\Settings::where('internal', '!=', true)->where('hidden', '!=', true)->get()->toArray(),
      'immutable' => \App\Models\Settings::where('internal', true)->where('hidden', '!=', true)->get()->toArray()
    ]);
  });
  Route::post('create', function () {
    \App\Models\Settings::create(['name' => request('key'), 'description' => request('description'), 'value' => null]);
    return APIResponse::success();
  });
  Route::post('edit', function () {
    \App\Models\Settings::where('name', request('key'))->first()->update([
      'value' => request('value') === 'null' ? null : request('value')
    ]);
    return APIResponse::success();
  });
  Route::post('remove', function () {
    \App\Models\Settings::where('name', request('key'))->delete();
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('chat_bot')->group(function () {
  Route::post('status', function () {
    return APIResponse::success([
      'status' => !(Settings::get('[Chat Bot] Stop', 'true', true) === 'true')
    ]);
  });
  Route::post('settings', function () {
    return APIResponse::success([
      [
        'name' => 'Message interval (seconds)',
        'description' => 'Messages will be sent each X seconds',
        'value' => Settings::get('Message interval (seconds)', 15, true)
      ],
      [
        'name' => 'Message interval random delay (seconds)',
        'description' => 'Messages will be sent with X seconds delay',
        'value' => Settings::get('Message interval random delay (seconds)', 15, true)
      ],
      [
        'name' => 'Chat bot channel',
        'description' => 'Chat channels where chat bot will be active',
        'type' => 'textarea',
        'value' => Settings::get('Chat bot channel', "casino_en
casino_pt-br
sport_en", true)
      ],
      [
        'name' => 'Messages',
        'description' => 'One of these messages will be randomly picked.',
        'type' => 'textarea',
        'value' => Settings::get('Messages', 'Hello
o/
hi
WOW', true)
      ]
    ]);
  });
  Route::post('start', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    Settings::toggle('[Chat Bot] Stop', 'false', 'true', 'true');
    dispatch(new \App\Jobs\Bot\Chat\ChatBotScheduler());
    return APIResponse::success();
  });
});

Route::post('bannerEdit', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\BannerPermission())) return APIResponse::reject(1);
  $key = null;
  switch ($request->editKey) {
    case 'state':
      $key = '[Banner] Enabled';
      break;
    case 'title':
      $key = '[Banner] Title';
      break;
    case 'image':
      $key = '[Banner] Image URL';
      break;
    case 'content':
      $key = '[Banner] Content';
      break;
    case 'ogTitle':
      $key = '[OpenGraph] Title';
      break;
    case 'ogImage':
      $key = '[OpenGraph] Image URL';
      break;
  }
  Settings::set($key, $request->editValue);
  return APIResponse::success();
});

Route::post('forceVip', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission(), 'edit')) return APIResponse::reject(1);
  User::where('_id', $request->id)->update([
    'forced_vip' => $request->level === -1 ? null : $request->level
  ]);
  return APIResponse::success();
});

Route::post('bannerSettings', function () {
  if (!\App\Utils\Demo::isDemo() && !auth('sanctum')->user()->checkPermission(new \App\Permission\BannerPermission())) return APIResponse::reject(1);
  return APIResponse::success([
    'enabled' => Settings::get('[Banner] Enabled', 'false', true) === 'true',
    'title' => Settings::get('[Banner] Title', 'Banner Title', true),
    'image' => Settings::get('[Banner] Image URL', '/img/phoenix_preview.png', true),
    'content' => Settings::get('[Banner] Content', "<div>This text will show for everyone after page is loaded.</div>
<div><a href=\"https://example.com\">You can insert links</a> and other HTML elements.</div>", true),
    'ogSettingsWebsiteTitle' => Settings::get('[OpenGraph] Title', 'Website Title'),
    'ogSettingsWebsiteImage' => Settings::get('[OpenGraph] Image URL', '/img/phoenix_preview.png')
  ]);
});

Route::post('editVIP', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\VIPControlPermission())) return APIResponse::reject(1);
  (new \App\VIP\VIP())->level($request->level)->set($request->key, $request->value ?? '');
  return APIResponse::success();
});

Route::middleware('superadmin')->prefix('bot')->group(function () {
  Route::post('status', function () {
    return APIResponse::success([
      'status' => !(Settings::get('[Bet Bot] Stop', 'true', true) === 'true')
    ]);
  });
  Route::post('settings', function () {
    return APIResponse::success([
      [
        'name' => 'Create new bot every X seconds',
        'description' => 'New bot will be created every X seconds',
        'value' => Settings::get('Create new bot every X seconds', 10, true)
      ],
      [
        'name' => 'Private bets probability',
        'description' => 'Private bets probability (0 - 100)',
        'value' => Settings::get('Private bets probability', 20, true)
      ],
      [
        'name' => 'Private profile probability',
        'description' => 'Private profile probability (0 - 100)',
        'value' => Settings::get('Private profile probability', 20, true)
      ],
      [
        'name' => 'Min. amount of games from one bot',
        'description' => 'Bot will play at least X games',
        'value' => Settings::get('Min. amount of games from one bot', 20, true)
      ],
      [
        'name' => 'Max. amount of games from one bot',
        'description' => 'Bot will play at least X games',
        'value' => Settings::get('Max. amount of games from one bot', 50, true)
      ],
      [
        'name' => 'Min. delay between games (seconds)',
        'description' => 'Bot will wait at least X seconds before playing the next game',
        'value' => Settings::get('Min. delay between games (seconds)', 1, true)
      ],
      [
        'name' => 'Max. delay between games (seconds)',
        'description' => 'Bot will wait a maximum of X seconds before playing the next game',
        'value' => Settings::get('Max. delay between games (seconds)', 5, true)
      ]
    ]);
  });
  Route::post('start', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    Settings::toggle('[Bet Bot] Stop', 'false', 'true', 'true');
    dispatch(new \App\Jobs\Bot\BotScheduler());
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->post('modules', function (Request $request) {
  $demo = $request->boolean('demo');
  $game = Game::find($request->game);

  $supportedModules = [];

  foreach (Module::modules() as $module) {
    $instance = new $module($game, null, null, null);

    $settings = [];
    foreach ($instance->settings() as $setting) {
      $settings[] = [
        'id' => $setting->id(),
        'name' => $setting->name(),
        'description' => $setting->description(),
        'defaultValue' => $setting->defaultValue(),
        'type' => $setting->type(),
        'value' => \App\Models\Modules::get($game, $demo)->get($instance, $setting->id())
      ];
    }

    if ($instance->supports()) $supportedModules[] = [
      'id' => $instance->id(),
      'name' => $instance->name(),
      'description' => $instance->description(),
      'supports' => $instance->supports(),

      'isEnabled' => \App\Models\Modules::get($game, $demo)->isEnabled($instance),
      'settings' => $settings
    ];
  }

  return APIResponse::success($supportedModules);
});

Route::prefix('promocode')->group(function () {
  Route::post('get', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      [
        'code' => 'TEST1',
        'used' => [],
        'currency' => 'native_btc',
        'sum' => 0.001,
        'usages' => 100,
        'times_used' => 56,
        'expires' => now()->addYear(),
        'created_at' => now()
      ]
    ]);

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\PromocodePermission())) return APIResponse::reject(1);
    return APIResponse::success(\App\Models\Promocode::get()->toArray());
  });
  Route::post('remove', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\PromocodePermission(), 'delete')) return APIResponse::reject(1);
    \App\Models\Promocode::where('_id', request()->get('id'))->delete();
    return APIResponse::success();
  });
  Route::post('remove_inactive', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\PromocodePermission(), 'delete')) return APIResponse::reject(1);
    foreach (\App\Models\Promocode::get() as $promocode) {
      if (($promocode->expires->timestamp != Carbon::minValue()->timestamp && $promocode->expires->isPast())
        || ($promocode->usages != -1 && $promocode->times_used >= $promocode->usages)) $promocode->delete();
    }
    return APIResponse::success();
  });
  Route::post('create', function () {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if (!auth('sanctum')->user()->checkPermission(new \App\Permission\PromocodePermission(), 'create')) return APIResponse::reject(1);
    request()->validate([
      'code' => 'required',
      'usages' => 'required',
      'expires' => 'required',
      'sum' => 'required',
      'currency' => 'required'
    ]);

    \App\Models\Promocode::create([
      'code' => request('code') === '%random%' ? \App\Models\Promocode::generate() : request('code'),
      'currency' => request('currency'),
      'used' => [],
      'sum' => floatval(request('sum')),
      'usages' => request('usages') === '%infinite%' ? -1 : intval(request('usages')),
      'times_used' => 0,
      'expires' => request('expires') === '%unlimited%' ? Carbon::minValue() : Carbon::createFromTimestamp(request()->get('expires'))
    ]);
    return APIResponse::success();
  });
});

Route::post('changePassword', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission(), "edit")) return APIResponse::reject(1);
  User::where('_id', $request->id)->first()->update(["password" => Hash::make($request->password)]);
  return APIResponse::success();
});

Route::post('createUser', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  $requestUser = auth('sanctum')->user();
  if (!$requestUser->checkPermission(new \App\Permission\ControlUsersPermission(), "create")) return APIResponse::reject(1);

  $request->validate([
    'email' => ['required', 'unique:users', 'email'],
    'name' => ['required', 'unique:users', 'string', 'max:64', 'regex:/^[a-zA-Z0-9]{5,64}$/u'],
    'password' => ['required', 'string', 'min:5']
  ]);

  $roles = [];

  foreach ($request->roles as $roleId) {
    $roles[] = [
      "id" => $roleId
    ];
  }

  User::create([
    'name' => $request->name,
    'password' => Hash::make($request->password),
    'avatar' => $avatar ?? '/avatar/' . uniqid(),
    'email' => $request->email,
    'client_seed' => ProvablyFair::generateServerSeed(),
    'roles' => $roles,
    'name_history' => [['time' => Carbon::now(), 'name' => $request->name]],
    'register_ip' => '(Registered by ' . $requestUser->name . ')',
    'login_ip' => '-',
    'login_date' => Carbon::now(),
    'register_multiaccount_hash' => base64_encode(random_bytes(18)),
    'login_multiaccount_hash' => base64_encode(random_bytes(18))
  ]);
  return APIResponse::success();
});

Route::prefix('roles')->group(function () {
  Route::post('all', function () {
    if (!\App\Utils\Demo::isDemo() && !auth('sanctum')->user()->checkPermission(new \App\Permission\ControlUsersPermission(), "create")) return APIResponse::reject(1);
    return APIResponse::success(\App\Models\Role::toRolesAndPermissionsArray());
  });
});

Route::middleware('superadmin')->prefix('roles')->group(function () {
  Route::post('new', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    \App\Models\Role::create([
      'id' => $request->id,
      'name' => $request->name,
      'permissions' => []
    ]);
    Cache::forget('allRoles');
    return APIResponse::success();
  });
  Route::post('remove', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    \App\Models\Role::where('id', $request->id)->delete();
    Cache::forget('allRoles');
    return APIResponse::success();
  });
  Route::post('update', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    $role = \App\Models\Role::where('id', $request->roleId);
    $permissions = $role->first()->permissions;

    $permission = \App\Permission\Permissions::findById($request->permissionId);
    $rolePermission = new \App\Permission\RolePermission();

    foreach ($permissions as $dbPermission) {
      if ($dbPermission['id'] === $permission->id()) {
        $rolePermission->from($dbPermission['permissions']);

        $permissions = array_filter($permissions, function ($a) use ($permission) {
          return $a['id'] !== $permission->id();
        });
      }
    }

    switch ($request->type) {
      case 'active':
        $rolePermission->active($request->state);
        break;
      case 'edit':
        $rolePermission->edit($request->state);
        break;
      case 'delete':
        $rolePermission->delete($request->state);
        break;
      case 'create':
        $rolePermission->create($request->state);
        break;
    }

    $permissions[] = $permission->toArray($rolePermission);

    $role->update(['permissions' => json_encode(array_values($permissions))]);
    Cache::forget('allRoles');
    return APIResponse::success();
  });
  Route::post('toggleRole', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    $user = User::where('_id', $request->userId)->first();
    $role = \App\Models\Role::where('id', $request->roleId)->first();

    if ($user->hasRole($role)) $user->deleteRole($role);
    else $user->addRole($role);
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('license')->group(function() {
  Route::post('/refresh', function() {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    Cache::forget('license:info');
    return [];
  });
  Route::post('/info', function() {
    $license = new \App\License\License();

    if(!$license->isValid()) return [ 'isValid' => false ];

    $info = $license->get();
    unset($info['key']);

    return [
      'isValid' => $license->isValid(),
      'info' => $info,
      'phoenixFeatures' => $license->getPhoenixFeatures()
    ];
  });
  Route::post('/setKey', function (Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    if($request->key === '-') {
      (new PhoenixGame())->curl("https://phoenix-gambling.com/api/license/removeFromWhitelist", [
        'key' => $request->key,
        'ip' => User::getServerIp()
      ]);
    }

    Cache::forget('license:info');
    Settings::set('[License] Key', $request->key);

    if($request->key !== '-') {
      (new PhoenixGame())->curl('https://phoenix-gambling.com/api/license/installerSetup', [
        'key' => $request->key,
        'ip' => $request->ip
      ]);
    }

    return [];
  });
});

Route::post('/notify', function() {
  if(!\App\Utils\Demo::isDemo() && !auth('sanctum')->user()->checkPermission(new \App\Permission\DashboardPermission())) return APIResponse::reject(1);

  $server = (new \App\Updater\Updater())->server();

  return APIResponse::success([
    'withdraws' => \App\Models\Withdraw::where('status', 0)->count() > 0,
    'update' => $server->isHigher($server->manifest()['latest'], $server->version())
  ]);
});

Route::middleware('superadmin')->prefix('database')->group(function() {
  $pageSize = 50;

  Route::post('saveDocument/{collection}/{id}', function($collection, $id, Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    DB::table($collection)->where('_id', $id)->update($request->object);
    return APIResponse::success();
  });
  Route::post('deleteDocument/{collection}/{id}', function($collection, $id) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    DB::table($collection)->where('_id', $id)->delete();
    return APIResponse::success();
  });
  Route::post('collections', function() use ($pageSize) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      [
        'name' => 'testCollection',
        'pages' => 1
      ]
    ]);
    $result = [];

    foreach (DB::connection()->getMongoDB()->listCollections() as $collection) {
      $result[] = [
        'name' => $collection->getName(),
        'pages' => ceil(DB::table($collection->getName())->count() / $pageSize)
      ];
    }

    return APIResponse::success($result);
  });
  Route::post('collection/{collection}/{page}', function($collection, $page) use ($pageSize) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      [
        '_id' => 1,
        'description' => 'Example document.'
      ],
      [
        '_id' => 2,
        'description' => 'Another example document.'
      ]
    ]);
    return APIResponse::success(DB::table($collection)->limit($pageSize)->skip($pageSize * (intval($page) - 1))->get()->toArray());
  });
});

Route::middleware('superadmin')->prefix('fileManager')->group(function() {
  Route::post('list', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success($request->path === '/exampleDir' ? [
      [
        'name' => 'example.txt',
        'path' => '/',
        'systemPath' => '/',
        'dirPath' => '/',
        'isDir' => false,
        'preview' => null,
        'warn' => false
      ]
    ] : [
      [
        'name' => 'exampleDir',
        'path' => '/exampleDir',
        'systemPath' => '/',
        'dirPath' => '/',
        'isDir' => true,
        'preview' => null,
        'warn' => false
      ],
      [
        'name' => 'demo.png',
        'path' => '/',
        'systemPath' => '/',
        'dirPath' => '/',
        'isDir' => false,
        'preview' => [
          'type' => 'image',
          'content' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAewAAAH8BAMAAADh0oHIAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAqUExURUdwTP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISP9ISO8lGgcAAAANdFJOUwAJ9hYo5z7SVruKb6MQiZktAAAgAElEQVR42uxd+W8TVx73MMZxjko2hgApSJCSlmNHckoU6FJL4ShkoUiUNmyXYikQQN1CJBpKYQuWuIKANlIWWnZVNVJoF7qoGylbaBfUWkr36IpqIy3eOgfJ+1828Yw97xy/mXlvbMN8f4wz781nvvcxbwIBn3zyySeffPLJJ5988sknn3zyySeffPLJJ5988sknn3zyySefbFAs8hRi3vrgxPtPFWL16sOTHY2NHR2n408R6vprAGgzqM/cfppQH02BGcr85naz8hSx+hbI0equqPU/rj/4xBi80JeaDhr84lJzsedzuevJAK0czYMG05uLoQ6oOx7+dO9J0OmePGiQOcfx/1uu7G5cU+mg5/+tABo0fcFzRcuWXgDWVLSCqw9SBdDjGzkf1MGWmYsmK1ip2/9lsvqxwT8lWuyqzc2LZ/7/+UoFHfsoaaI2UUSLye+MWx+aueL3FYk6uv7vJmiQtakcs7rx10rk9XYNQm2bc6Gck6+4IDZ2FEa9x/4CNTk3X2GoW7+EUfc7WaIaVJpdU3Zcg0Bn3nW2ysDsxSMVBHs7ZMEBcIg6EMw9s8qxa4tSMOo3HK/Tnbv+bIVIeCvsuMCEi5X0FdoqAvURWK9B1k1srbN7vALCc2WDXkMxDHmm09Vq3U5CnZIUUQZ0vI06areKiQe2Zcrr9XnUOrcH3S44R1+uvN2Y8mo+SMk4C0lJ0txbCPmeCw7NAJgSsGS1kbWWc00B8dcgK8TzuPb+0mkIQS0oX04Yq90sV9QHUNSCqkJBobIjntI5U2ZG48OC1n3GWG9lWZq1Or0OPpBH/bGwlcWvKNB3GZKdEn+PeXZnyzAp6TZuTRMfVyl47bVsjXg2LnLxpeUq5iEUtePKgmWoNpOLnSsv2GkUtejKX11+4XJqESnNNZiIC7c9KbEhkBhaiIn4dUnecVbMy0ix0QQEyGjSFhY/VjYynsJEvFPCJolC9blctLsPE/HjMjapLSy/ojxQV2GoJWnfkPBY312OjaGWpXxzzT55GXpseabW3GKw9KhrPWI25LrLYbwD810SbwnSprFSo56LM1uiAEJ+ssSFFgVHPe2NPv1cWtg7cdhSCwFQxzxeStRhHHW/1O2eYSS2iscPYRSHLXe7OshyItr9tqeoq3HU/5O8IaBNuc1qvac19BQOW7aBhUIjtIH8Fw9N+ybPIhWaeCEZSZ13r9yENW81G5NypAeqnvAsP8GDcfDI25jwNfiHQ295FKgswlEDD9xIN7RdE/zDkqZOT2A348UFsMyLbVmmJNhz3Ivtg60l0OyAMZhIS3F793lRbOraVBJmo3EhzO6aDg+y8PotyRJoNi7lq2Bbk5L/Uo16cR2O2qsUGImHh5GsSDq7N3QhAVp2tyY5CaGmI2j+WSO9xqbefwktjO8GmZJk+Mh0YlI2u5+9MIBKuOZh3o94kBHoh4TsGtvhl4lQJeIZbETKYbkOAbBcqozvHyqVQSOy3WFEyqWqWv0vCWYPewcbLd/BafdSuTlgO5Fne9p9RZQbTrvDcm/ka4LZI17CDiJbv4c+kF3Stg2BkjIbmw6CX5DbKbM/Ng+UJhyH0UGu+yYasHuSBJWkR4G4sMwpxJZLM2o1BOqpQAlhg31tyC+TXohYjnZ5DBvtu2X/gVq7sx64TS9TToYpz5yGfkrJao/NKbmM483lF1ApBx5sWZqJKezR30Sdq4wUuLb0Mo5LORKganLyg27gZUebT+TgdyETUrJBikFbVgLYA8wJimopdzSnHGScGACEjbeUWJnIvUozJIYGLEh/JCUhDSYjtNJMvobZc0IJCZMtifKQcbRajhZ3qiRM42plEKtQlQ2qeIkfoSkXGScTg37Uyk9I3axkMk5MAUJSnmvOdYrcLFkuMk5UeCBtzlVWXxe4V13ZyHhA0Zil29xsyXTkSZRxssRjRmZqWuw0JCUw5SxlSJgS7GNnBpvESjklMH2u2DWtV/+9u6njA/GwX2R3oxaLne8mnbZVFBhd25I/8HKvBFWoZluZWqF9GkqmzTwFJrb16iemJsgwACF2F05Jinx/aCkJmxH7xo7cOWGKhrYxIgG2asGCUZEPexTw9YBi7d9/shu6nR/kHNOcYk+y5zKHzAVZdpzWVlVaPnzYI/40DpKG2GVrXe//KSsep7yQr7bD8i3rDcBZOsBWbr3UtlKIlCd4ZHz+Rz0evPdYSDBZyq1b0nNSdInMaoPvXOM19RJMOdSJ008u+1GO+8LLSernhQeiCXRdrQxTnmTXMtNEc0xYVETMx9SbrG7KWfLsKiHa1RvntGnTmK99wf2bFMpAsRCtAbJkTbOcyDR1CnFd5wf5onIoL9JTxewN95qkFXFfS+Cf9CPiXhPzzZRexqEeZKNiEHW2mU9d772hSBUNNXi5I+KOb/iTENvVu4dX7fqxesgZt8qt/MpatRfSctJn24TAPr+X/vcFFvmgrverBl1uHU5bqjYllBE3/3meYRkXW7gWXRKybg8EaUhaZV8NFNTiGjJDjWc5w0bzlE2jwrjX5dYUGTdVu5eCWtyrpzP+mV6wCwO2coeBkGNh0xaqTZNw9GUld3GSxkidg6QErsDzJne1BiXF1t0GStFFZM+7hlWVVslQwjxXjB5RuY9M8wH5EhqvRQ6q1bHqYsoQeVNtmEN11459kanaIU2uiOcyrSy35g3jNZGIFNUOpmiohZ5AOwewpsQPWDzvbhHKnWQ8V4qcCR/P28kcuVpH7jyBF7fdKHeIxdBXqKjFzlD0Mbm92GLrOgE9urkMU/0SFbXg4fI0U7cbLGxpSMBxW330UnGYas5EH5Y2wBSfWotHrgg4GYSi2u/SDZ3w1nLOFY1z657Z+kq6Vm5KFAjOUdu+Ms6NZwc/Qc3Cpo26HqSitEMym6P1SSrqjGBmh9m3rlLu4HEcV0zHyk3xUtNbto56w+wFbHNMK3QVTrVOuFZuykMd23IIeMLsnAFhDOAotCe/H/c+ToMIShcIjB2mi7jwI+NzYeAYf/AIvsAct+OhJZqfmHiVjlr4yem5Gt4Yv2MFb0Swux4XZ9HAeLc3zI4dsmpV025iIo57N4cFD5oGZVJ0ZovW7JYhq2OgE7RM3/h4pOr29VPAT3sEo1baNatq5Dzak9+M3/YxUXkIi4Qf2Kxus/z+Dg026ML9j7N3KObyw34zIprbOVs9Hrdza3fNWN6NTevjZ/YFwaiNb9JMRtiFF5KuR7Agy1kSluJn9kbRzD6fW3c16/dqapgYx9nlRPUUjQfx7D9lv40Lhm343pUW1UVKVN5mVmWcV/ZqeVDnWn1rLoqW8XSRYIB6b+P7cXvnJE6bxwV7ltt/3i+H2exZJyrs/GBWlatzdrt5NXv6smgZN4xSZtieJN7DNcCJTUvzCvkp0TK+zZCiyTY79Q8Abus/1rhqVozyeq/7gmV8UdIwlYUPJIVHuLj9YwT/0UF4yuu/pi46i1UiUfo3yGOzY0/aLOxCk3reMS5un4rgP9r/tEtQ44R9o9nOstHmaKzl8OXvvrp/cG20mXxgSuy3+Y3Nb9Wm+nGBoIcPbXhQvcKxNS2q3V12YgFl/Z3CtGLTmW8J2MraHSkiq6sjUhI6t6f0C1SN0gjlpSpO2I/tmPHYFVSG9t3D7qv+e7N4U3jZhTwGjp4lTetDl9BnB+0XP3j9lx312XEtg1/+609hg9h6i9aqHyBME53bk3eJAuNZWRaN/3mGbtE9wd2CLLzTQxsfCJEWmZET3yNg/yCpxsAf/6mM2iNo+t1nX13avPUqOo1upo1VJNMYsA0PNkrplfAack7Y3BUMI6nip9dNdSOK0XQhz9wgYI9JMuS8Mq5+bRO1yWGNnH6iwwan4nhX47EcQ867LK2PUSQKgu5kki84BW8RsO0OaiWEyngwaRe12SsfoLyHxIC9uo3IJmx6MM6KUlzoaoDCpjpaW4cB26gZp51PgvIlIpxFuhrbqE2DNkrLKBiwxzcSD9leVK7wSWXRDy0oLW9fap5vX8Sb2mCAcU6Dmz1HwF4hw38VCYKCD05owBG9hzh7Xj9j1CX6WLPvgsTSWsaV7SngkApv2KTptSHW7d0kYNt7i30h190ts1zjG6egQUY//02JHmLEHCzY1wnY9gYEN7mOVYKfO0ZtjDy1Xv6G9XQZ0zPgfdJv2PJgQ1zlJLGeGk0glJYjo8w7Z8F+k4RtJwdTB1zacSXtAvWqu4Fo64d3kmwLUs0SkwgB247j5uOVxYqvuEANGjtOnuwwXcAjftjTJOx+Oz1eLr/Dvr7ODWqQyWgZ65FOVsagv1PTR417OCjsMg0ZAgIpwg97nIT9vGjYj9xdzu3DbbTe9dpZN3AYr3Bl251MFUmKhP1fyg5LWbA7CW7biVcWunJfAyJRUz1Qt+U/95FyL65suot18QKhqMdt2Y5hontnZzqSI+3MtHnD7F22thgkH4oNx81x58xgVwWyDZpFNXuQZBp/zVjhSJ2m3bZT+GiZvfmSEZJp/Gdj8BQZpq0V5PTlI7c0AbDj9qoBFNj88QrPtM6YVYC3b8aORAJR9Ypb5PSHyx4U7CdVgL++wqOe/Ywi0iakgOUqEQOsd41qLSu5mKzyh2lBp9IXCLysoVFr2BW/s3ZD/l2krE5FBMamrMWqifR4nQvYDJ/LHgtdTjKNv8fdUPyGWJNuVdqE094pf7KTsCwBhGjJKA8tdl4zrcp/ji4qQM47bXcbfiZllT86LR5eMoOVbcbzUKCBnIaUUM22ymvHSIOX5Ya903mu/V2+vPLBxajt9ilvrsOOnSdIg5fhLSIqxTMRVhlNHTaN7b7C+V2qJlLErazFBKXixJuLqGmH/nTmiUUgjmT/GDfGj5x47+VOgsgpip3nrZ2qoy44gYTm4z9d2hiIxtqdcNui0gcsYS+lJaNCysU8M6wGS7Kf/ecPPQ7S7GFHtZ8pimUa4YVd1PLyTCzPcRWeWZnfamvYuHvjPYpSLaqKXJMwLlBbz/glrGHjlom3xR1zXlCCyXlj5LHj2Ygpint7jhN28RfA/k/dtb/GdVzhu17JlmUZrmrHr8QQ49h1Yi+snJgmaQ3XTU3aNAKlDW4KFaztuCFtBTIOgRYWpLShtLAgk6RNAwK5KqWULsipaQvtwsrqD4F0QV7tarWv/6XRSrt7H/P4zszcezfzoy1d3e/OzDnn+86ZM5CVoJfp7G7rP6WVJa8thjSEMk85E8ECn1EV0Gc+S4PGkgc7q7IjkaAKPVdG9Fv1f//tD5/Ij5QlJUYnxVj4RmCjFZeTJNTlZTeBUVtEmwxTisKWJu4emdTb+2voJkYRRYcwKwyNpGUKNp48JS3z/9raa6jCWKso7P1GDDnoFFzjPHb8oiCGPaYSW0FKN6EehLK9a9gRo6x4b+8LCzbp0mdKqhtrRtsmwkbLbWUHt2mN/wjSyjPIMhKq2RWGNITC3mPIf+Eplu4L/l1Xza4wNBK0NO2ksgDAfk88SP1Y18+ctobyKiwZMUNFizjeQmFfcDTf7rZ1LKsYSkuDDM+77UWe+B1QaDgDrPKSOKJ4JaXqeGYo0spxjMJ/69sPoVVu65mKBeu3KgqYjM8GDflHoGEbP5w1YstHJPHjf8KC7Qnt98Mn4p97FbHl81oWrX03+Ws8g0OD7TmwkodhJw4B012WXoVyUfj7HxwphDXbs54lh/d0SSAB2znJ5u7U8Ja5v772fCms2V7yeBNC/wNEXKtJNrekTmDtu9NhwZ7z6js4L4FoyZqWBLL8k3ZYsB3vS+CwLyOwzxFpksdglB/8JjTYtnf+MjBsqHRLssr9MYW3bV31wafKsBM5lHZ26tfw2caqzJeFzwiowV7Y/yipz3YBlU1HaZoDlua+JrKR/q6U5Xkv7D8vhrXIG94VR7hiASszbzmEL9f0rvrqZ6mwYDe9uTLCQStMWCtfJ0gBd72wy5+3w4Jd8Vb2EC7+Ak9Qfkh4Ncdn4/6nlowHYK97f44w22Atx6YN62gNv2n/SAP2DBab7qAgRGmgvCSoqBoN5nBn+F7cKOyiT04nwAbVxBVY7pqDQj/Q10xisWmOChttylVBXet2gSEQ+qVNwHZ8wi0hVwBq5nyf6Fsu7wB6Jz4xJ6FvN0yHnQOVxFXMFXS6vx40BnsPFKTlaV6RkgCt2FBQfz4NzTZqdIcRJS1JzwPCCbHWBLRaljFah0YW+5DYdB9LdTCVB3wOWeONK9gKqpmAveWHMIvDvozCXmMaNd9ZxGUbsxco7P2SnKLXqG7gsE+isCvX5e5r54g6ALtuAnbFzwAJTargwsT6S9Igr9syMCLYG/6NQMh/4q2/fynlra9bpmELVZBHfutEyHbjBwK/L/to3S6HCTls9AWFZXRTPcmU+DGRopi+lbYlXv8OrkKjzc1G5ARshO4WSbBZrMm9tZ+xcdjoLhySE7CD9EifBntKqCfVM4Q4H605FcoBxcAfmwsD9pbI+1XnKawOLawWygFLgQW3FIJJY1RDuf7kms1XkFnJXwNywFJA3FkIY7YDa8jlXs6P87moRs2JHPaIeBsamO1AXVCJnfoHhCp0NQrr35f8Fo0QnVJgNzhmtvqxh57JD3jAFDFRksEuqDgIGuwyO66tPfCSUqCfOGxy8zLYKZXgj3QvjX9t7s7EhZu+inOg+xOcm8zJlgwofSlTkeDm7vzTVz9/c4KeV4NVrxkJ7AOKq4gEu+VH98O/TARPFwBZVFjavSTZKcNqHgLn24HXTf71X2/aauYCD56PS2D7VBL0oBWurgS+ZoL3SPlRc7ymaEwCO68W69OOjnhOpCTUvyROjA9I7KIvnIHbQudIsCHCOGnmMVLmmbGCB19QW1kgwW6b+ZJ4WCEq/nKC6VY4DiLCLmqGGLKMWoB5ZoWwh1SpXZ4GG5knOQHDKYMoOnUYRGpT/7EY+VRJmRPSFwVh5i/gfuuYTSMf8ZWHf0BdCCFH955wtnOKYS/hsAzK5EdMfLveuCqEnVcUMIaIqIGzpKMmdkpvfE3owBYVreVeKmx5pCGPTSmXeT4hgs1Yqxj3JDfbKRuIVihn9UZFFoLVGgra3PTOxlJzVDLiBXvzkhIQzzFVdfIoCPYH0+jmBroXVgiwR7ICN/gYPyEoHl/BUF/oa5gtfWsxRYC9d1EA+2J4tKHncm5hYTlgLYoE2IKuSkU2fUS8Y47gt96Hon1AeM8QYCceCkKInGoslKMY8J07mTe0NUmHANt6KMh4FlS3EEzAir2NJnFhFw0qaeLo9BTbaQBdPnAmsmPJkn+UEomcAdePiYgVdqoIeDyh1eu9Xn5rQzlrRS46EIb4WxxBftZA/OwvZkjkxS4M8F/EzgLcBdk8ofr8Fwjh2XIvCrP1DDmxswAXduMFVYL3PkVY6aL9qQj2N826bZHVbbysGg5RLhHCrlsFXEOaBpsbUbWuKlIdmqR0D3lJQK2hoeZnG1o3FAODoSwFNhLke5O81ZRWJkhCEls5xaCfdttIjexuzme1cgMSD9YqKBL6x2lUG9DevYb83UVN2inUveolRVHgGzTYgAjtzX8xj3dOEWFzK4BqWcU3Jd6VBRx18Ky72o+zeiK5ONytTit6HWImCAj7PG/SZHYMdqiwuVKIQMcRXeGBXShHITdejeGdV7T5FzGChrIjx6i5AWmk4d3aD1hhVI0MW6mnuCDIeJH8MFlC0ZOlqL90Q80uojYNCqWD4xb9YRTV9OxEQck+aJugjvnl9gZK0h8n6Z7vjSyWx/N6aT+ivOsdZ3nTPbRodMv480DVK+OMeILQQIGQXmL5MF6L1mMKpqKCk6VzNstTtOioFe8pvHaF/bRXVRxDGqZfq8wKDAWLFuj1AU43u2oucUvlYSvorHxhBliXfL+mANuaVptu5hQl8yrPeg2VyJvsxVlUga30pu0zN1nPOqLiDoUurOBd48x7jjIqsJVMebv9hm0kWOnsGH4Y4Gnyut2H6ZISmzGSjt75Y/cZW/sttWctYX5mu+vWjFLKwphN+8IAB8OMIbUNI5CWPAH5GlvqXVCCrXyR1xuBJ31d8VF1SNDednSsMyMZNdgzirCDvb9+pvokB9nar7MTJGpbW+MemJYvZkn8U/VJRWRrZ9gJ/roaarULMjrjQ++HTn6q+qANIKZocOzvliJs9Wtnq/NocYQsBwhY21kOgzitCFvj1p9WWj+8F6hC+wM0a9II69wZKfXXvWbENvI296TPoDEVT1sV9nH1123fN7FZeJs7SC5LRlinXsDSDRh1mLsoKeR6YPdW8ay5ra1Kwna/9ryOPCX03K6Y7yxX8FxSh631wtUVltZHHQtiqr3Crctz1GFfbGvhtnXteJtZdnqw/79PO7x9VLbVYWvenH5NnCpnxJM/zwI0qhRkPRcNWjToULgQxbs2baeUV5/1V7hUBfVJ5Qx/N97WmO3EpN50t390nZZeeWo80Lzd4a/xJQFZzGjMNqmJBHM8fZOU4G2NW0dKksxG/xsJ4kn8QmLWOJxqRzvuBXKjFd5MuCSnIO2s2DqwE9MRw94KVLnUefr4nIgrFi0t2DMRw+4kvjynUcocNrwprKtytGCr6ojqYzbwVzPMyV4Qcp2arQf7UCqGVW5ZN3hx2ghzEZdMpIE8qzwb9XR3TLD7OOgGo6RmSSJ2LmjCRspZzY51f0TbDAo+5yVZynJaF/Zo1LB3TdhRpinvZL5+58iiiy1d1NG7sK5jKnmX/U6aYftesQ/kjEl7jVP7SBgYu3u5X0jbz/cOf49ZJ+KPZ8sZfdiXYlrlfS/Wr6B8/Pe/uA6oIS1bH/ZI1LB7UfjloDc67Ehrltp490PLZPmg9mj6lFu3nmYjVnfVAOzoXZi/87usFt6fiK86JmCPxrbKu5GoTTO6Z02g1pQAVUZvM+9yEolhXjTKvuJzYX35bwzA4Z8W/RDNkMSikQLqfPJTJFfTNINaKzmiNipe3WSLJO/OGoKtk7rTTnTmpeLvDMcPaI/ItQZXUD0m3a2LQNYs2uIdTbGhR0kyBK5UMQf7ZOTT7XjCBhGjOmAu5Rd/XL7g4Veio49HzSWBpBFBlKt8VHxmcSYkO65Rf6qRLU27p1tgpvxN2GyTsA9EvspX3eGSwJT7eiZsWUZH5HF5xU19BXLJE6EZtFgiFrfA/2KKX2x1IyynHRP7dCFNvs2dbV+l3ynDsKNf5W6ndYhrqHzN2zKmYQ9HPt1uc819rROhkK84pxtSC66a1scF9UERayyikQ/ToMXCuhGVxOu1p0KAHb22BPjg/eEw7Vi1pVPEcKJhhTKiTgICWzUbGguJ0aitSLd2eCzEElR/hTyeJKldmyGhjtyoSSWDyRBZSIz0U7ZsF8OSVeI1alMErasYHuzhwbLle6KZ7OiNWhqOTGfDhH15kAI194mjcpio6Q3lNcc6GjY+ChV21D6shi49O1zYUfuwDLa1m1bIoxDxKrehrZ0JG3bEeaFGGtnaDSv0MTkogVopZKId63QXAbWnaUUwotUSKwD7cqKAHe0q5x5a7LuvuhXJyA+E1jAdoT0LeI7YArW9uAbzpYzMaxKFq2xFNiKdbkec8nsqOtiR8u4poRutWRGO6bhd2MkIRBWJZGnCTU3zo4Gy6Ls3rUhH1jDsXy1CCf4AEcxEC9s0AT0tuH7jEZ8HVqyIx0nDkrigN2aN70qcqGGbrtCbSBSQuoad0a2l3ogctWlZbVVwinaWl+i0o4dtmJJsCijtJmeNOzGgNlzesF1teRRc5ftiW+LmY7W7AqFujqWrlK2YhlHBoSmwF+ssBpiJC7bZ0sRtGJcQF7bjOxes2MaM8di7BAiJqQglFVVKUoZZS2evcm7GWwnwgZU4YcuX+Sbu3zsMk31P7rrfccY62YDQ0krDVxvu7GDmAeK6z2nXrJiHLEYtz+FXOnYWLrsZRNrrtJ24YScBf4z2DjvF3zgLHqe9YMU+ZFmSba0LvPmtwfcP625r0rAGYEg6B+7cyXkLDVA5/qHu3lSZQYAtufK87HRwv0xIeI3xPfe2vHF7IFDLtvddXI+p8PMuSz1ZZcsakCFe5t37kw7IDVu34R3jyr0nu4bE1GH80IXUK10wcsPWNdF7OJt7RqMRdQhDWGt+ZxyOZbf4ymy6Q/HL96xBGkdFlPLZLpOQ9qXqpXVHmKFr8pOMNVhDBOhmb7qlAVsvEHlMqiwNxEgIotQ7E/0fK0klNR6ptQZz8A1Ww31qLwvZcoZbnLO+bPN9fxxWHvs8OodkPgdh8A30uDuPlJLTEZZV2xxU2HxWMu9WhYT3/jW4XL41qLCt0WlJpAbsb4e7euyBxc295c1rj0R9z2e5Qe+SNbhjGsrn7E1BxQtZWcJ3cOb7PTYaH4G4BBUveFlb1RrkUZBYaBl7yXAVSmeQYSffTiFFlCegyqRpqOx2MMbzWWC6rRsyFhawao/+397ZszQQBGF4zzN+FwlBO0GwDohIBMHOIggWai1oLQFBLA/ExkICaSwDsRCxCNiJhZDGwsJCJWrE/BdPzMfmdmbv1JjbHeZpL83c7s6+887exuyw3dw5MNyKPXAU5WSSh70QE3WqcwxbqJE89jJWydeSwnSWlJ1MvSrH9SKcTJLfzY7xYYupO43ubG3fEdq6csrfNz9s4SgrvBrRjEL96LeksCHw1a0wvXGrsctbQl/23IUVZK+79nD1ml3wNFpgo6rYIcvlqFK5uxCHxNO3dbsk6tOmsCXudO6iM+LAhelQVRL4VcF8q0GNO+Vkb7Y0uXgobHFL9kVdWEPKjz29e/o9qDVgmqu9pOBBuwIu9Ywm6W9nlwd+5Jn6XIQa/RWdEGVhHc7Cyf1G4yzKNA+mgLZpPitsxEmvQNN0WSvLhdQC/RCUeAj7YGDNzsUdwiBwABX+QZXUcAcsdlXGrlm9uFHmtY5jpyX2TivsQMtrDxtuC6yGnzlRWsHSWd2HxIZ7NKRGzRve+PwtJb0nPmSHj/i3XayMrf5XamF3nfuZUR+Pm71bRikAAAETSURBVN747IFYgyrrCkHBEqhJoD7fhA29kd8wqW/il2L4ZLsveNrKetjkk1q90ebgXM4Y3+/96zQHxfc0zZwmZXPw7IJDNKdJrjg4l/MEi7BvboEv/bq2uCeSYQ9qD1t6RHNa+7YL+Kv0ROOfr3KND20HJI5rR/rDGGIstZJanWbYzcq7ir4Tmou7mdWeUf1aJDrcD5qaYyC2Ozj+HVfjo7gEjaUWBU2pVSFoLMniOwk/G+3LNbbxSdQr9JVckR3uDK7G8o11qmF/OSmPqJx5IRu2v4mhPXzvg27YY7hrlqjRDdvfqA7RYmWbbtgj+D+C5fcID/ciqsbcIuGwxYpgGIZhGIZhGIZhGIZhGIZhGKanfAJfcNLIdmka+AAAAABJRU5ErkJggg=='
        ],
        'warn' => false
      ]
    ]);

    $warnPath = [
      'node_modules',
      'vendor',
      'public/build',
      '.git',
      '@web/resources/sass/themes-map.scss.backup',
      '@admin/resources/sass/themes-map.scss.backup'
    ];

    $path = $request->path ?? '';
    $path = $path === '' ? '' : $path . '/';

    $result = [];
    $files = array_values(array_diff(scandir(base_path($path)), ['..', '.']));

    foreach ($files as $file) {
      $p = base_path($path . $file);
      $extension = pathinfo($p, PATHINFO_EXTENSION);
      $isDir = is_dir($p);

      $preview = null;

      if(!$isDir) {
        try {
          if (@is_array(getimagesize($p))) {
            $preview = [
              'type' => 'image',
              'content' => 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($p))
            ];
          }
        } catch (\Exception) {
          // No preview
        }
      }

      $result[] = [
        'name' => $file,
        'path' => $path . $file,
        'systemPath' => $p,
        'dirPath' => $path,
        'isDir' => $isDir,
        'preview' => $preview,
        'warn' => in_array($path . $file, $warnPath)
      ];
    }

    usort($result, function($a, $b) {
      return $b['isDir'];
    });

    return APIResponse::success($result);
  });
  Route::get('download/{url?}', function($url = null) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    return response()->download(base_path($url));
  })->where('url', '[ \/\w\:.-]*');
  Route::post('rename', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::reject(1, 'Not available');
    rename(base_path($request->path), base_path($request->name));
    return APIResponse::success();
  });
  Route::post('delete', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    $path = base_path($request->path);
    if(is_dir($path)) {
      $deleteTree = function($path) use(&$deleteTree) {
        $files = array_diff(scandir($path), array('.','..'));
        foreach ($files as $file) {
          (is_dir("$path/$file")) ? $deleteTree("$path/$file") : unlink("$path/$file");
        }
        return rmdir($path);
      };

      $deleteTree($path);
    } else unlink($path);

    return APIResponse::success();
  });
  Route::post('newDir', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    mkdir(base_path($request->path), 0777, true);
    return APIResponse::success();
  });
  Route::post('upload', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    $request->file->move(base_path($request->path), $request->file->getClientOriginalName());
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('themes')->group(function () {
  $compile = function(string $type): array {
    if(\App\Utils\Demo::isDemo()) return [
      'error' => [
        'This action is not available in the demo version.'
      ],
      'code' => 1
    ];

    $error = [];

    $process = new Process(['npm', 'run', 'build']);
    $process->setTimeout(null);
    $process->setWorkingDirectory(base_path($type));

    $code = $process->run(function ($type, $line) use (&$error) {
      if ($type == 'err') $error[] = $line;
    });

    return [
      'error' => $error,
      'code' => $code
    ];
  };

  Route::post('data', function () {
    return APIResponse::success([
      'web' => file_get_contents(base_path('@web/resources/sass/themes-map.scss')),
      'dashboard' => file_get_contents(base_path('@admin/resources/sass/themes-map.scss'))
    ]);
  });
  Route::post('compile', function (Request $request) use ($compile) {
    if(\App\Utils\Demo::isDemo()) {
      $result = $compile('');
    } else {
      $map = $request->target === 'web' ? '@web/resources/sass/themes-map.scss' : '@admin/resources/sass/themes-map.scss';
      $backupPath = base_path($map . '.backup');
      $map = base_path($map);

      if (!file_exists($backupPath))
        file_put_contents($backupPath, file_get_contents($map));

      file_put_contents($map, $request->text);

      $result = $compile($request->target === 'web' ? '@web' : '@admin');
    }
    return $result['code'] === 0 ? APIResponse::success() : APIResponse::reject(1, join('\n', $result['error']));
  });
  Route::post('reset', function (Request $request) use ($compile) {
    $map = $request->target === 'web' ? '@web/resources/sass/themes-map.scss' : '@admin/resources/sass/themes-map.scss';
    $backupPath = base_path($map . '.backup');
    $map = base_path($map);

    if (file_exists($backupPath)) {
      file_put_contents($map, file_get_contents($backupPath));

      $result = $compile($request->target === 'web' ? '@web' : '@admin');
      return $result['code'] === 0 ? APIResponse::success() : APIResponse::reject(1, join('\n', $result['error']));
    }

    return APIResponse::success();
  });
});

Route::middleware('admin')->prefix('stats')->group(function() {
  Route::post('validate', function() {
    return APIResponse::success([
      'isConfigured' => Settings::get('[GA] Configured', 'null') !== 'null'
    ]);
  });
  Route::middleware('superadmin')->post('configure', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    \App\Utils\EnvFile::set('ANALYTICS_PROPERTY_ID', $request->propertyId);
    $request->file->move(storage_path('app/analytics'), 'service-account-credentials.json');

    try {
      $client = App::make('laravel-analytics-v4');
      $lastMonth = Period::months(1);
      $client->runReport(PrebuiltRunConfigurations::getMostVisitedPages($lastMonth));

      Settings::set('[GA] Property ID', $request->propertyId);
      Settings::set('[GA] Measurement ID', $request->measurementId);
      Settings::set('[GA] Stream ID', $request->streamId);
      Settings::set('[GA] Configured', 'true');
      return APIResponse::success();
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error($e);
      return APIResponse::reject(1, 'Failed to run report');
    }
  });
  Route::post('pages', function() {
    $client = App::make('laravel-analytics-v4');
    $lastMonth = Period::months(1);
    $results = $client->runReport(PrebuiltRunConfigurations::getMostVisitedPages($lastMonth));
    return APIResponse::success($results);
  });
  Route::post('newUsers', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['date'])
      ->orderByDimension('date', true)
      ->addMetric('newUsers')));
  });
  Route::post('totalUsers', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['date'])
      ->orderByDimension('date', true)
      ->addMetric('totalUsers')));
  });
  Route::post('popularDeviceTypes', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['deviceCategory'])
      ->addMetric('totalUsers')));
  });
  Route::post('popularBrowsers', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['browser'])
      ->addMetric('totalUsers')));
  });
  Route::post('popularOS', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['operatingSystem'])
      ->addMetric('totalUsers')));
  });
  Route::post('region', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['region'])
      ->addMetric('totalUsers')));
  });
  Route::post('country', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['country'])
      ->addMetric('totalUsers')));
  });
  Route::post('city', function() {
    $client = App::make('laravel-analytics-v4');
    $period = Period::months(1);

    return APIResponse::success($client->runReport((new RunReportConfiguration())
      ->setDateRange($period)
      ->addDimensions(['city'])
      ->addMetric('totalUsers')));
  });
  Route::post('game/{id}', function(string $id) {
    $report = new \App\Utils\AnalyticsReport('date', 'totalUsers');
    for($i = 7; $i >= -1; $i--) {
      $metric = DB::table('games')->where('game', $id)->where('created_at', '>=', Carbon::today()->subDays($i))->where('created_at', '<=', Carbon::today()->addDay()->subDays($i))->count();
      $report->add(Carbon::today()->subDays($i)->format('Ymd'), $metric);
    }

    return APIResponse::success($report->get());
  });
  Route::post('registeredUsers', function() {
    $report = new \App\Utils\AnalyticsReport('date', 'totalUsers');
    for($i = 30; $i >= -1; $i--) {
      $metric = DB::table('users')->where('created_at', '>=', Carbon::today()->subDays($i))->where('created_at', '<=', Carbon::today()->addDay()->subDays($i))->count();
      $report->add(Carbon::today()->subDays($i)->format('Ymd'), $metric);
    }

    return APIResponse::success($report->get());
  });
  Route::post('registeredUsersTotal', function() {
    return APIResponse::success([
      'count' => DB::table('users')->where('bot', '!=', true)->count(),
      'today' => DB::table('users')->where('bot', '!=', true)->where('created_at', '>=', Carbon::today())->count()
    ]);
  });
  Route::post('gamesTotal', function() {
    return APIResponse::success([
      'count' => DB::table('games')->where('bot', '!=', true)->count(),
      'today' => DB::table('games')->where('bot', '!=', true)->where('created_at', '>=', Carbon::today())->count()
    ]);
  });
  Route::post('invoicesTotal', function() {
    return APIResponse::success([
      'count' => DB::table('invoices')->where('status', 1)->count(),
      'today' => DB::table('invoices')->where('status', 1)->where('created_at', '>=', Carbon::today())->count()
    ]);
  });
  Route::post('totalWalletBalance', function() {
    if(\App\Utils\Demo::isDemo()) return APIResponse::success([
      'balance' => 1000
    ]);

    if(!auth('sanctum')->user()->checkPermission(new \App\Permission\WalletPermission())) return APIResponse::reject(1);

    $balance = 0;
    foreach (Currency::all() as $currency)
      $balance += $currency->convertTokenToFiat($currency->coldWalletBalance());

    return APIResponse::success([
      'balance' => $balance
    ]);
  });
});

Route::middleware('superadmin')->prefix('maintenance')->group(function() {
  Route::post('status', function() {
    return APIResponse::success([
      'status' => file_exists(storage_path('/framework/down'))
    ]);
  });
  Route::post('toggle', function() {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    Artisan::call(file_exists(storage_path('/framework/down')) ? 'up' : 'down');
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('appearance')->group(function() {
  Route::post('uploadLogo', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    $request->file->move(base_path('public/img/misc'), 'logo.png');
    return APIResponse::success();
  });
  Route::post('uploadFavicon', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    $request->file->move(base_path('public'), 'favicon.png');
    return APIResponse::success();
  });
  Route::post('setWebsiteName', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    Settings::set('Website Name', $request->name);
    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('update')->group(function() {
  Route::post('check', function() {
    $server = (new \App\Updater\Updater())->server();
    $latest = $server->manifest()['latest'];

    if($server->isHigher($latest, $server->version()))
      return [ 'code' => 1, 'version' => $latest, 'changelog' => $server->changelog($latest) ];

    return [ 'code' => 0 ];
  });
  Route::post('install', function() {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    return (new \App\Updater\Updater())->update();
  });
});

Route::middleware('superadmin')->prefix('ssl')->group(function() {
  Route::post('install', function(Request $request) {
    if(OperatingSystem::isWindows()) return APIResponse::reject(1, 'Windows OS is not supported');

    $process = new Process(['certbot', 'certonly', '-d', $request->domain, '--noninteractive', '--cert-name', 'cert', '--register-unsafely-without-email',
      '--agree-tos', '--force-renewal', '--webroot', '-w', base_path('public'),
      '--config-dir', '/var/www/html/.letsencrypt/config', '--work-dir', '/var/www/html/.letsencrypt/work', '--logs-dir', '/var/www/html/.letsencrypt/logs']);
    $process->setTimeout(null);

    $code = $process->run(function($type, $l) use(&$fullchain, &$privkey) {
      \Illuminate\Support\Facades\Log::info($l);
    });

    if($code !== 0) return APIResponse::reject(2, 'Failed to install SSL certificate (certbot error)');

    $certificate = '/var/www/html/.letsencrypt/config/live/cert/fullchain.pem';
    $key = '/var/www/html/.letsencrypt/config/live/cert/privkey.pem';

    $config = file_get_contents(base_path('storage/config/apache2_ssl.conf'));
    $config = str_replace("*ssl_cert_chain", $certificate, $config);
    $config = str_replace("*ssl_cert_key", $key, $config);

    file_put_contents('/etc/apache2/sites-available/default-ssl.conf', $config);

    return APIResponse::success();
  });
});

Route::middleware('superadmin')->post('/serverIp', function() {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  return User::getServerIp();
});

Route::post('sportBets', function (Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\SportManagementPermission())) return APIResponse::reject(1);

  $page = intval($request->page);
  $pageSize = 20;

  $bets = [];

  foreach (\App\Models\SportBet::oldest()->where('status', 'ongoing')->skip(($page - 1) * $pageSize)->take($pageSize)->get() as $bet) {
    if($bet->multiBetId == null)
      $bets['single|'.$bet->_id] = [[
        'data' => $bet->toArray(),
        'user' => User::where('_id', $bet->user)->first()->toArray()
      ]];
    else {
      if(isset($bets['multi|' . $bet->multiBetId])) $bets['multi|' . $bet->multiBetId][] = [
        'data' => $bet->toArray(),
        'user' => User::where('_id', $bet->user)->first()->toArray()
      ];
      else $bets['multi|' . $bet->multiBetId] = [[
        'data' => $bet->toArray(),
        'user' => User::where('_id', $bet->user)->first()->toArray()
      ]];
    }
  }

  return [
    'maxPages' => ceil(\App\Models\SportBet::oldest()->where('status', 'ongoing')->where('status', 'ongoing')->count() / $pageSize),
    'bets' => $bets
  ];
});

Route::post('setSportManually', function(Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\SportManagementPermission())) return APIResponse::reject(1);

  \App\Models\SportBet::where('_id', $request->id)->first()->validate($request->status);
  return APIResponse::success();
});

Route::post('sportVerify', function(Request $request) {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\SportManagementPermission())) return APIResponse::reject(1);

  return APIResponse::success(['result' => SportBet::where('_id', $request->id)->first()->validate()]);
});

Route::middleware('superadmin')->prefix('phoenixGames')->group(function() {
  $sendToPG = function($url, $data = null): \Illuminate\Http\Response {
    $response = (new PhoenixGame())->curl($url, $data);
    if(str_contains($response, "error")) return APIResponse::reject(1, "Phoenix Games error");
    return response($response);
  };

  Route::post('toggleFakePresence', function() use ($sendToPG) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    return $sendToPG('https://phoenix-gambling.com/dashboard/apiKey/toggleFakePresence/' . (new \App\License\License())->phoenixGamesApiKey());
  });
  Route::post('toggleFeature/{gameId}/{featureId}', function($gameId, $featureId) use ($sendToPG) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    return $sendToPG('https://phoenix-gambling.com/dashboard/apiKey/toggleFeature/' . (new \App\License\License())->phoenixGamesApiKey() . '/' . $gameId . '/' . $featureId);
  });
  Route::post('setFeatureValue/{gameId}/{featureId}/{key}/{value}', function($gameId, $featureId, $key, $value) use ($sendToPG) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    return $sendToPG('https://phoenix-gambling.com/dashboard/apiKey/setFeatureValue/' . (new \App\License\License())->phoenixGamesApiKey() . '/' . $gameId . '/' . $featureId . '/' . $key . '/' . $value);
  });
  Route::post('info', function() use ($sendToPG) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    return $sendToPG('https://phoenix-gambling.com/dashboard/apiKey/info/' . (new \App\License\License())->phoenixGamesApiKey());
  });
  Route::post('setCallbackUrl', function(Request $request) use ($sendToPG) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
    return $sendToPG((new PhoenixGame())->curl('https://phoenix-gambling.com/dashboard/apiKey/setCallbackUrl/' . (new \App\License\License())->phoenixGamesApiKey(), [
      'url' => $request->url
    ]));
  });
});

Route::middleware('superadmin')->post('resyncGames', function() {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

  Game::forgetCache();

  return APIResponse::success();
});

Route::middleware('superadmin')->prefix('plugins')->group(function() {
  Route::post('all', function() {
    $pluginManager = new \App\License\Plugin\PluginManager();
    $response = [];

    foreach ($pluginManager->fetch() as $plugin)
      $response[] = $plugin->toArray();

    return APIResponse::success($response);
  });
  Route::post('installed', function() {
    $pluginManager = new \App\License\Plugin\PluginManager();
    $response = [];

    foreach ($pluginManager->fetch() as $plugin)
      if($plugin->isEnabled())
        $response[] = $plugin->toArray();

    return APIResponse::success($response);
  });
  Route::post('toggle', function(Request $request) {
    if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();

    $plugin = (new \App\License\Plugin\PluginManager())->find($request->id);
    if(!$plugin) return APIResponse::reject(1, 'Invalid plugin');

    if($plugin->hasEvent(\App\License\Plugin\Event\PluginStateEvent::class)) {
      if($plugin->isEnabled())
        $plugin->onDisable();
      else
        $plugin->onEnable();
    }

    return APIResponse::success();
  });
});

Route::middleware('superadmin')->prefix('slots')->group(function() {
  Route::post('settings', function() {
    return APIResponse::success([
      'enabled' => Settings::get('[Slotegrator] Enabled', 'false'),
      'type' => Settings::get('[Slotegrator] Key Type', 'staging'),
      'url' => Settings::get('[Slotegrator] API URL', 'https://staging.slotegrator.com/api/index.php/v1'),
      'id' => Settings::get('[Slotegrator] Merchant ID', ''),
      'key' => Settings::get('[Slotegrator] Merchant Key', '')
    ]);
  });
  Route::post('setValue', function(Request $request) {
    Settings::set($request->key, $request->value);
    Game::forgetCache();
    return APIResponse::success();
  });
  Route::get('slotegratorValidate', function() {
    return APIResponse::success(\App\Games\Kernel\ThirdParty\Slotegrator\Slotegrator::request('/self-validate'));
  });
});

Route::post('gameAnalytics', function() {
  if(\App\Utils\Demo::isDemo()) return APIResponse::rejectDemo();
  if (!auth('sanctum')->user()->checkPermission(new \App\Permission\GameStatsPermission())) return APIResponse::reject(1);

  return APIResponse::success(\App\Models\GameAnalytics::get()->toArray());
});
