<?php namespace App\Permission;

class RootPermission extends Permission {

    public function id(): string {
        return "*";
    }

    public function name(): string {
        return "*";
    }

    public function description(): string {
        return "Access to everything, unrestricted by other roles";
    }

    public function isEditable(): bool {
        return false;
    }

    public function isViewable(): bool {
        return false;
    }

    public function isDeletable(): bool {
        return false;
    }

    public function isCreatable(): bool {
        return false;
    }

}
