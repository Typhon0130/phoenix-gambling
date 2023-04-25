<?php namespace App\Currency\Commerce;

class CommerceShibaInu extends CommerceCurrency {

  function id(): string {
    return 'commerce_shibainu';
  }

  function walletId(): string {
    return 'shib';
  }

  function name(): string {
    return "SHIB";
  }

  function alias(): string {
    return "shiba-inu";
  }

  function displayName(): string {
    return "SHIB";
  }

  function icon(): string {
    return "shib";
  }

  function style(): string {
    return '#FFA409';
  }

  public function coinbaseId(): string {
    return 'SHIB';
  }

  public function coinbaseName(): string {
    return 'shibainu';
  }

}
