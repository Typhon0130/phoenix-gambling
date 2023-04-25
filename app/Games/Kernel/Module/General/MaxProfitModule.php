<?php namespace App\Games\Kernel\Module\General;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\Module;
use App\Games\Kernel\Module\General\Wrapper\MultiplierCanBeLimited;
use App\Games\Kernel\Module\ModuleConfigurationOption;
use App\Games\Kernel\ProvablyFairResult;
use App\Games\Stairs;
use App\Models\Modules;
use Illuminate\Support\Facades\Log;

class MaxProfitModule extends Module {

  function id(): string {
    return "max_profit";
  }

  function name(): string {
    return "Max Profit";
  }

  function description(): string {
    return "Limits max profit based on each currency";
  }

  function supports(): bool {
    return $this->game instanceof MultiplierCanBeLimited && !$this->game instanceof Stairs;
  }

  function lose(Data $data): bool {
    return $data->bet() * $this->game->multiplier($this->dbGame, $this->data, $this->result) >= floatval(Modules::get($this->game, $data->demo())->get($this, 'currency:' . $data->currency()));
  }

  function settings(): array {
    $options = [];

    foreach (Currency::all() as $currency)
      $options = array_merge($options, [
        new class($currency) extends ModuleConfigurationOption {
          private Currency $currency;

          public function __construct($currency) {
            $this->currency = $currency;
          }

          function id(): string {
            return 'currency:' . $this->currency->id();
          }

          function name(): string {
            return "Max profit (" . $this->currency->displayName() . ")";
          }

          function description(): string {
            return "Max profit per game, for example 1 " . $this->currency->name();
          }

          function defaultValue(): ?string {
            return "0.00";
          }

          function type(): string {
            return "input";
          }
        }
      ]);

    return $options;
  }

}
