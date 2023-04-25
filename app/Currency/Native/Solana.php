<?php namespace App\Currency\Native;

use App\Currency\Currency;
use App\Currency\CurrencyTransactionResult;
use App\Currency\Option\WalletOption;
use App\Models\User;
use Bezhanov\Ethereum\Converter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class Solana extends Currency {

  public const MAINNET_ENDPOINT = 'https://api.mainnet-beta.solana.com';
  public const MAINNET_ENDPOINT_NO_RATELIMIT = 'https://solana-api.projectserum.com';

  function id(): string {
    return "native_sol";
  }

  public function walletId(): string {
    return "sol";
  }

  function name(): string {
    return "SOL";
  }

  public function alias(): string {
    return "solana";
  }

  public function displayName(): string {
    return "Solana";
  }

  function icon(): string {
    return "sol";
  }

  public function style(): string {
    return "#627eea";
  }

  public function isRunning(): bool {
    return true;
  }

  public function newWalletAddress(?\App\Models\User $user, ?string $chainId = null): string {
    $randomId = rand();

    $process = new Process([
      PHP_OS_FAMILY === 'Windows' ? base_path('nodes/solana/windows/solana-keygen.exe') : base_path('nodes/solana/linux/solana-keygen'),
      'new',
      '--no-bip39-passphrase',
      '-f',
      '--outfile',
      base_path('nodes/solana/' . $randomId . '.json')
    ]);
    $process->run();

    $data = explode("\n", $process->getOutput());
    $pubkey = explode("pubkey: ", $data[3])[1];
    //$seed = $data[5];

    Storage::disk('local')->put($pubkey . '.json', file_get_contents(base_path('nodes/solana/' . $randomId . '.json')));
    unlink(base_path('nodes/solana/' . $randomId . '.json'));

    return $pubkey;
  }

  private function balance($account): float {
    if($account === '1') return 0;
    return floatval((new Converter())->fromWei($this->request('getBalance', [$account])['value'], 'gwei'));
  }

  public function setupWallet() {
    $this->option('transfer_address', $this->newWalletAddress());
  }

  public function isTokenAccountFunded(string $tokenAccount): bool {
    return $this->balance($tokenAccount) > 0;
  }

  public function findAssociatedTokenAccount(string $owner, string $mint): string {
    $process = new Process([
      'node',
      base_path('utils/get_associated_token_account.mjs'),
      $owner,
      $mint
    ]);
    $process->run();
    $process->wait();

    return str_replace("\n", "", $process->getOutput());
  }

  public function sendToken(string $toWalletAddress, string $tokenAddress, string $tokenOwnerAddress, float $amount = 1): string {
    $process = new Process([
      PHP_OS_FAMILY === 'Windows' ? base_path('nodes/solana/windows/spl-token.exe') : base_path('nodes/solana/linux/spl-token'),
      'transfer',
      $tokenAddress,
      $amount,
      $toWalletAddress,
      '--allow-unfunded-recipient',
      '-u', 'mainnet-beta',
      '--fund-recipient',
      '--owner',
      base_path('storage/app/' . $tokenOwnerAddress . '.json'),
      '--fee-payer',
      base_path('storage/app/' . $this->option('wallet') . '.json')
    ], null, null, null, null);

    $process->run();
    $process->wait();

    $output = $process->getOutput() ?? '';
    $errorOutput = $process->getErrorOutput() ?? '';

    Log::info($output);
    Log::info($errorOutput);

    return str_contains($output, "Signature");
  }

  public function send(string $from, string $to, float $sum) {
    $process = new Process([
      PHP_OS_FAMILY === 'Windows' ? base_path('nodes/solana/windows/solana.exe') : base_path('nodes/solana/linux/solana'),
      'transfer',
      $to,
      $sum - 0.000005,
      '-u', 'mainnet-beta',
      '--allow-unfunded-recipient',
      '--no-wait',
      '--from',
      base_path('storage/app/' . $from . '.json'),
      '--fee-payer',
      base_path('storage/app/' . $from . '.json')
    ]);
    $process->run();
  }

  public function coldWalletBalance(): float {
    return $this->balance($this->option('wallet'));
  }

  public function process(string $wallet = null): string {
    $transaction = $this->request('getTransaction', [$wallet, "json"]);
    if ($transaction == null) return CurrencyTransactionResult::$invalidTransaction;
    //$slot = $this->request('getSlot');

    // Transaction from user web wallet to main wallet, should be ignored, otherwise deposit will be doubled!
    if (in_array($this->option('wallet'), $transaction['transaction']['message']['accountKeys'])) return CurrencyTransactionResult::$doublePrevention;

    //Log::info($transaction);

    $hasDeposit = false;


    foreach ($transaction['transaction']['message']['accountKeys'] as $index => $accountKey) {
      $sum = abs($transaction['meta']['postBalances'][$index] - $transaction['meta']['preBalances'][$index]);

      if ($this->accept(/*$slot - $transaction['slot']*/ intval($this->option('confirmations')), $accountKey, $wallet, (new Converter())->fromWei($sum, 'gwei')))
        $hasDeposit = true;
    }

    if ($hasDeposit) return CurrencyTransactionResult::$success;
    else return CurrencyTransactionResult::$invalidRecipientAddress;
  }

  public function request(string $method, array $params = []) {
    $curl = curl_init(self::MAINNET_ENDPOINT_NO_RATELIMIT);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json"
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
      "jsonrpc" => "2.0",
      "id" => "1",
      "method" => $method,
      "params" => $params
    ]));

    $json_response = curl_exec($curl);
    $result = json_decode($json_response, true);

    curl_close($curl);

    //Log::info(json_encode($result));

    if (!isset($result['result'])) {
      //Log::warning('Invalid SOL request');
      //Log::warning($result);
      return null;
    }

    return $result['result'];
  }

  protected function options(): array {
    return [];
  }

  public function url(): ?string {
    return "https://solscan.io/account/%s";
  }

}
