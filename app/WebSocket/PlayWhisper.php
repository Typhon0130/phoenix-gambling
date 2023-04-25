<?php namespace App\WebSocket;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\ThirdParty\Phoenix\PhoenixGame;
use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\Models\GameStats;
use Illuminate\Support\Facades\DB;

class PlayWhisper extends WebSocketWhisper {

  public function event(): string {
    return "Play";
  }

  public function process($data): array {
    $game = Game::find($data->api_id);
    if ($game == null) return ['code' => -3, 'message' => 'Unknown API game id'];
    if ($game->isDisabled()) return ['code' => -5, 'message' => 'Game is disabled'];

    if (!$game instanceof ThirdPartyGame && $this->user != null && !$game->ignoresMultipleClientTabs() && DB::table('games')->where('game', $data->api_id)->where('user', $this->user->_id)->where('demo', false)->where('status', 'in-progress')->count() > 0) return ['code' => -8, 'message' => 'Game already has started'];

    $c = Currency::find($data->currency);

    if ($this->user == null && !$data->demo) return ['code' => -2, 'message' => 'Not authorized'];
    if (!$game instanceof ThirdPartyGame && !$game->usesCustomWagerCalculations() && floatval($data->bet) < 0.00000001) return ['code' => -1, 'message' => 'Invalid wager value'];
    if (!$game instanceof ThirdPartyGame && $this->user != null && ($this->user->balance($c)->demo($data->demo)->get() < floatval($data->bet))) return ['code' => -4, 'message' => 'Not enough money'];

    /*
    if($game instanceof PhoenixGame && ($this->user == null || $this->user->access == 'user'))
        return [ 'code' => -99, 'message' => 'House games are temporarily disabled' ];
    */

/*    try {
      if (floatval($data->bet) > $c->convertEURToToken(20))
        return ['code' => -9, 'message' => 'Max bet: 20 EUR'];
    } catch (\Exception $ignored) {
      //
    }*/

    $data = new Data($this->user, [
      'api_id' => $data->api_id,
      'bet' => $data->bet ?? 0,
      'currency' => $data->currency,
      'demo' => $data->demo,
      'quick' => $data->quick ?? false,
      'data' => (array)$data->data
    ]);

    if ($this->user != null && $this->user->referral != null && $this->user->games() >= floatval(\App\Models\Settings::get('referrer_activity_requirement', 100))) {
      $referrer = \App\Models\User::where('_id', $this->user->referral)->first();
      $referrals = $referrer->referral_wager_obtained ?? [];
      if (!in_array($this->user->_id, $referrals)) {
        $referrals[] = $this->user->_id;
        $referrer->update(['referral_wager_obtained' => $referrals]);
        $referrer->balance(Currency::find('btc'))->add(floatval(Currency::find('btc')->option('referral_bonus')), \App\Models\Transaction::builder()->message('Active referral bonus')->get());
      }
    }

    $stats = GameStats::where('game', $data->id())->first();
    if ($stats == null) {
      GameStats::create([
        'game' => $data->id(),
        'launches' => 1
      ]);
    } else $stats->update(['launches' => $stats->launches + 1]);

    $currency = Currency::find($data->currency());
    if ($currency->isToken() && $currency->tokenPrice() == -1) return ['code' => -9, 'message' => 'Token price error'];

    return $game->process($data);
  }

}

