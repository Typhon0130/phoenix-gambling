<?php namespace App\License\Plugin\Event;

trait PluginToggleEvent {

  use PluginStateEvent;

  public abstract function onState(bool $isEnabled);

  public function onEnable() {
    $this->onState(true);
  }

  public function onDisable() {
    $this->onState(false);
  }

}
