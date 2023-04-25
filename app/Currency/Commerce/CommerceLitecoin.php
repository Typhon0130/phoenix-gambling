<?php namespace App\Currency\Commerce;

class CommerceLitecoin extends CommerceCurrency {

  function id(): string {
    return "commerce_litecoin";
  }

  public function walletId(): string {
    return "ltc";
  }

  function name(): string {
    return "LTC";
  }

  public function alias(): string {
    return 'litecoin';
  }

  public function displayName(): string {
    return "Litecoin";
  }

  function icon(): string {
    return "ltc";
  }

  public function style(): string {
    return "#bfbbbb";
  }

  public function coinbaseId(): string {
    return 'LTC';
  }

  public function coinbaseName(): string {
    return 'litecoin';
  }

}
