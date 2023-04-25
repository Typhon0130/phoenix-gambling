<?php namespace App\Currency\Token;

use App\Currency\Currency;
use App\Models\User;

abstract class TokenCurrency extends Currency {

  function isRunning(): bool {
    return true;
  }

  function process(string $wallet = null): string {
    throw new \Exception('Unsupported operation');
  }

  function send(string $from, string $to, float $sum) {}

  function setupWallet() {}

  function coldWalletBalance(): float {
    return -1;
  }

  public function isToken(): bool {
    return true;
  }

  public function decimals(): int {
    return 2;
  }

  protected function options(): array {
    return [];
  }

  public function newWalletAddress(?User $user, ?string $chainId = null): string {
    return $this->generateAddressFromChain($this, $user, $chainId);
  }

}
