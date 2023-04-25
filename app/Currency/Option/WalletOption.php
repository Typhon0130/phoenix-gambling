<?php namespace App\Currency\Option;

abstract class WalletOption {

    abstract function id();

    abstract function name(): string;

    abstract function description(): string;

    function readOnly(): bool {
        return false;
    }

}
