<?php namespace App\Permission;

use App\License\License;
use App\License\Plugin\PluginManager;
use App\Models\Role;

class Permissions {

  public static function all(): array {
    $permissions = [
      new RootPermission(),
      new ChatModeratorPermission(),
      new DashboardPermission(),
      new IgnorePrivacyPermission(),
      new WalletPermission(),
      new WithdrawsPermission(),
      new NotificationPermission(),
      new PromocodePermission(),
      new ControlUsersPermission(),
      new BannerPermission(),
      new VIPControlPermission(),
      new GameStatsPermission()
    ];

    if((new License())->hasFeature('phoenixSport'))
      $permissions[] = new SportManagementPermission();

    if((new PluginManager())->isEnabled('phoenix:supportChat'))
      $permissions[] = new ManageTicketsPermission();

    return $permissions;
  }

  public static function findById($id): ?Permission {
    foreach (self::all() as $item) {
      if ($item->id() === $id) return $item;
    }

    return null;
  }

  public static function whitelistedRolesAndPermissions(array $dbRoles): array {
    $result = [];

    /** @var Permission $permission */
    foreach ($dbRoles as $dbRole) {
      $role = Role::fromId($dbRole['id']);
      if ($role == null) continue;

      $result[] = [
        'id' => $dbRole['id'],
        'permissions' => $role->permissions
      ];
    }

    return $result;
  }

}
