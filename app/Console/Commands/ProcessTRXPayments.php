<?php namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTRXPayments extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'win5x:processTrxPayments';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Check recent payments';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    $currency = Currency::find('tron_trx');
    if(!$currency) {
      $this->info('Enable TRX to continue.');
      return 0;
    }

    $this->info('Starting...');

    DB::table('users')->where('bot', '!=', true)->where('latest_activity', '>=', now()->subMinutes(5))
        ->orderBy('created_at', 'desc')->chunk(10, function($users) use($currency) {
      foreach($users as $user) {
        $wallet = $user['wallet_tron_trx'] ?? null;
        if($wallet == null) continue;

        $this->info('Checking ' . $wallet . ' (' . $user['name'] . ')');

        try {
          $currency->process($wallet);
        } catch (\Exception $e) {
          Log::critical($e);
        }
      }
    });

    $this->info('Done');
    return 1;
  }

}
