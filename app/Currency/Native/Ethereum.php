<?php namespace App\Currency\Native;

use App\Currency\Currency;
use App\Currency\CurrencyTransactionResult;
use App\Currency\Option\WalletOption;
use App\Models\Settings;
use App\Models\User;
use Bezhanov\Ethereum\Converter;
use Illuminate\Support\Facades\Log;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;
use kornrunner\Ethereum\Address;

class Ethereum extends Currency {

  function id(): string {
    return "infura_eth";
  }

  public function walletId(): string {
    return "eth";
  }

  function name(): string {
    return "ETH";
  }

  public function alias(): string {
    return "ethereum";
  }

  public function displayName(): string {
    return "Ethereum";
  }

  function icon(): string {
    return "eth";
  }

  public function style(): string {
    return "#627eea";
  }

  public function isRunning(): bool {
    return true;
  }

  public function newWalletAddress(?\App\Models\User $user, ?string $chainId = null): string {
    $address = new Address();

    file_put_contents(storage_path('app/ethereumPrivateKeys/0x' . $address->get() . '.json'), json_encode([
      'address' => '0x' . $address->get(),
      'privateKey' => $address->getPrivateKey(),
      'publicKey' => $address->getPublicKey()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return '0x' . $address->get();
  }

  private function balance($account) {
    return number_format(floatval(Settings::get('ethereumBalance', '0')), 8, '.', '');
  }

  public function setupWallet() {}

  public function send(string $from, string $to, float $sum) {
    /*try {
      $password = User::where('wallet_native_eth', $from)->first()->_id;

      $this->getClient()->getPersonal()->unlockAccount($from, $password, function ($err, $unlocked) use ($to, $sum, $from) {
        if ($err != null) {
          Log::critical($err);
          return;
        }

        $gas = 84000;
        $gasPriceGwei = 85;

        $this->getClient()->getEth()->getBalance($from, function ($err, $balance) use ($sum, $gas, $gasPriceGwei, $to, $from) {
          $ethBalance = floatval((new Converter())->fromWei($balance, 'ether'));

          $txValue = $ethBalance - ($gasPriceGwei / 1000000000) * $gas;

          if ($gas * ($gasPriceGwei / 1000000000) > $ethBalance) {
            Log::error("Insufficient funds for gas*price+value");
            return;
          }

          $this->getClient()->getEth()->sendTransaction([
            'to' => $to,
            'from' => $from,
            'value' => '0x' . dechex(intval((new Converter())->toWei($txValue, 'ether'))),
            'gas' => '0x' . dechex($gas),
            'gasPrice' => '0x' . dechex($gasPriceGwei * 1000000000)
          ], function ($err) {
            if ($err !== null) Log::critical($err);
          });
        });
      });
    } catch (\Exception $e) {

    }*/
  }

  public function url(): ?string {
    return "https://etherscan.io/address/%s";
  }

  public function coldWalletBalance(): float {
    return $this->balance($this->option('transfer_address')) ?? -1;
  }

  protected function getClient() {
    return new Web3(new HttpProvider(new HttpRequestManager('https://mainnet.infura.io/v3/'.$this->option('infura_api_key'), 30)));
  }

  public function process(string $wallet = null): string {
    $hasDeposit = false;

    try {
      $this->getClient()->getEth()->getTransactionByHash($wallet, function ($err, $response) use (&$hasDeposit, $wallet) {
        if ($err != null) {
          Log::critical($err);
          return;
        }

        if ($response == null) {
          Log::error('Invalid native_eth transaction response (null) for ' . $wallet);
          return;
        }

        //Log::info(json_encode($response));

        //if(isset($response->blockNumber)) $confirmations = intval($number->toString()) - hexdec($response->blockNumber);
        if (isset($response->to) && isset($response->blockNumber)) {
          $confirmations = intval(Currency::find($this->id())->option('confirmations'));

          $sum = hexdec($response->value) / pow(10, 18);
          if ($this->accept($confirmations, $response->to, $wallet, $sum)) {
            $ethBalance = floatval(Settings::get('ethereumBalance', '0'));
            Settings::set('ethereumBalance', $ethBalance + $sum);

            $hasDeposit = true;
          }
        }
      });
    } catch (\Exception $e) {
      Log::error('eth deposit verification error: ' . $e->getMessage());
      return CurrencyTransactionResult::$invalidTransaction;
    }

    return $hasDeposit ? CurrencyTransactionResult::$success : CurrencyTransactionResult::$invalidRecipientAddress;
  }

  protected function options(): array {
    return [
      new class extends WalletOption {
        function id() {
          return "infura_api_key";
        }

        function name(): string {
          return "Infura API key";
        }

        public function description(): string {
          return "https://infura.io/";
        }
      }
    ];
  }

}
