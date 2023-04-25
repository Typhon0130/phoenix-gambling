<?php namespace App\License\Plugin\Local\Phoenix;

use App\License\Plugin\Local\ToggleableFeatureLocalPlugin;

class SupportChatPlugin extends ToggleableFeatureLocalPlugin {

  public function id(): string {
    return "phoenix:supportChat";
  }

  public function name(): string {
    return "Support Chat";
  }

  public function description(): array {
    return [
      "Manage tickets directly on your website"
    ];
  }

  public function author(): string {
    return "Phoenix Gambling, https://phoenix-gambling.com";
  }

  public function logoUrl(): string {
    return "/img/dashboard/plugins/supportChat/icon.png";
  }

}
