<?php namespace App\Sport\Provider\PhoenixGambling;

use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\License\License;
use App\Models\PhoenixGamblingSportData;
use App\Sport\Provider\PhoenixGambling\Market\NoOpMarketHandler;
use App\Sport\Provider\SportCategory;
use App\Sport\Provider\SportGame;
use App\Sport\Provider\SportLineProvider;
use App\Sport\Provider\SportMarketHandler;
use App\Sport\Sport;
use Illuminate\Support\Facades\Cache;

class PhoenixGamblingLine extends SportLineProvider {

  /**
   * ws - WebSocket implementation, receives data from Phoenix server and saves it to database.
   * request - Deprecated. Slower implementation, sends requests directly to Phoenix server. Unsupported since ver. 2.3.3.
   * @var string
   */
  public static string $mode = 'ws';

  public static bool $debug = false;

  /**
   * @return array<SportCategory>
   */
  function getCategories(): array {
    if(!$this->isAlive()) return [];

    if(Cache::has('sport:pg:categoryList')) return Cache::get('sport:pg:categoryList');
    return [];
  }

  public function updateCategoryCache(): void {
    $categories = [];

    $json = json_decode(Sport::cachedRequest(self::domain() . ':2053/line/categories', [
      'key' => (new License())->getKey()
    ]), true);

    foreach ($json as $category)
      $categories[] = new PhoenixGamblingSportCategory($category['id'], $category['name'], $category['live'], $category['total'], $category['sportType']);

    usort($categories, function($a, $b) {
      return $b->liveCount() - $a->liveCount();
    });

    usort($categories, function($a, $b) {
      return $b->id() === 'soccer' ? 1 : 0;
    });

    Cache::put('sport:pg:categoryList', $categories);
  }

  /**
   * @param string $id
   * @return SportGame|null
   */
  function findGame(string $id): ?SportGame {
    if(!$this->isAlive()) return null;

    try {
      if(self::$mode === 'request') {
        $data = json_decode(Sport::cachedRequest(self::domain() . ':2053/line/game/' . $id, [
          'key' => (new License())->getKey()
        ]), true);
      } else {
        $data = PhoenixGamblingSportData::where('srId', $id)->first()?->toArray();
      }

      return new PhoenixGamblingGame(new PhoenixGamblingSportCategory($data['sportId'], $data['sportName'], 0, 0, $data['sportType']), $data);
    } catch (\Exception) {
      return null;
    }
  }

  /**
   * @param string $sportType
   * @param string $market
   * @param string $runner
   * @return SportMarketHandler|null
   */
  function findMarket(string $sportType, string $market, string $runner): ?SportMarketHandler {
    $handlers = [
      // Sport
      new Market\Sport\OnexTwo(),
      new Market\Sport\BothTeamsToScore(),
      new Market\Sport\DoubleChance(),
      new Market\Sport\DrawNoBet(),
      new Market\Sport\CorrectScore(),
      new Market\Sport\Winner(),
      new Market\Sport\ExactSets(),
      new Market\Sport\SetWinner(),

      new Market\Sport\OnexTwoHalf(),
      new Market\Sport\HalfBothTeamsToScore(),
      new Market\Sport\HalfDrawNoBet(),
      new Market\Sport\HalfCorrectScore(),
      new Market\Sport\HalfExactGoals(),
      new Market\Sport\HalfTotal(),
      new Market\Sport\HalfOddEven(),
      new Market\Sport\HalfDoubleChance(),

      new Market\Sport\OnexTwoPeriod(),
      new Market\Sport\PeriodBothTeamsToScore(),
      new Market\Sport\PeriodDrawNoBet(),
      new Market\Sport\PeriodCorrectScore(),
      new Market\Sport\PeriodExactGoals(),
      new Market\Sport\PeriodTotal(),
      new Market\Sport\PeriodOddEven(),
      new Market\Sport\PeriodDoubleChance(),

      new Market\Sport\WhichTeamToScore(),
      new Market\Sport\PlayerExactGoals(),
      new Market\Sport\PlayerToWin(),
      new Market\Sport\OddEven(),
      new Market\Sport\ExactGoals(),
      new Market\Sport\Total(),
      new Market\Sport\PlayerTotal(),

      // eSport
      new Market\Esport\MatchWinnerTwoway(),
      new Market\Esport\MatchWinnerThreeway(),
      new Market\Esport\MatchCorrectMatchScore(),
      new Market\Esport\MapWinnerThreeway(),
      new Market\Esport\MapWinnerTwoway(),
      new Market\Esport\AwayWinsAtLeastOneMap(),
      new Market\Esport\HomeWinsAtLeastOneMap(),

      // Keep NoOp last. It refunds unsupported bets automatically.
      new NoOpMarketHandler()
    ];

    /* @var $handler SportMarketHandler */
    foreach ($handlers as $handler)
      if($handler->supports($sportType) && $handler->isHandling($market, $runner)) return $handler;

    return null;
  }

  public static function domain(): string {
    return self::$debug ? "http://localhost" : "https://phoenix-gambling.com";
  }

  public function isAlive(): bool {
    try {
      return json_decode((new PhoenixGame())->curl('http://localhost:9615', null, 2), true)['status'] === true;
    } catch (\Exception) {
      return false;
    }
  }

}
