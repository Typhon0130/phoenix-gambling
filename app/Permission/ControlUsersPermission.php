<?php namespace App\Permission;

class ControlUsersPermission extends Permission {

    public function id(): string {
        return "users";
    }

    public function name(): string {
        return "Users";
    }

    public function description(): string {
        return "Account information in dashboard. 'Create' permission allows user to register new accounts with permissions creator might not have access to!";
    }

    public function isEditable(): bool {
        return true;
    }

    public function isDeletable(): bool {
        return true;
    }

    public function isCreatable(): bool {
        return true;
    }

}
