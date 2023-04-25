<?php namespace App\Currency\Local;

use App\Currency\Currency;
use App\Currency\CurrencyTransactionResult;

abstract class LocalCurrency extends Currency {

  function style(): string {
    return 'lightgray';
  }

  function newWalletAddress(?\App\Models\User $user, ?string $chainId = null): string {
    return 'Unsupported operation';
  }

  function isRunning(): bool {
    return true;
  }

  function process(string $wallet = null): string {
    return CurrencyTransactionResult::$invalidCurrency;
  }

  function send(string $from, string $to, float $sum) {}

  function setupWallet() {}

  function coldWalletBalance(): float {
    return 0;
  }

  public function minBet(): float {
    return 0.01;
  }

  public function decimals(): int {
    return 2;
  }

}
