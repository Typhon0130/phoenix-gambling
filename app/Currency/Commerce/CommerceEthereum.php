<?php namespace App\Currency\Commerce;

class CommerceEthereum extends CommerceCurrency {

  function id(): string {
    return "commerce_ethereum";
  }

  public function walletId(): string {
    return "eth";
  }

  function name(): string {
    return "ETH";
  }

  public function alias(): string {
    return "ethereum";
  }

  public function displayName(): string {
    return "Ethereum";
  }

  function icon(): string {
    return "eth";
  }

  public function style(): string {
    return "#627eea";
  }

  public function coinbaseId(): string {
    return 'ETH';
  }

  public function coinbaseName(): string {
    return 'ethereum';
  }

}
