<?php namespace App\Currency\Commerce;

class CommerceBitcoin extends CommerceCurrency {

  function id(): string {
    return "commerce_bitcoin";
  }

  public function walletId(): string {
    return "btc";
  }

  function name(): string {
    return "BTC";
  }

  public function alias(): string {
    return "bitcoin";
  }

  public function displayName(): string {
    return "Bitcoin";
  }

  function icon(): string {
    return "btc-icon";
  }

  function style(): string {
    return "#f7931a";
  }

  public function coinbaseId(): string {
    return 'BTC';
  }

  public function coinbaseName(): string {
    return 'bitcoin';
  }

}
