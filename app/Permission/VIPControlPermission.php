<?php namespace App\Permission;

class VIPControlPermission extends Permission {

    public function id(): string {
        return "vip";
    }

    public function name(): string {
        return "VIP Settings";
    }

    public function description(): string {
        return "Allows to edit VIP ranks.";
    }

    public function isEditable(): bool {
        return false;
    }

    public function isDeletable(): bool {
        return false;
    }

    public function isCreatable(): bool {
        return false;
    }

}
