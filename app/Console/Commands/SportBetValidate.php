<?php

namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\SportBet;
use App\Models\Transaction;
use App\Models\User;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Sport;
use Illuminate\Console\Command;

class SportBetValidate extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sport:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validates sport bets';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
      $checkedMultiBetIds = [];

      /* @var $sportBet SportBet */
      foreach (SportBet::where('status', 'ongoing')->get() as $sportBet) {
        if($sportBet->multiBetId !== null) {
          if(in_array($sportBet->_id, $checkedMultiBetIds)) continue;
          $checkedMultiBetIds[] = $sportBet->_id;
        }

        $sportBet->validate();
        $this->info('Checking: ' . $sportBet->_id);
      }
    }
}
