<?php namespace App\Games\Kernel;

abstract class Metadata {

  abstract function id(): string;

  abstract function name(): string;

  /**
   * @return string icon name or image (third-party games)
   * @see Icon.vue
   */
  abstract function icon(): string;

  /**
   * @return array of categories
   */
  abstract function category(): array;

  /**
   * If this returns true, then this game shouldn't appear anywhere in admin panel, will be disabled,
   * and users will see "Coming soon!" label instead of "Unavailable".
   * @return bool
   */
  public function isPlaceholder(): bool {
    return false;
  }

  public function releasedAt(): string {
    return '-';
  }

  public function isMobile(): ?bool {
    return null;
  }

  public function image(): string {
    return '/img/misc/unknown-game-image.jpg';
  }

  public function toArray(): array {
    return [
      'id' => $this->id(),
      'name' => $this->name(),
      'icon' => $this->icon(),
      'category' => $this->category(),
      'released_at' => $this->releasedAt(),
      'isMobile' => $this->isMobile(),
      'image' => $this->image()
    ];
  }

}
