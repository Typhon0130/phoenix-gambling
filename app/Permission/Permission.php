<?php namespace App\Permission;

abstract class Permission {

    public abstract function id(): string;

    public abstract function name(): string;

    public abstract function description(): string;

    public abstract function isEditable(): bool;

    public abstract function isDeletable(): bool;

    public abstract function isCreatable(): bool;

    public final function toArray(RolePermission $permission): array {
        return [
            'isEditable' => $this->isEditable(),
            'isDeletable' => $this->isDeletable(),
            'isCreatable' => $this->isCreatable(),
            'id' => $this->id(),
            'name' => $this->name(),
            'description' => $this->description(),
            'permissions' => $permission->data()
        ];
    }

}
