<?php namespace App\Games\Kernel\ThirdParty\Slotegrator;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ThirdParty\ThirdPartyGame;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBalance;
use App\Utils\Exception\UnsupportedOperationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotegratorGame extends ThirdPartyGame {

  private string $providerId;

  public function __construct(string $providerId, ?array $data = null) {
    parent::__construct($data);

    $this->providerId = $providerId;
  }

  public function provider(): string {
    return $this->providerId;
  }

  function metadata(): ?Metadata {
    if (!$this->data) return null;

    return new class($this->data) extends Metadata {
      private ?array $data;

      public function __construct(?array $data) {
        $this->data = $data;
      }

      function id(): string {
        return 'external:sg:' . $this->data['uuid'];
      }

      function name(): string {
        return str_replace(" Mobile", "", $this->data['name']);
      }

      function icon(): string {
        return "slots";
      }

      public function image(): string {
        return $this->data['image'];
      }

      public function category(): array {
        $categories = [$this->data['provider']];

        if ($this->data['type'] === 'live dealer')
          $categories[] = 'live';
        else
          $categories[] = 'slots';

        return $categories;
      }

      public function isMobile(): ?bool {
        return $this->data['is_mobile'] === 1 || str_contains(strtolower($this->name()), "mobile");
      }
    };
  }

  function processCallback(array $request, string $type, string $headerMerchantId, string $headerTimestamp, string $headerNonce, string $headerXSign) {
    $headers = [
      'X-Merchant-Id' => $headerMerchantId,
      'X-Timestamp' => $headerTimestamp,
      'X-Nonce' => $headerNonce,
    ];
    $XSign = $headerXSign;
    $mergedParams = array_merge($_POST, $headers);
    ksort($mergedParams);
    $hashString = http_build_query($mergedParams);
    $expectedSign = hash_hmac('sha1', $hashString, Slotegrator::keys()['merchantKey']);
    if ($XSign !== $expectedSign) throw new \Exception('Invalid sign');

    //Log::info($type . ' -> [C] ' . json_encode($request));

    try {
      if (!str_contains($request['player_id'], '-')) return [
        'error_code' => 'INTERNAL_ERROR',
        'error_description' => 'Invalid player id'
      ];

      $data = explode('-', $request['player_id']);
      $userId = $data[0];
      $currencyId = $data[1];

      $currency = Currency::find($currencyId);
      $user = User::where('_id', $userId)->first();

      /** @var UserBalance $userBalance */
      $userBalance = $user->balance($currency);

      $balance = $currency->fiatNumberFormat($currency->convertTokenToEUR($userBalance->get()));
//      if(Slotegrator::type() === 'staging') $balance = min($balance, 99.0);

      // optimize:
      //isset($request['game_uuid']) ? Game::find($request['game_uuid'])->metadata()->name() . '/' . $request['game_uuid'] : '-';
      $gameName = $request['game_uuid'] ?? '-';

      $update = function () use ($request, $currency, $user, $type) {
        if (!isset($request['round_id'])) return;
        $game = \App\Models\Game::where('ss_gameId', $request['round_id'])->first();

        if ($game == null) {
          $game = \App\Models\Game::create([
            'id' => DB::table('games')->count() + 1,
            'user' => $user->_id,
            'game' => 'external:sg:' . $request['game_uuid'],
            'multiplier' => 0,
            'status' => 'in-progress',
            'profit' => 0,
            'data' => [],
            'type' => 'third-party',
            'demo' => false,
            'wager' => $currency->convertEURToToken(floatval($request['amount'])),
            'currency' => $currency->id(),
            'ss_gameId' => $request['round_id'],
            'bet_usd_converted' => floatval($request['amount'])
          ]);
        }

        $profit = $game->profit;

        if ($type === 'win') {
          if (!$game->sentUpdate) {
            try {
              self::analytics($game, 'Slots');
              event(new \App\Events\LiveFeedGame($game, 0));
            } catch (\Exception $ignored) {
              //
            }

            /*
            if ($user->vipLevel() > 0 && ($user->weekly_bonus ?? 0) < 100)
              $user->update(['weekly_bonus' => ($user->weekly_bonus ?? 0) + 0.1]);*/
          }

          $game->update(['sentUpdate' => true]);

          $profit += $currency->convertEURToToken(floatval($request['amount']));
        }

        $payout = $profit > 0 && $game->wager > 0 ? $profit / $game->wager : 0;

        $game->update([
          'multiplier' => $payout,
          'status' => $payout > 0 ? 'win' : 'lose',
          'profit' => $profit
        ]);
      };

      $refund = function ($transactionId, $value, $refundWins = false) use ($gameName, $userBalance, $currency, $request) {
        $tx = Transaction::where('service_id', $transactionId)->first();

        if ($tx != null && !$tx->refund_processed) {
          $tx->update(['refund_processed' => true]);

          if ($tx->service_type === 'bet')
            $userBalance->add($currency->convertEURToToken($value), Transaction::builder()->game($gameName)->message('[Slotegrator] Action: refund / Transaction id: ' . ($request['transaction_id'] ?? '-') . ' / Refunded transaction id: ' . $transactionId)->get(),
              0, $transactionId . '_refund', 'refund_bet');

          if ($refundWins && $tx->service_type === 'win')
            $userBalance->subtract($currency->convertEURToToken($value), Transaction::builder()->game($gameName)->message('[Slotegrator] Action: refund / Transaction id: ' . ($request['transaction_id'] ?? '-') . ' / Refunded transaction id: ' . $transactionId)->get(),
              $transactionId . '_refund', 'refund_win');
        }
      };

      $txId = ($request['transaction_id'] ?? '') . (isset($request['round_id']) ? '_' . $request['round_id'] : '');

      switch ($type) {
        case 'balance':
        {
          return [
            'balance' => $balance
          ];
        }
        case 'bet':
        {
          if (!Transaction::isProcessed($txId)) {
            /*if(floatval($request['amount']) <= 0) return [
                'error_code' => 'INSUFFICIENT_FUNDS',
                'error_description' => 'Invalid bet value'
            ];*/

            if (floatval($request['amount']) > 20) return [
              'error_code' => 'INSUFFICIENT_FUNDS',
              'error_description' => 'Max. bet amount: 20 EUR'
            ];

            if (floatval($request['amount']) > $balance) return [
              'error_code' => 'INSUFFICIENT_FUNDS',
              'error_description' => 'Not enough money to continue playing'
            ];

            $userBalance->subtract($currency->convertEURToToken(floatval($request['amount'])), Transaction::builder()->game($gameName)->message('[Slotegrator] Action: bet / Transaction id: ' . $txId)->get(),
              $txId, 'bet');
          }

          $update();

          $balance = $currency->fiatNumberFormat($currency->convertTokenToEUR($userBalance->get()));
//          if (Slotegrator::type() === 'staging' && $balance > 99) $balance = 99.0;

          return [
            'balance' => $balance,
            'transaction_id' => $request['transaction_id']
          ];
        }
        case 'win':
        {
          $isProcessed = Transaction::isProcessed($txId);

          if (floatval($request['amount']) > 0 && !$isProcessed) {
            $userBalance->add($currency->convertEURToToken(floatval($request['amount'])), Transaction::builder()->game($gameName)->message('[Slotegrator] Action: win / Transaction id: ' . $txId)->get(),
              0, $txId, 'win');
          }

          $update();
          $balance = $currency->fiatNumberFormat($currency->convertTokenToEUR($userBalance->get()));
//          if (Slotegrator::type() === 'staging' && $balance > 99) $balance = 99.0;

          return [
            'balance' => $balance,
            'transaction_id' => $isProcessed ? Transaction::where('service_id', $txId)->first()->_id : $request['transaction_id']
          ];
        }
        case 'refund':
        {
          $refund($request['bet_transaction_id'], floatval($request['amount']), false);

          $update();
          $balance = $currency->fiatNumberFormat($currency->convertTokenToEUR($userBalance->get()));
//          if (Slotegrator::type() === 'staging' && $balance > 99) $balance = 99.0;

          return [
            'balance' => $balance,
            'transaction_id' => $request['transaction_id']
          ];
        }
        case 'rollback':
        {
          $rollbackTransactions = [];

          foreach ($request['rollback_transactions'] as $rollback_transaction) {
            $refund($rollback_transaction['transaction_id'], floatval($rollback_transaction['amount']), true);
            $rollbackTransactions[] = $rollback_transaction['transaction_id'];
          }

          $balance = $currency->fiatNumberFormat($currency->convertTokenToEUR($userBalance->get()));

          return [
            'balance' => $balance,
            'transaction_id' => $request['transaction_id'],
            'rollback_transactions' => $rollbackTransactions
          ];
        }
        default:
          return [
            'error_code' => 'INTERNAL_ERROR',
            'error_message' => 'Unknown method ' . $type
          ];
      }
    } catch (\Exception $e) {
      Log::error('-- Slotegrator error');
      Log::error($e);

      return [
        'error_code' => 'INTERNAL_ERROR',
        'error_message' => 'Internal server error'
      ];
    }
  }

  function process(Data $data) {
    $metadata = $this->metadata();

    if (!in_array('live', $metadata->category())) {
      if ($data->demo() || $data->user() == null) {
        $response = Slotegrator::request('/games/init-demo', [
          'game_uuid' => str_replace("external:sg:", "", $metadata->id())
        ]);
      } else {
        $response = Slotegrator::request('/games/init', [
          'game_uuid' => str_replace("external:sg:", "", $metadata->id()),
          'player_id' => $data->user()->_id . '-' . $data->currency(),
          'player_name' => $data->user()->name,
          'currency' => 'EUR',
          'session_id' => ProvablyFair::generateServerSeed()
        ]);
      }

      if (!isset($response['data']['url'])) {
        switch ($response['status']) {
          case 403:
            $message = 'The authenticated user is not allowed to access the specified API endpoint.';
            break;
          case 401:
            $message = 'Authentication failed.';
            break;
          default:
            Log::error('Slotegrator error');
            Log::error($response);
            $message = 'Error during game initialization';
            break;
        }
        return ['error' => ['message' => $message]];
      }

      return [
        'response' => [
          'id' => '-1',
          'wager' => $data->bet(),
          'type' => 'third-party',
          'link' => $response['data']['url']
        ]
      ];
    } else {
      throw new UnsupportedOperationException();
    }
  }

  public function createInstances(): array {
    $games = [];

    $blacklist = [
      '3a70598171a689fe57eb4ccab7333bacac26c1b9' // Quickspin "test"
    ];

    $gameNames = [];

    foreach ($this->data as $game) {
      $name = str_replace(" Mobile", "", $game['name']);

      if (!in_array($name, $gameNames) && !in_array($game['uuid'], $blacklist) && $game['technology'] === 'HTML5') {
        $games[] = new SlotegratorGame($this->providerId, $game);
        $gameNames[] = $name;
      }
    }

    return $games;
  }

}
