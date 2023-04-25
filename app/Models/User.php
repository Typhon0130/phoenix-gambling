<?php

namespace App\Models;

use App\Currency\Currency;
use App\Events\BalanceModification;
use App\Events\BonusBalanceTransferred;
use App\Notifications\DatabaseNotification;
use App\Permission\Permission;
use App\Token\NewAccessToken;
use App\VIP\VIP;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\BSON\Decimal128;
use NotificationChannels\WebPush\HasPushSubscriptions;
use RobThree\Auth\TwoFactorAuth;
use Laravel\Sanctum\HasApiTokens;

class User extends \Jenssegers\Mongodb\Auth\User {

  use Notifiable, HasPushSubscriptions, HasApiTokens;

  protected $connection = 'mongodb';
  protected $collection = 'users';

  /**
   * The attributes that are mass assignable.
   * @var array
   */
  protected $fillable = [
    'name', 'email', 'password', 'avatar', 'roles', 'client_seed',
    'bonus_claim', 'ignore', 'private_profile', 'private_bets', 'name_history', 'latest_activity',
    'discord_bonus', 'notification_bonus', 'ban', 'mute', 'weekly_bonus', 'weekly_bonus_obtained',
    'tfa', 'tfa_enabled', 'tfa_persistent_key', 'tfa_onetime_key', 'email_notified', 'dismissed_global_notifications',
    'register_ip', 'login_ip', 'register_multiaccount_hash', 'login_multiaccount_hash',
    'referral', 'referral_wager_obtained', 'referral_bonus_obtained', 'promocode_limit_reset', 'promocode_limit',
    'bot', 'favoriteGames', 'withdraw_limit_reset', 'forced_vip',

    'vk', 'fb', 'google', 'discord', 'steam',

    'btc', 'ltc', 'eth', 'doge', 'bch', 'sol', 'tether', 'dai', 'shib', 'matic', 'ape', 'usdc', 'trx', 'eth_pepebet', 'eth_usdt',
    'demo_btc', 'demo_ltc', 'demo_eth', 'demo_doge', 'demo_bch', 'demo_sol', 'demo_tether', 'demo_dai', 'demo_shib', 'demo_matic', 'demo_ape', 'demo_usdc', 'demo_trx', 'demo_eth_pepebet', 'demo_eth_usdt',
    'wallet_native_btc', 'wallet_native_ltc', 'wallet_native_eth', 'wallet_infura_eth', 'wallet_native_doge', 'wallet_native_bch', 'wallet_native_sol', 'wallet_tron_trx', 'wallet_infura_eth_pepebet',
    'wallet_infura_eth_usdt',
    'wallet_trx_private_key',

    'vip_0_bonus_claimed', 'vip_1_bonus_claimed', 'vip_2_bonus_claimed', 'vip_3_bonus_claimed', 'vip_4_bonus_claimed',
    'vip_5_bonus_claimed', 'vip_6_bonus_claimed', 'vip_7_bonus_claimed', 'vip_8_bonus_claimed',
    'vip_9_bonus_claimed', 'vip_10_bonus_claimed',

    'isDemoLimitationsIgnored', 'isPhoenixGamblingManager'
  ];

  /**
   * The attributes that should be hidden for arrays.
   * @var array
   */
  public $hidden = [
    'password', 'remember_token', 'email', 'ignore', 'ban',
    'notification_bonus', 'latest_activity',
    'tfa', 'tfa_persistent_key', 'tfa_onetime_key', 'email_notified', 'dismissed_global_notifications',
    'register_ip', 'login_ip', 'register_multiaccount_hash', 'login_multiaccount_hash',
    'referral', 'referral_wager_obtained', 'referral_bonus_obtained', 'promocode_limit_reset', 'promocode_limit',
    'bot',

    'fb', 'google', 'steam',

    'btc', 'ltc', 'eth', 'doge', 'bch', 'trx', 'algo', 'btg', 'celo', 'dash', 'eos', 'xrp', 'xlm', 'xtz', 'wbtc', 'zec', 'sol', 'sol_bones',
    'demo_btc', 'demo_ltc', 'demo_eth', 'demo_doge', 'demo_bch', 'demo_trx', 'demo_algo', 'demo_btg', 'demo_celo', 'demo_dash',
    'demo_eos', 'demo_xrp', 'demo_xlm', 'demo_xtz', 'demo_wbtc', 'demo_zec', 'demo_sol', 'demo_sol_bones',

    'wallet_native_btc', 'wallet_native_ltc', 'wallet_native_eth', 'wallet_native_doge', 'wallet_native_bch', 'wallet_native_trx',
    'wallet_bg_btc', 'wallet_bg_bch', 'wallet_bg_trx', 'wallet_bg_eos', 'wallet_bg_eth', 'wallet_bg_ltc',
    'wallet_bg_algo', 'wallet_bg_btg', 'wallet_bg_celo', 'wallet_bg_dash', 'wallet_bg_eos', 'wallet_bg_xrp', 'wallet_bg_xlm',
    'wallet_bg_xtz', 'wallet_bg_wbtc', 'wallet_bg_zec', 'wallet_native_sol', 'wallet_native_sol_fee', 'wallet_native_sol_bones',
    'wallet_trx_private_key'
  ];

  /**
   * Some of the attributes should be hidden even for account owners.
   * @var array
   */
  public $alwaysHidden = [
    'register_multiaccount_hash', 'login_multiaccount_hash', 'register_ip', 'login_ip', 'wallet_trx_private_key', 'password', 'remember_token',
  ];

  /**
   * The attributes that should be cast to native types.
   * @var array
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'bonus_claim' => 'datetime',
    'mute' => 'datetime',
    'latest_activity' => 'datetime',
    'promocode_limit_reset' => 'datetime',
    'tfa_persistent_key' => 'datetime',
    'tfa_onetime_key' => 'datetime',
    'ignore' => 'json',
    'name_history' => 'json',
    'referral_wager_obtained' => 'json',
    'favoriteGames' => 'json',
    'roles' => 'json',
    'withdraw_limit_reset' => 'datetime'
  ];

  /**
   * @param Permission $permission
   * @param string $checkType edit / delete / create / active
   * @return bool
   */
  public function checkPermission(Permission $permission, string $checkType = 'active'): bool {
    if ($checkType != 'active' && !$this->checkPermission($permission)) return false; // If no main permission - refuse to accept sub-permissions

    foreach ($this->roles as $role) {
      $dbRole = Role::fromId($role['id']);
      if ($dbRole == null) continue;

      if ($dbRole->id === '*') return true;

      foreach ($dbRole->permissions as $rolePermission) {
        if ($rolePermission['id'] !== $permission->id()) continue;
        if ($rolePermission['permissions'][$checkType] == true) return true;
      }
    }

    return false;
  }

  public function addRole(Role $role) {
    $roles = $this->roles;

    $roles[] = [
      'id' => $role->id
    ];

    $this->update([
      'roles' => $roles
    ]);
  }

  public function deleteRole(Role $role) {
    $roles = $this->roles;
    if(is_string($roles)) $roles = json_decode($roles);

    $this->update([
      'roles' => array_filter($roles, function($a) use ($role) {
        return $a['id'] !== $role->id;
      })
    ]);
  }

  public function wagered() {
    return DB::table('games')->where('user', $this->_id)
      ->where('demo', '!=', true)
      ->where('status', '!=', 'in-progress')
      ->where('status', '!=', 'cancelled')
      ->sum('bet_usd_converted');
  }

  public function deposited() {
    return DB::table('invoices')
      ->where('status', 1)
      ->where('user', $this->_id)
      ->sum('usd_converted');
  }

  public function depositedThisMonth() {
    return DB::table('invoices')
      ->where('status', 1)
      ->where('user', $this->_id)
      ->where('created_at', '>=', Carbon::now()->firstOfMonth())
      ->sum('usd_converted');
  }

  public function bonus(): ?BonusBalance {
    $balance = isset($_COOKIE['useBonus']) ?
      ($_COOKIE['useBonus'] === 'null' ? null : BonusBalance::where('_id', $_COOKIE['useBonus'])->first()) : null;
    if($balance !== null && $balance->user !== $this->_id
      && $this->clientCurrency()->walletId() === $balance->currency) return null;
    return $balance;
  }

  public function hasRole(Role $role): bool {
    return count(array_filter($this->roles, function($a) use($role) {
        return $role->id === $a['id'];
      })) > 0;
  }

  public static function getIp(bool $fetchServerIp = true) {
    $rIp = null;

    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
      if (array_key_exists($key, $_SERVER) === true) {
        foreach (explode(',', $_SERVER[$key]) as $ip) {
          $ip = trim($ip);
          if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            $rIp = $ip;
            break;
          }
        }
      }
    }

    if($rIp === null) $rIp = request()->ip();
    if($fetchServerIp && ($rIp === '::1' || $rIp === '127.0.0.1' || $rIp === 'localhost')) return self::getServerIp();
    return $rIp;
  }

  public static function getServerIp(): string {
    return str_replace("\n", "", file_get_contents('https://checkip.amazonaws.com/'));
  }

  public function isDismissed(GlobalNotification $globalNotification) {
    return in_array($globalNotification->_id, $this->dismissed_global_notifications ?? []);
  }

  public function dismiss(GlobalNotification $globalNotification) {
    $array = $this->$globalNotification->dismissed_global_notifications ?? [];
    array_push($array, $globalNotification->_id);
    $this->update([
      'dismissed_global_notifications' => $array
    ]);
  }

  public function notifications() {
    return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
  }

  public function balance(Currency $currency): UserBalance {
    return new UserBalance($this, $currency);
  }

  public function clientCurrency(): Currency {
    return Currency::find($_COOKIE['currency'] ?? Currency::all()[0]->id()) ?? Currency::all()[0];
  }

  public function depositWallet(Currency $currency, bool $isFeeWallet = false): string {
    $key = 'wallet_' . $currency->id() . ($isFeeWallet ? '_fee' : '');
    $wallet = $this->makeVisible($key)->toArray()[$key] ?? null;

    if ($wallet == null) {
      $wallet = $currency->newWalletAddress();
      if ($wallet !== 'Error') $this->update([
        $key => $wallet
      ]);
    }
    return $wallet;
  }

  public function total(Currency $currency) {
    return DB::table('games')->where('user', $this->_id)->where('currency', $currency->id())->where('demo', '!=', true)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->sum('wager');
  }

  public function games() {
    return DB::table('games')->where('user', $this->_id)->where('status', '!=', 'cancelled')->where('status', '!=', 'in-progress')->where('demo', '!=', true)->count();
  }

  public function vipData(): array {
    $data = [];

    foreach (Currency::all() as $currency) {
      $data[] = [
        'id' => $currency->id(),
        'wager' => DB::table('games')->where('user', $this->_id)->where('currency', $currency->id())->where('multiplier', '!=', 1)->where('demo', '!=', true)->where('status', '!=', 'in-progress')->where('status', '!=', 'cancelled')->sum('wager')
      ];
    }

    return $data;
  }

  public function vipLevel(): int {
    $level = 0;
    $wagered = $this->wagered();
    $deposited = $this->deposited();
    $depositedThisMonth = $this->depositedThisMonth();

    for($i = 0; $i <= 10; $i++) {
      $data = (new VIP())->level($i);
      if($wagered >= $data->wagerRequirement && $deposited >= $data->depositRequirement && $depositedThisMonth >= $data->levelProtection)
        $level = $i;
    }

    return $this->forced_vip !== null ? $this->forced_vip : $level;
  }

  public function vipBonus(): float {
    return floatval($this->clientCurrency()->option('weekly_bonus')) * $this->vipLevel();
  }

  public function tfaInstance(): TwoFactorAuth {
    return new TwoFactorAuth('axes.bet/' . $this->name);
  }

  public function validate2FA(bool $persist): bool {
    $token = $persist ? ($this->tfa_persistent_key ?? null) : ($this->tfa_onetime_key ?? null);
    return ($this->tfa_enabled ?? false) === false || ($token != null && !$token->isPast());
  }

  public function reset2FAOneTimeToken() {
    $this->update(['tfa_onetime_key' => null]);
  }

  public function createToken(array $abilities = ['*']) {
    $token = $this->tokens()->create([
      'name' => $this->_id,
      'token' => hash('sha256', $plainTextToken = Str::random(80)),
      'abilities' => $abilities,
    ]);

    return new NewAccessToken($token, $token->id . '|' . $plainTextToken);
  }

}

class UserBalance {

  private User $user;
  private Currency $currency;
  private bool $quiet = false;
  private bool $demo = false;

  private float $minValue = 0.00000000;

  public function __construct(User $user, Currency $currency) {
    $this->user = $user;
    $this->currency = $currency;
  }

  public function quiet() {
    $this->quiet = true;
    return $this;
  }

  public function demo($set = true) {
    $this->demo = $set;
    return $this;
  }

  public function get(): float {
    $value = floatval(($this->user->{$this->getColumn()} ?? new Decimal128($this->minValue))->jsonSerialize()['$numberDecimal']);
    return $value < 0 ? 0 : floatval(number_format($value, $this->currency->walletId() === 'rub' ? 2 : 8, '.', ''));
  }

  private function getColumn() {
    return $this->demo ? 'demo_' . $this->currency->walletId() : $this->currency->walletId();
  }

  public function add(float $amount, array $transaction = null, int $delay = 0, string $serviceId = null, string $serviceType = null) {
    $this->user->update([
      $this->getColumn() => new Decimal128(strval($this->get() + $amount))
    ]);

    if ($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'add', $this->demo, $amount, $delay));
    return Transaction::create([
      'user' => $this->user->_id,
      'demo' => $this->demo,
      'currency' => $this->currency->id(),
      'new' => $this->get(),
      'old' => $this->get() - $amount,
      'amount' => $amount,
      'quiet' => $this->quiet,
      'data' => $transaction ?? [],
      'service_id' => $serviceId,
      'service_type' => $serviceType
    ]);
  }

  public function subtract(float $amount, array $transaction = null, string $serviceId = null, string $serviceType = null) {
    $value = $this->get() - $amount;
    if ($value < 0) $value = 0;
    $this->user->update([
      $this->getColumn() => new Decimal128(strval($value))
    ]);

    if ($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'subtract', $this->demo, $amount, 0));
    return Transaction::create([
      'user' => $this->user->_id,
      'demo' => $this->demo,
      'currency' => $this->currency->id(),
      'new' => $this->get(),
      'old' => $this->get() + $amount,
      'amount' => -$amount,
      'quiet' => $this->quiet,
      'data' => $transaction ?? [],
      'service_id' => $serviceId,
      'service_type' => $serviceType
    ]);
  }

  public function createBonus(float $amount, string $description, $wagerRequirementMultiplier = 20) {
    BonusBalance::create([
      'user' => $this->user->_id,
      'currency' => $this->currency->walletId(),
      'originalValue' => $amount,
      'value' => $amount,
      'wagered' => 0,
      'neededToWager' => $amount * $wagerRequirementMultiplier,
      'description' => $description
    ]);
  }

  public function addBonus(float $amount, BonusBalance $balance, int $delay = 0) {
    $balance->update([
      'value' => $balance->value + $amount
    ]);

    if($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'add', false, $amount, $delay, $balance));
  }

  public function subtractBonus(float $amount, BonusBalance $balance, int $delay = 0) {
    $balance->update([
      'value' => $balance->value - $amount
    ]);

    if($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'subtract', false, $amount, $delay, $balance));
  }

  public function addBonusWager(float $wagered, BonusBalance $balance) {
    $balance->update([
      'wagered' => $balance->wagered + $wagered
    ]);

    if($balance->wagered >= $balance->neededToWager) {
      $this->add($balance->value, Transaction::builder()->message('Bonus (' . $balance->description . ') x' . number_format($balance->neededToWager / $balance->originalValue, 2, '.', ''))->get());
      event(new BonusBalanceTransferred($this->user, $balance));
      $balance->delete();
    }
  }

  public function bonusBalances(): array {
    return BonusBalance::orderBy('value', 'desc')->where('user', $this->user->_id)->get()->toArray();
  }

}
