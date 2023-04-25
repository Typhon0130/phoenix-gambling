<?php namespace App\Currency\Token;

use App\Currency\Chain\EthereumChain;
use App\Currency\Commerce\Utils\CoinbaseCommerce;
use App\Models\User;

class TokenUSDT extends TokenCurrency {

  public function id(): string {
    return "token_usdt";
  }

  public function walletId(): string {
    return "tether";
  }

  public function name(): string {
    return "USDT";
  }

  public function alias(): string {
    return "tether";
  }

  public function displayName(): string {
    return "USDT";
  }

  public function icon(): string {
    return "tether";
  }

  public function style(): string {
    return "lightgreen";
  }

  public function chains(): array {
    return [
      new class extends EthereumChain {
        function newWalletAddress(?User $user): ?string {
          return CoinbaseCommerce::generateWalletAddressForNonCommerceCurrency(new TokenUSDT(), 'tether', $user);
        }
      }
    ];
  }

}
