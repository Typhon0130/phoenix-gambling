<?php namespace App\Permission;

class WalletPermission extends Permission {

    public function id(): string {
        return "wallet";
    }

    public function name(): string {
        return "Wallet";
    }

    public function description(): string {
        return "Allows control over funds, settings can be changed if allowed to edit.";
    }

    public function isEditable(): bool {
        return true;
    }

    public function isDeletable(): bool {
        return false;
    }

    public function isCreatable(): bool {
        return false;
    }

}
