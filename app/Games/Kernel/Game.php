<?php namespace App\Games\Kernel;

use App\Models\DisabledGame;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\Models\GameAnalytics;
use App\Models\HiddenGame;
use Illuminate\Support\Facades\Cache;

/**
 * @package App\Games\Kernel
 * @see QuickGame
 * @see ExtendedGame
 * @see MultiplayerGame
 * @see ThirdPartyGame
 */
abstract class Game {

  abstract function metadata(): ?Metadata;

  abstract function process(Data $data);

  abstract function result(ProvablyFairResult $result): array;

  public function nonce() {
    return auth()->guest() ? mt_rand(1, 100000) : \App\Models\Game::where('user', auth()->user()->_id)->count() + 1;
  }

  public function server_seed() {
    return ProvablyFair::generateServerSeed();
  }

  public function client_seed() {
    return auth()->guest() ? 'guest' : auth()->user()->client_seed;
  }

  public function data(): array {
    return [];
  }

  public function isDisabled(): bool {
    if ($this->metadata()->isPlaceholder()) return true;
    return DisabledGame::where('name', $this->metadata()->id())->first() != null;
  }

  public function isHidden(): bool {
    return HiddenGame::where('name', $this->metadata()->id())->first() != null;
  }

  public function ignoresMultipleClientTabs() {
    return false;
  }

  public function usesCustomWagerCalculations() {
    return false;
  }

  public function getBotData(): array {
    return [];
  }

  protected function getCards(ProvablyFairResult $result, int $count, $fisher_yates = false): array {
    $cards = range(0, 207);
    $output = [];
    for ($i = 0; $i < $count; $i++) $output[] = $fisher_yates ? array_splice($cards, floor($result->extractFloats($count)[$i] * (52 - $i)), 1)[0]
      : $cards[floor($result->extractFloats($count)[$i] * 52)];
    return $output;
  }

  public static function list() {
    $thirdPartyGames = [];
    foreach (ThirdPartyGame::providers() as $provider)
      $thirdPartyGames = array_merge($thirdPartyGames, $provider->createInstances());

    return array_merge($thirdPartyGames, [
      new \App\Games\Mines(),
      new \App\Games\Dice(),
      new \App\Games\Wheel(),
      new \App\Games\Plinko(),
      new \App\Games\Coinflip(),
      new \App\Games\Tower(),
      new \App\Games\Keno(),
      new \App\Games\Stairs(),
      new \App\Games\Blackjack(),
      new \App\Games\Diamonds(),
      new \App\Games\Roulette(),
      new \App\Games\HiLo(),
      new \App\Games\Limbo(),
      new \App\Games\Slide(),
      new \App\Games\Baccarat(),
      new \App\Games\VideoPoker()
    ]);
  }

  public static function find(string $api_id) {
    foreach (self::list() as $game)
      if ($game->metadata()->id() === $api_id) return $game;
    return null;
  }

  protected function applyHouseEdge(array $payouts): array {
    $result = $payouts;
    if (array_keys($payouts) !== array_keys(array_keys($payouts))) foreach ($payouts as $key => $value) {
      $result = array_replace($result, [$key => HouseEdgeModule::apply($this, $value)]);
    }
    else {
      $result = [];
      foreach ($payouts as $value) $result[] = HouseEdgeModule::apply($this, $value);
    }
    return $result;
  }

  public static function analytics(\App\Models\Game $game, string $id = null) {
    self::handleAnalytics('daily', $game, 'Total');
    self::handleAnalytics('total', $game, 'Total');

    self::handleAnalytics('daily', $game, $id);
    self::handleAnalytics('total', $game, $id);
  }

  private static function handleAnalytics(string $type, \App\Models\Game $game, string $id = null) {
    $statId = $id !== null ? $id : $game->game;
    $analytics = GameAnalytics::where('statId', $statId)->where('type', $type)->where('currency', $game->currency)->first();

    if($analytics == null) {
      $analytics = GameAnalytics::create([
        'statId' => $statId,
        'type' => $type,
        'currency' => $game->currency,
        'wagered' => 0,
        'payout' => 0,
        'profit' => 0
      ]);
    }

    $analytics->update([
      'wagered' => $analytics->wagered + $game->wager,
      'payout' => $analytics->payout + $game->profit,
      'profit' => $analytics->profit + ($game->status === 'lose' ? $game->wager : 0)
    ]);
  }

  public static function forgetCache(): void {
    Cache::forget('game:list');
    Cache::forget('phoenix:list');
    Cache::forget('slotegrator:providerGameList');
    Cache::forget('slotegrator:loadingGameList');
  }

}
