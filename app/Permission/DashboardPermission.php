<?php namespace App\Permission;

class DashboardPermission extends Permission {

    public function id(): string {
        return "dashboard";
    }

    public function name(): string {
        return "Dashboard";
    }

    public function description(): string {
        return "Access to dashboard";
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
