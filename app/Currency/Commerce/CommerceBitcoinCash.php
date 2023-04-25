<?php namespace App\Currency\Commerce;

class CommerceBitcoinCash extends CommerceCurrency {

  function id(): string {
    return "commerce_bitcoincash";
  }

  public function walletId(): string {
    return "bch";
  }

  function name(): string {
    return "BCH";
  }

  function icon(): string {
    return "bch";
  }

  public function alias(): string {
    return 'bitcoin-cash';
  }

  public function displayName(): string {
    return "Bitcoin Cash";
  }

  function style(): string {
    return "#8dc351";
  }

  public function coinbaseId(): string {
    return 'BCH';
  }

  public function coinbaseName(): string {
    return 'bitcoincash';
  }

}
