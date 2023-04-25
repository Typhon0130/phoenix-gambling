<?php namespace App\Permission;

class WithdrawsPermission extends Permission {

    public function id(): string {
        return "withdraws";
    }

    public function name(): string {
        return "Withdraws & Deposits";
    }

    public function description(): string {
        return "User withdraw requests and deposits.";
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
