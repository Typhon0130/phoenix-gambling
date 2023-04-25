<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteBots extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'phoenix:deleteBots';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Deletes all bots from database';

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
    $this->info('Processing...');
    User::where('bot', '!=', null)->delete();
    $this->info('Done');
    return 1;
  }

}
