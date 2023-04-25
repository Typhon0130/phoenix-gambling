<?php namespace App\Currency\Commerce;

use App\Currency\Commerce\Utils\CoinbaseCommerce;
use App\Currency\Currency;
use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\License\License;
use App\Models\CommerceCharge;
use App\Models\Settings;

abstract class CommerceCurrency extends Currency {

  abstract function coinbaseId(): string;

  abstract function coinbaseName(): string;

  function newWalletAddress(?\App\Models\User $user, ?string $chainId = null): string {
    return CoinbaseCommerce::generateWalletAddress($this, $user);
  }

  protected function options(): array {
    return [];
  }

  function isRunning(): bool {
    return true;
  }

  function process(string $wallet = null): string {
    throw new \Error('Unsupported operation');
  }

  function send(string $from, string $to, float $sum) {
    throw new \Error('Unsupported operation');
  }

  function setupWallet() {
    throw new \Error('Unsupported operation');
  }

  function coldWalletBalance(): float {
    return -1;
  }

  public function url(): ?string {
    return null;
  }

}
