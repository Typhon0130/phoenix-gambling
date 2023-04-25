<?php

use App\Events\LiveFeedSportGame;
use App\Models\Transaction;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Sport;
use App\Utils\APIResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('categories', function() {
  $result = [];
  foreach(Sport::getLine()->getCategories() as $category) $result[] = $category->toArray(false);
  return $result;
});

Route::post('live', function(Request $request) {
  $request->validate(['type' => 'required']);

  $category = Sport::getLine()->findCategory($request->type);
  if($category == null) return APIResponse::reject(1, 'Invalid category');

  return APIResponse::success($category->toArray(true, true, $request->isLive ?? true));
});

Route::post('game', function(Request $request) {
  $request->validate(['id' => 'required']);

  $game = Sport::getLine()->findGame($request->id);
  if($game == null) return APIResponse::reject(1, "Invalid game");
  return APIResponse::success($game->toArray());
});

Route::middleware('auth:sanctum')->post('bet', function(Request $request) {
  if(!(new \App\Sport\Provider\PhoenixGambling\PhoenixGamblingLine())->isAlive()) return APIResponse::reject(1, "WebSocket server is dead");

  $request->validate([
    'type' => 'required',
    'bets' => 'required'
  ]);

  $type = $request->type;

  if($type !== 'multi' && $type !== 'single') return APIResponse::reject(4, 'Invalid betting type: only single/multi are accepted as input value');

  $multiBetMatchIds = [];
  $currency = auth('sanctum')->user()->clientCurrency();
  $maxBetUSD = 100;

  foreach($request->bets as $bet) {
    if(($type === 'single' && floatval($bet['value']) < 0.00000001) || auth('sanctum')->user()->balance($currency)->get() < ($type === 'single' ? floatval($bet['value']) : floatval($request->multiBetValue)))
      return APIResponse::reject(3, 'Insufficient balance');

    if(($type === 'single' && $currency->convertTokenToFiat(floatval($bet['value'])) > $maxBetUSD)
      || (floatval($request->multiBetValue) > $currency->convertFiatToToken($maxBetUSD)))
      return APIResponse::reject(9, 'Max. bet amount exceeded');

    $game = Sport::getLine()->findGame($bet['game']['id']);

    $market = null;
    $runner = null;

    foreach($game->markets() as $gameMarket) {
      if($gameMarket->name() === $bet['market']['name']) {
        foreach($gameMarket->getRunners() as $gameRunner) {
          if($gameRunner->name() === $bet['runner']['name']) {
            $market = $gameMarket;
            $runner = $gameRunner;
            break;
          }
        }
      }
    }

    if($market == null || $runner == null)
      return APIResponse::reject(1, 'Market or runner does not exist');

    if(!$market->isOpen() || !$runner->isOpen() || !$game->isOpen())
      return APIResponse::reject(1, 'This game does not accept bets');

    $handler = Sport::getLine()->findMarket($game->sportType(), $market->name(), $runner->name());
    if($game->sportType() === 'SPORTS') {
      if($handler->getData($game->sportRadarId())->match()->isFinished()) return APIResponse::reject(1, 'Game is finished');
    } else if($game->sportType() === 'ESPORTS') {
      if($handler->findHistoricGame($game->id())->matchStatus === 'Closed') return APIResponse::reject(1, 'Game is finished');
    } else return APIResponse::reject(1, 'Unknown sportType');

    if($type === 'multi') {
      if (in_array($game->id(), $multiBetMatchIds)) return APIResponse::reject(8, 'Invalid multibet');
      $multiBetMatchIds[] = $game->id();
    }
  }

  $multiBetId = $type === 'multi' ? \App\Games\Kernel\ProvablyFair::generateServerSeed() : null;

  if($type === 'multi') auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->subtract(floatval($request->multiBetValue), Transaction::builder()->message('Sport Bet [Multibet]')->get());

  foreach($request->bets as $bet) {
    $game = Sport::getLine()->findGame($bet['game']['id']);

    if($game == null) return APIResponse::reject(2, 'Invalid game id');

    $category = Sport::getLine()->findCategory($bet['category']);
    if($category == null) return APIResponse::reject(2, 'Invalid category');

    $market = null;
    $runner = null;

    foreach($game->markets() as $gameMarket) {
      if($gameMarket->name() === $bet['market']['name']) {
        foreach($gameMarket->getRunners() as $gameRunner) {
          if($gameRunner->name() === $bet['runner']['name']) {
            $market = $gameMarket;
            $runner = $gameRunner;
            break;
          }
        }
      }
    }

    if(!$market->isOpen() || !$runner->isOpen() || !$game->isOpen())
      return APIResponse::reject(1, 'This game does not accept bets');

    if($type === 'single')
      auth('sanctum')->user()->balance(auth('sanctum')->user()->clientCurrency())->subtract(floatval($bet['value']), Transaction::builder()->message('Sport Bet')->get());

    $sportBet = \App\Models\SportBet::create([
      'user' => auth('sanctum')->user()->_id,
      'sportradar_id' => $game->sportRadarId(),
      'status' => 'ongoing',
      'game_id' => $game->id(),
      'game' => $game->name(),
      'market' => $bet['market']['name'],
      'runner' => $bet['runner']['name'],
      'odds' => $runner->price(),
      'bet' => ($type === 'single' ? floatval($bet['value']) : floatval($request->multiBetValue)),
      'currency' => auth('sanctum')->user()->clientCurrency()->id(),
      'category' => $category->id(),
      "icon" => $category->icon(),
      'snapshot' => SportGameSnapshot::createSnapshot($game, $bet['market']['name'])->toArray(),
      'multiBetId' => $multiBetId,
      'sportType' => $game->sportType()
    ]);

    if($type !== 'multi') event(new LiveFeedSportGame($sportBet));
  }

  return APIResponse::success();
});
