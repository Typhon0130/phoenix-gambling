<?php namespace App\Currency\Commerce;

class CommercePolygonMatic extends CommerceCurrency {

  function id(): string {
    return 'commerce_polygon:matic';
  }

  function walletId(): string {
    return 'matic';
  }

  function name(): string {
    return 'MATIC';
  }

  function alias(): string {
    return 'matic-network';
  }

  function displayName(): string {
    return 'MATIC';
  }

  function icon(): string {
    return 'matic';
  }

  function style(): string {
    return '#8247E5';
  }

  public function coinbaseId(): string {
    return "PMATIC";
  }

  public function coinbaseName(): string {
    return "polygon";
  }

}
