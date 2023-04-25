<?php

namespace App\Console\Commands;

use App\Models\GameAnalytics;
use Illuminate\Console\Command;

class ClearGameAnalytics extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'phoenix:clearTodayAnalytics';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Clear all daily game analytics';

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
    $this->info('Deleting...');

    GameAnalytics::where('type', 'daily')->delete();

    $this->info('Done');
  }

}
