<?php namespace App\License\Plugin\Event;

trait PluginStateEvent {

  public abstract function onEnable();

  public abstract function onDisable();

}
