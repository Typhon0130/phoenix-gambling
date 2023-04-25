<?php namespace App\Games\Kernel\ThirdParty\Phoenix;

use App\Currency\Currency;
use App\Currency\Local\LocalCurrency;
use App\Games\Kernel\Data;
use App\Games\Kernel\GameCategory;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\License\License;
use App\Models\Leaderboard;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhoenixGame extends ThirdPartyGame {

  public function provider(): string {
    return $this->data && $this->data['category'] === 'Slots' ? "Slots (Originals)" : "Originals";
  }

  function metadata(): Metadata {
    return new class($this->data) extends Metadata {
      private ?array $data;

      public function __construct(?array $data) {
        $this->data = $data;
      }

      function id(): string {
        return "external:" . (!$this->data ? 'dummy' : $this->data['id']);
      }

      function name(): string {
        return $this->data ? $this->data['name'] : 'Dummy';
      }

      function icon(): string {
        return 'slots';
      }

      function image(): string {
        return 'https://phoenix-gambling.com' . $this->data['thumbnail'];
      }

      public function category(): array {
        $categories = [GameCategory::$originals];
        if ($this->data['category'] === 'Slots') $categories[] = GameCategory::$slots;
        if ($this->data['category'] === 'Multiplayer') $categories[] = GameCategory::$multiplayer;
        return $categories;
      }

      public function isPlaceholder(): bool {
        return $this->data['comingSoon'];
      }
    };
  }

  function processCallback(Request $request): array {
    /** @var User $user */

    $user = User::where('_id', $request->input('session')['playerId'])->first();
    $currencyId = $request->input('session')['currency'];

    $bet = $request->game['bet'];
    $id = $request->game['id'];
    $gameId = $request->input('session')['gameId'];

    $currency = Currency::getByName($currencyId)[0];

    switch ($request->type) {
      case "start":
      {
        $userBalance = $user->balance($currency);
        if ($userBalance->get() >= $bet) {
          $userBalance->subtract($bet, Transaction::builder()->message('[Phoenix Games] Game Start')->get());
          return ['result' => true];
        }

        return ['result' => false];
      }
      case "finish":
      {
        $payout = $request->game['payout'];

        $game = \App\Models\Game::create([
          'id' => DB::table('games')->count() + 1,
          'user' => $user->_id,
          'game' => 'external:' . $gameId,
          'multiplier' => $payout,
          'status' => $request->game['status'],
          'profit' => $bet * $payout,
          'data' => [],
          'type' => 'third-party',
          'demo' => false,
          'wager' => $bet,
          'currency' => $currency->id(),
          'bet_usd_converted' => $currency->convertTokenToFiat($bet)
        ]);

        if ($payout != 1) {
          Leaderboard::insert($game);
        }

        if ($request->game['status'] == "win") {
          $user->balance($currency)->add($bet * $payout, Transaction::builder()->message('[Phoenix Games] x' . $payout . ' ' . $gameId)->get(), $request->data['balanceUpdateDelay']);
        }

        event(new \App\Events\LiveFeedGame($game, 0));
        self::analytics($game);

        return [];
      }
      default:
        return [];
    }
  }

  function process(Data $data) {
    $currency = Currency::find($data->currency());
    $license = (new License());

    if(!$license->isValid())
      $url = '/error/noLayout/1';
    else
      $url = json_decode($this->curl('https://phoenix-gambling.com/api/game/create/' . $this->data['id'], [
        "api_key" => $license->get()['apiKey'],
        "playerId" => $data->guest() ? "-" : $data->user()->_id,
        "playerName" => $data->guest() ? "Demo" : $data->user()->name,
        "minBet" => $currency->convertUSDToToken(0.20),
        "maxBet" => $currency->convertUSDToToken(20),
        "step" => $currency->convertUSDToToken(0.1),
        "currencyName" => $currency->name(),
        "currencyParam" => $currency instanceof LocalCurrency ? 2 : 8,
        "isDemo" => $data->demo()
      ]), true)['url'];

    return [
      'response' => [
        'id' => '-1',
        'wager' => $data->bet(),
        'type' => 'third-party',
        'link' => $url
      ]
    ];
  }

  public function createInstances(): array {
    if (Cache::has('phoenix:list')) {
      $result = [];

      $hasSlots = (new License())->hasFeature('phoenixSlots');

      foreach (Cache::get('phoenix:list') as $game) {
        $instance = new PhoenixGame($game);
        if(in_array(GameCategory::$slots, $instance->metadata()->category()) && !$hasSlots)
          continue;

        $result[] = $instance;
      }
      return $result;
    }

    $json = json_decode($this->curl("https://phoenix-gambling.com/api/game/list"), true);
    Cache::forever('phoenix:list', $json);
    return $this->createInstances();
  }

  /**
   * @throws \Exception
   */
  public function curl($url, $params = null): string {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36"
    ]);

    if ($params != null) {
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
    }

    $response = curl_exec($curl);

    if(curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200)
      throw new \Exception('Invalid status code');

    curl_close($curl);

    return $response;
  }

}
