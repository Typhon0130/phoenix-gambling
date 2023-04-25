<?php namespace App\License\Plugin;

use App\License\Plugin\Local\Phoenix\SupportChatPlugin;

class PluginManager {

  public function fetch(): array {
    return $this->localPlugins();
  }

  private function localPlugins(): array {
    return [
      new SupportChatPlugin()
    ];
  }

  public function find(string $id): ?Plugin {
    foreach ($this->fetch() as $item)
      if($item->id() === $id) return $item;
    return null;
  }

  public function isEnabled(string $id): bool {
    foreach ($this->fetch() as $item)
      if($item->id() === $id && $item->isEnabled()) return true;
    return false;
  }

}
