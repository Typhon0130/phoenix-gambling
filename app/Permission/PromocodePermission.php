<?php namespace App\Permission;

class PromocodePermission extends Permission {

    public function id(): string {
        return "promocodes";
    }

    public function name(): string {
        return "Promocode";
    }

    public function description(): string {
        return "Access to promocodes.";
    }

    public function isEditable(): bool {
        return false;
    }

    public function isDeletable(): bool {
        return true;
    }

    public function isCreatable(): bool {
        return true;
    }

}
