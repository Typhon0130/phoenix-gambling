<?php namespace App\Currency\Commerce\Utils;

use App\Currency\Commerce\CommerceCurrency;
use App\Currency\Currency;
use App\Currency\Token\TokenUSDC;
use App\Currency\Token\TokenUSDT;
use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\License\License;
use App\Models\CommerceCharge;
use App\Models\Settings;
use App\Models\User;

class CoinbaseCommerce {

  public static function generateWalletAddress(Currency $currency, ?User $user): string {
    $charge = CommerceCharge::where('user', $user->_id)->where('currency', $currency->id())->where('gotPayment', '!=', true)->first();
    if($charge != null) return $charge->address;

    $json = json_decode((new PhoenixGame())->curl('https://phoenix-gambling.com/license/commerce/charge/create', [
      'license' => (new License())->getKey(),
      'commerceApiKey' => Settings::get('[Coinbase Commerce] API Key', '')
    ]), true);

    $foundOne = false;

    foreach (Currency::getAllSupportedCoins() as $c) {
      if(!($c instanceof CommerceCurrency)) continue;

      if(isset($json['addresses'][$c->coinbaseName()])) {
        CommerceCharge::create([
          'user' => $user->_id,
          'currency' => $c->id(),
          'address' => $json['addresses'][$c->coinbaseName()],
          'code' => $json['code']
        ]);

        $foundOne = true;
      }
    }

    return $foundOne ? self::generateWalletAddress($currency, $user) : 'Error';
  }

  public static function generateWalletAddressForNonCommerceCurrency(Currency $currency, string $coinbaseName, ?User $user): string {
    $charge = CommerceCharge::where('user', $user->_id)->where('currency', $currency->id())->where('gotPayment', '!=', true)->first();
    if($charge != null) return $charge->address;

    $json = json_decode((new PhoenixGame())->curl('https://phoenix-gambling.com/license/commerce/charge/create', [
      'license' => (new License())->getKey(),
      'commerceApiKey' => Settings::get('[Coinbase Commerce] API Key', '')
    ]), true);

    if(isset($json['addresses'][$coinbaseName])) {
      CommerceCharge::create([
        'user' => $user->_id,
        'currency' => $currency->id(),
        'address' => $json['addresses'][$coinbaseName],
        'code' => $json['code']
      ]);

      return $json['addresses'][$coinbaseName];
    }

    return 'Error';
  }

  public static function handle(array $payments): void {
    foreach ($payments as $payment) {
      $currency = self::findByCoinbaseId($payment['currency']);
      if(!$currency) continue;

      $charge = CommerceCharge::where('code', $payment['code'])->where('currency', $currency->id())->first();
      if(!$charge) continue;

      $user = User::where('_id', $charge->user)->first();
      if(!$user) continue;

      if($currency->acceptThirdParty($payment['status'] === 'CONFIRMED' ? $payment['confirmations_required'] : 0, $user, $payment['id'], floatval($payment['amount']), $payment['confirmations_required'])) {
          CommerceCharge::where('code', $payment['code'])->update([
            'gotPayment' => true
          ]);
      }
    }
  }

  private static function findByCoinbaseId(string $coinbaseId): ?Currency {
    switch($coinbaseId) {
      case "PUSDC":
      case "USDC":
        return new TokenUSDC();
      case "USDT":
        return new TokenUSDT();
    }

    foreach (Currency::getAllSupportedCoins() as $currency) {
      if(!($currency instanceof Currency)) continue;
      if($currency->coinbaseId() === $coinbaseId) return $currency;
    }

    return null;
  }

}
