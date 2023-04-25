<?php namespace App\Models;

use App\Permission\ChatModeratorPermission;
use App\Permission\ControlUsersPermission;
use App\Permission\Permissions;
use App\Permission\RolePermission;
use App\Permission\RootPermission;
use App\Utils\Demo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Mongodb\Eloquent\Model;

class Role extends Model {

  protected $connection = 'mongodb';
  protected $collection = 'roles';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'id', 'permissions', 'name', 'readOnly'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [];

  /**
   * The attributes that should be cast to native types.
   * @var array
   */
  protected $casts = [
    'permissions' => 'json'
  ];

  public static function fromId(string $id): ?Role {
    foreach(self::allRoles() as $role) {
      if($role->id === $id) {
        return $role;
      }
    }

    return null;
  }

  public static function toRolesAndPermissionsArray(): array {
    $roles = [];
    $perms = [];

    foreach (self::allRoles() as $role) $roles[] = $role->toArray();
    foreach (Permissions::all() as $perm) $perms[] = $perm->toArray(new RolePermission());

    return [
      'roles' => $roles,
      'allPermissions' => $perms
    ];
  }

  public static function allRoles(): Collection {
    if(!Cache::has('demoRoleCheck') && Demo::isDemo(true) && Role::where('id', 'test')->first() == null) {
      Role::create([
        'id' => 'test',
        'name' => 'Example role',
        'permissions' => [
          (new ControlUsersPermission())->toArray((new RolePermission())->active(true)->edit(true))
        ],
        'readOnly' => false
      ]);

      Cache::forever('demoRoleCheck', 'true');
    }

    if(!Cache::has('defaultRoleCheck') && Role::where('id', '*')->first() == null) {
      Role::create([
        'id' => '*',
        'name' => 'Administrator',
        'permissions' => [
          (new RootPermission())->toArray((new RolePermission())->active(true))
        ],
        'readOnly' => true
      ]);

      Role::create([
        'id' => 'chat_moderator',
        'name' => 'Moderator',
        'permissions' => [
          (new ChatModeratorPermission())->toArray((new RolePermission())->active(true))
        ],
        'readOnly' => true
      ]);

      Cache::forever('defaultRoleCheck', true);
    }

    return Role::all();
  }

}
