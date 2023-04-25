<?php namespace App\Currency\Chain;

use App\Models\User;

abstract class CurrencyChain {

  abstract function id(): string;

  abstract function name(): string;

  abstract function newWalletAddress(?User $user): ?string;

}
