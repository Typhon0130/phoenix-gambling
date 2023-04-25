<?php namespace App\Currency;

use App\Currency\Chain\CurrencyChain;
use App\Currency\Native\Solana;
use App\Currency\Option\WalletOption;
use App\Events\Deposit;
use App\Models\Invoice;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\Decimal128;

abstract class Currency {

  abstract function id(): string;

  abstract function walletId(): string;

  abstract function name(): string;

  abstract function alias(): string;

  abstract function displayName(): string;

  abstract function icon(): string;

  abstract function style(): string;

  abstract function newWalletAddress(?User $user, ?string $chainId = null): string;

  protected abstract function options(): array;

  public function minBet(): float {
    return 0.00000001;
  }

  public function tokenPrice($fiat = 'usd'): float {
    try {
      if (!Cache::has('conversions:' . $this->alias()))
        Cache::put('conversions:' . $this->alias(), file_get_contents("https://api.coingecko.com/api/v3/coins/{$this->alias()}?localization=false&market_data=true"), now()->addHours(1));
      $json = json_decode(Cache::get('conversions:' . $this->alias()), true);
      return $json['market_data']['current_price'][$fiat];
    } catch (\Exception $e) {
      return 0;
    }
  }

  public function convertUSDToToken(float $usdAmount) {
    return $usdAmount / $this->tokenPrice('usd');
  }

  public function convertTokenToUSD(float $tokenAmount) {
    return $tokenAmount * $this->tokenPrice('usd');
  }

  public function convertEURToToken(float $eurAmount) {
    return $eurAmount / $this->tokenPrice('eur');
  }

  public function convertTokenToEUR(float $tokenAmount) {
    return $tokenAmount * $this->tokenPrice('eur');
  }

  public function convertTokenToFiat(float $tokenAmount, string $fiat = 'usd') {
    return $tokenAmount * $this->tokenPrice($fiat);
  }

  public function convertFiatToToken(float $fiatAmount, string $fiat = 'usd') {
    return $fiatAmount / $this->tokenPrice($fiat);
  }

  public function fiatNumberFormat(float $value): float {
    return floatval(number_format($value, 2, '.', ''));
  }

  public function getBotBet() {
    return $this->randomBotBet($this->convertUSDToToken(1), $this->convertUSDToToken(25));
  }

  public function isEnabled(): bool {
    $currencies = json_decode(\App\Models\Settings::get('currencies', '["commerce_btc"]'));
    return in_array($this->id(), $currencies);
  }

  /**
   * Gets random bet value. Higher values are less common.
   * @param $min
   * @param $max
   * @return mixed
   */
  protected function randomBotBet(float $min, float $max) {
    try {
      $diff = 100000000;
      return min(mt_rand($min * $diff, $max * $diff) / $diff, mt_rand($min * $diff, $max * $diff) / $diff);
    } catch (\Exception $e) {
      return $this->randomBotBet(1, 100);
    }
  }

  public function url(): ?string {
    return null;
  }

  /**
   * @return array<CurrencyChain>
   */
  public function chains(): array {
    return [];
  }

  public static function findChain(Currency $currency, ?string $chainId): ?CurrencyChain {
    foreach ($currency->chains() as $chain) {
      if ($chain->id() === $chainId) return $chain;
    }
    return null;
  }

  protected static function generateAddressFromChain(Currency $currency, ?User $user, ?string $chainId): string {
    try {
      return self::findChain($currency, $chainId)->newWalletAddress($user);
    } catch (\Exception) {
      return 'Error';
    }
  }

  /** @return WalletOption[] */
  public function getOptions(): array {
    $defaultOptions = [
      new class extends WalletOption {
        public function id(): string {
          return "minWithdraw";
        }

        public function name(): string {
          return "Min. withdrawal (USD)";
        }

        public function description(): string {
          return "Minimal withdrawal order";
        }
      },
      new class extends WalletOption {
        function id(): string {
          return "demo";
        }

        function name(): string {
          return "Free demo balance";
        }

        public function description(): string {
          return "Free demo balance.\nDemo balance cannot be withdrawn or converted into real balance.";
        }
      },
      new class extends WalletOption {
        function id() {
          return 'high_roller_requirement';
        }

        function name(): string {
          return 'Min. bet amount to appear in "High Rollers"';
        }

        public function description(): string {
          return "";
        }
      },
      new class extends WalletOption {
        public function id() {
          return 'quiz';
        }

        public function name(): string {
          return 'Trivia answer reward';
        }

        function description(): string {
          return "Reward amount for correctly answering trivia question";
        }
      },
      new class extends WalletOption {
        public function id() {
          return 'rain';
        }

        public function name(): string {
          return 'Rain reward amount';
        }

        function description(): string {
          return "Rain is a free reward available to random people who deposited any amount in the last 24 hours.";
        }
      },
      new class extends WalletOption {
        public function id() {
          return 'referral_bonus';
        }

        public function name(): string {
          return 'Active referral reward';
        }

        function description(): string {
          return "Referrers are rewarded if their affiliate is active";
        }
      }
    ];

    if(str_starts_with($this->id(), "native_")) {
      $defaultOptions[] = new class extends WalletOption {
        function id(): string {
          return "confirmations";
        }

        function name(): string {
          return "Required confirmations";
        }

        public function description(): string {
          return "Deposit will be accepted after X number of confirmations";
        }
      };
    }

    return array_merge($this->options(), $defaultOptions);
  }

  public function option(string $key, string $value = null): string {
    if ($value == null) {
      if (Cache::has('currency:' . $this->walletId() . ':' . $key)) $val = json_decode(Cache::get('currency:' . $this->walletId() . ':' . $key), true)[$key] ?? '1';
      else $val = \App\Models\Currency::where('currency', $this->walletId())->first()->data[$key] ?? '1';

      if (str_starts_with($val, "$")) {
        $val = floatval(substr($val, 1));
        return strval($this->convertUSDToToken($val));
      }

      return $val;
    }

    $data = \App\Models\Currency::where('currency', $this->walletId())->first();

    if (!$data) $data = \App\Models\Currency::create(['currency' => $this->walletId(), 'data' => []]);

    $data = $data->data;
    $data[$key] = $value;

    \App\Models\Currency::where('currency', $this->walletId())->first()->update([
      'data' => $data
    ]);

    Cache::forget('currency:' . $this->walletId() . ':' . $key);
    Cache::put('currency:' . $this->walletId() . ':' . $key, json_encode($data), now()->addYear());
    return $value;
  }

  abstract function isRunning(): bool;

  /**
   * @param string|null $wallet Null for every transaction except local nodes
   * @return string
   */
  abstract function process(string $wallet = null): string;

  abstract function send(string $from, string $to, float $sum);

  abstract function setupWallet();

  abstract function coldWalletBalance(): float;

  public function isToken(): bool {
    return false;
  }

  public function decimals(): int {
    return 8;
  }

  /**
   * @param array<Currency> $array
   * @return array
   */
  public static function toCurrencyArray(array $array) {
    $currency = [];
    $isGuest = auth('sanctum')->guest();

    foreach ($array as $c) {
      $balance = $isGuest ? null : auth('sanctum')->user()->balance($c);

      $chains = [];

      foreach ($c->chains() as $chain)
        $chains[] = [
          'id' => $chain->id(),
          'name' => $chain->name()
        ];

      $currency = array_merge($currency, [
        $c->id() => [
          'id' => $c->id(),
          'walletId' => $c->walletId(),
          'name' => $c->name(),
          'displayName' => $c->displayName(),
          'icon' => $c->icon(),
          'style' => $c->style(),
          'price' => $c->tokenPrice(),
          'requiredConfirmations' => intval($c->option('confirmations')),
          'highRollerRequirement' => floatval($c->option('high_roller_requirement')),
          'minWithdraw' => floatval($c->option('minWithdraw')),
          'decimals' => $c->decimals(),
          'balance' => [
            'real' => $isGuest ? null : $balance->get(),
            'demo' => $isGuest ? null : $balance->demo(true)->get()
          ],
          'chains' => $chains
        ]
      ]);
    }

    return $currency;
  }

  /**
   * @return array<Currency>
   */
  public static function getAllSupportedCoins(): array {
    return [
      new Native\Ethereum(),

      new Commerce\CommerceBitcoin(),
      new Commerce\CommerceEthereum(),
      new Commerce\CommerceLitecoin(),
      new Commerce\CommerceBitcoinCash(),
      new Commerce\CommerceDogecoin(),
      new Commerce\CommerceDAI(),
      new Commerce\CommerceShibaInu(),
      new Commerce\CommercePolygonMatic(),
      new Commerce\CommerceApe(),

      new Token\TokenUSDC(),
      new Token\TokenUSDT(),

      new Native\Bitcoin(),
      new Native\Litecoin(),
      new Native\Dogecoin(),
      new Native\BitcoinCash(),
      new Native\Solana()
    ];
  }


  /**
   * @param bool $includeTokens
   * @return array<Currency>
   */
  public static function all(bool $includeTokens = true): array {
    $currencies = json_decode(Settings::get('currencies', '["commerce_btc"]'));
    $result = [];

    foreach ($currencies as $currency) {
      try {
        $currencyInstance = Currency::find($currency);
        if (!$includeTokens && $currencyInstance->isToken()) continue;

        $result[] = $currencyInstance;
      } catch (\Exception) {}
    }

    return $result;
  }

  public static function getByWalletId($walletId): array {
    $result = [];
    foreach (self::getAllSupportedCoins() as $coin) if ($coin->walletId() === $walletId) $result[] = $coin;
    return $result;
  }

  public static function getByName($name): array {
    $result = [];
    foreach (self::getAllSupportedCoins() as $coin) if ($coin->name() === $name) $result[] = $coin;
    return $result;
  }

  public static function find(string $id): ?Currency {
    foreach (self::getAllSupportedCoins() as $currency) if ($currency->id() == $id) {
      if (\App\Models\Currency::where('currency', $currency->id())->first() == null) {
        \App\Models\Currency::create([
          'currency' => $currency->id(),
          'data' => []
        ]);
      }
      return $currency;
    }
    return null;
  }

  public function acceptThirdParty(int $confirmations, User $user, string $id, float $sum, int $requiredConfirmations): bool {
    if ($user == null) return false;

    $invoice = Invoice::where('id', $id)->first();
    if ($invoice == null) {
      $invoice = Invoice::create([
        'user' => $user->_id,
        'sum' => new Decimal128($sum),
        'type' => 'currency',
        'currency' => $this->id(),
        'id' => $id,
        'confirmations' => $confirmations,
        'status' => 0,
        'usd_converted' => $this->convertTokenToUSD($sum)
      ]);
      event(new Deposit($user, $this, $sum));
    } else $invoice->update([
      'confirmations' => $confirmations
    ]);

    if ($invoice->status == 0 && $invoice->confirmations >= $requiredConfirmations) {
      $invoice->update(['status' => 1]);
      $user->balance($this)->add($sum, Transaction::builder()->message('Deposit')->get());

      if ($user->referral) {
        $referrer = User::where('_id', $user->referral)->first();

        $commissionPercent = 0;

        switch ($referrer->vipLevel()) {
          case 0:
            $commissionPercent = 1;
            break;
          case 1:
            $commissionPercent = 2;
            break;
          case 2:
            $commissionPercent = 3;
            break;
          case 3:
            $commissionPercent = 5;
            break;
          case 4:
            $commissionPercent = 10;
            break;
          case 5:
            $commissionPercent = 20;
            break;
        }

        if ($commissionPercent !== 0) {
          $commission = ($commissionPercent * $sum) / 100;
          $referrer->balance($this)->add($commission, Transaction::builder()->message('Affiliate commission (' . $commissionPercent . '% from ' . $sum . ' .' . $this->name() . ')')->get());
        }
      }
    }

    return true;
  }

  public function accept(int $confirmations, string $wallet, string $id, float $sum): bool {
    $user = User::where('wallet_' . $this->id(), $wallet)->first();
    if ($user == null) return false;

    $invoice = Invoice::where('id', $id)->first();
    if ($invoice == null) {
      $invoice = Invoice::create([
        'user' => $user->_id,
        'sum' => new Decimal128($sum),
        'type' => 'currency',
        'currency' => $this->id(),
        'id' => $id,
        'confirmations' => $confirmations,
        'status' => 0,
        'usd_converted' => $this->convertTokenToUSD($sum)
      ]);
      event(new Deposit($user, $this, $sum));
    } else $invoice->update([
      'confirmations' => $confirmations
    ]);

    if ($invoice->status == 0 && $invoice->confirmations >= intval($this->option('confirmations'))) {
      $invoice->update(['status' => 1]);
      $user->balance($this)->add($sum, Transaction::builder()->message('Deposit')->get());

      $transferWallet = $this->option('transfer_address');
      $this->send($wallet, $transferWallet, $sum);

      if ($user->referral) {
        $referrer = User::where('_id', $user->referral)->first();

        $commissionPercent = 0;

        switch ($referrer->vipLevel()) {
          case 0:
            $commissionPercent = 1;
            break;
          case 1:
            $commissionPercent = 2;
            break;
          case 2:
            $commissionPercent = 3;
            break;
          case 3:
            $commissionPercent = 5;
            break;
          case 4:
            $commissionPercent = 10;
            break;
          case 5:
            $commissionPercent = 20;
            break;
        }

        if ($commissionPercent !== 0) {
          $commission = ($commissionPercent * $sum) / 100;
          $referrer->balance($this)->add($commission, Transaction::builder()->message('Affiliate commission (' . $commissionPercent . '% from ' . $sum . ' .' . $this->name() . ')')->get());
        }
      }
    }

    return true;
  }

}
