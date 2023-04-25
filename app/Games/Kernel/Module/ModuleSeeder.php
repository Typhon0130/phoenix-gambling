<?php namespace App\Games\Kernel\Module;

use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\ProvablyFair;
use App\Models\Modules;

class ModuleSeeder {

  private $game;
  private ?Data $data;
  private ?\App\Models\Game $dbGame;
  private bool $demo;

  public function __construct(Game $game, bool $demo, ?Data $data, \App\Models\Game $dbGame = null) {
    $this->game = $game;
    $this->data = $data;
    $this->demo = $demo;
    $this->dbGame = $dbGame;
  }

  public function find($isLoss, $startingSeed = null): string {
    $temporary = $startingSeed !== null ? $startingSeed : ProvablyFair::generateServerSeed();
    $result = (new ProvablyFair($this->game, $temporary))->result();
    $cheat = false;
    $moduleList = Modules::get($this->game, $this->demo)->activeModules($this->data, $result, $this->dbGame);

    foreach ($moduleList as $module) {
      if ($module->lose($this->data)) {
        $cheat = true;
        break;
      }
    }

    if ($cheat) {
      $server_seed = null;
      while ($server_seed == null) {
        $temporary = ProvablyFair::generateServerSeed();
        $result = (new ProvablyFair($this->game, $temporary))->result();

        if ($isLoss($result) === true) $server_seed = $temporary;
      }
      return $server_seed;
    } else return $temporary;
  }

}
