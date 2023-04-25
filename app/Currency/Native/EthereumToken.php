<?php namespace App\Currency\Native;

use App\Currency\Currency;
use App\Currency\CurrencyTransactionResult;
use App\Currency\Utils\EthereumAbi;
use App\Currency\Utils\InputDataDecoder;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Web3\Contract;

abstract class EthereumToken extends Ethereum {

  public abstract function tokenAddress(): string;

  public abstract function abi(): string;

  public function isToken(): bool {
    return true;
  }

  public function coldWalletBalance(): float {
    return -1;
    //return json_decode(file_get_contents('https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=' . $this->tokenAddress() .'&address=' .  . '&tag=latest'))
  }

  public function send(string $from, string $to, float $sum) {

  }

  public function process(string $wallet = null): string {
    $hasDeposit = false;

    try {
      $web3 = $this->getClient();
      $web3->getEth()->getTransactionByHash($wallet, function ($err, $response) use (&$hasDeposit, $wallet, &$web3) {
        if ($err != null) {
          Log::critical($err);
          return;
        }

        if ($response == null) {
          Log::error('Invalid native_eth transaction response (null) for ' . $wallet);
          return;
        }

        if (isset($response->to) && isset($response->blockNumber)) {
          if($response->to !== $this->tokenAddress()) return;
          $confirmations = intval(Currency::find($this->id())->option('confirmations'));

          $data = (new InputDataDecoder(json_decode($this->abi())))->decodeData($response->input);

          $contract = new Contract($web3->provider, $this->abi());
          $address = $contract->bytecode($response->input)->ethabi->decodeParameter('address', $data->inputs[0]);

          $div = $this->decimals() < 8 ? 1_000_000 : intval('1' . str_repeat('0', $this->decimals()));
          /** @noinspection PhpCastIsUnnecessaryInspection */
          $value = floatval(strval($contract->bytecode($response->input)->ethabi->decodeParameter('uint256', $data->inputs[1]))) / $div;

          if($this->accept($confirmations, $address, $wallet, $value)) {
            $hasDeposit = true;
          }
        }
      });
    } catch (\Exception $e) {
      Log::error('eth token deposit verification error: ' . $e->getMessage());
      Log::error($e);
      return CurrencyTransactionResult::$invalidTransaction;
    }

    return $hasDeposit ? CurrencyTransactionResult::$success : CurrencyTransactionResult::$invalidRecipientAddress;
  }

  private function balance(string $account): float {
    try {
      $web3 = $this->getClient();

      $result = -1;
      $contract = new Contract($web3->provider, $this->abi());
      $contract->at($this->tokenAddress())->call('balanceOf', $account, function ($err, $balance) use (&$result) {
        $result = strval($balance['balance']);
      });

      return floatval($result) / 1000000;
    } catch (\Exception) {
      return 0;
    }
  }

}
