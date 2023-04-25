<?php namespace App\Currency\Token;

use App\Currency\Chain\EthereumChain;
use App\Currency\Chain\PolygonChain;
use App\Currency\Commerce\Utils\CoinbaseCommerce;
use App\Models\User;

class TokenUSDC extends TokenCurrency {

  function id(): string {
    return "token_usdc";
  }

  function walletId(): string {
    return 'usdc';
  }

  function name(): string {
    return 'USDC';
  }

  function alias(): string {
    return 'usd-coin';
  }

  function displayName(): string {
    return 'USDC';
  }

  function icon(): string {
    return 'usdc';
  }

  function style(): string {
    return '#2775ca';
  }

  public function chains(): array {
    return [
      new class extends EthereumChain {
        function newWalletAddress(?User $user): ?string {
          return '?????';
          //return CoinbaseCommerce::generateWalletAddressForNonCommerceCurrency(new TokenUSDC(), 'usdc', $user);
        }
      }/*,
      new class extends PolygonChain {
        function newWalletAddress(?User $user): ?string {
          return CoinbaseCommerce::generateWalletAddressForNonCommerceCurrency(new TokenUSDC(), 'pusdc', $user);
        }
      }*/
    ];
  }

}
