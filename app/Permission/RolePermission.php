<?php namespace App\Permission;

class RolePermission {

    private bool $active = false, $delete = false, $create = false, $edit = false;

    public function active(bool $set): RolePermission {
        $this->active = $set;
        return $this;
    }

    public function edit(bool $set): RolePermission {
        $this->edit = $set;
        return $this;
    }

    public function delete(bool $set): RolePermission {
        $this->delete = $set;
        return $this;
    }

    public function create(bool $set): RolePermission {
        $this->create = $set;
        return $this;
    }

    public function all(bool $set): RolePermission {
        return $this->active($set)->delete($set)->create($set)->edit($set);
    }

    public function isActive(): bool {
        return $this->active;
    }

    public function canDelete(): bool {
        return $this->delete;
    }

    public function canCreate(): bool {
        return $this->create;
    }

    public function from(array $data): RolePermission {
        return $this->active($data['active'])->create($data['create'])->delete($data['delete'])->edit($data['edit']);
    }

    public function data(): array {
        return [
            'active' => $this->active,
            'delete' => $this->delete,
            'create' => $this->create,
            'edit' => $this->edit
        ];
    }

}
