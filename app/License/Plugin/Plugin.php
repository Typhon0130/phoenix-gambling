<?php namespace App\License\Plugin;

abstract class Plugin {

  public abstract function id(): string;

  public abstract function name(): string;

  public abstract function description(): array;

  public abstract function author(): string;

  public abstract function version(): string;

  public abstract function logoUrl(): string;

  public abstract function isEnabled(): bool;

  public function toArray(): array {
    return [
      'id' => $this->id(),
      'name' => $this->name(),
      'description' => $this->description(),
      'author' => $this->author(),
      'version' => $this->version(),
      'logoUrl' => $this->logoUrl(),
      'isEnabled' => $this->isEnabled()
    ];
  }

  public function hasEvent(mixed $c): bool {
    return in_array($c, class_uses_recursive($this));
  }

}
