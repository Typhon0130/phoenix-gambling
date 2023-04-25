<?php namespace App\License\Plugin\Local;

use App\License\Plugin\Event\PluginToggleEvent;
use App\Models\Settings;

abstract class ToggleableFeatureLocalPlugin extends LocalPlugin {

  use PluginToggleEvent;

  public function onState(bool $isEnabled) {
    Settings::set($this->getSettingsKey(), $isEnabled ? 'true' : 'false');
  }

  public function isEnabled(): bool {
    return Settings::get($this->getSettingsKey(), 'false') === 'true';
  }

  private function getSettingsKey(): string {
    return '[Plugin/' . $this->id() . '] State';
  }

}
