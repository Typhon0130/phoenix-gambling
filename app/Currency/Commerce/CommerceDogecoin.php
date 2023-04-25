<?php namespace App\Currency\Commerce;

class CommerceDogecoin extends CommerceCurrency {

  public function id(): string {
    return "commerce_dogecoin";
  }

  public function walletId(): string {
    return "doge";
  }

  function name(): string {
    return "DOGE";
  }

  public function alias(): string {
    return "dogecoin";
  }

  public function displayName(): string {
    return "Dogecoin";
  }

  function icon(): string {
    return "dogecoin";
  }

  public function style(): string {
    return "#c2a633";
  }

  public function coinbaseId(): string {
    return 'DOGE';
  }

  public function coinbaseName(): string {
    return 'dogecoin';
  }

}
