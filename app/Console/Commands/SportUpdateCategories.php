<?php

namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\PhoenixGamblingSportData;
use App\Models\SportBet;
use App\Models\Transaction;
use App\Models\User;
use App\Sport\Provider\PhoenixGambling\Market\NoOpMarketHandler;
use App\Sport\Provider\PhoenixGambling\PhoenixGamblingLine;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Sport;
use Illuminate\Console\Command;

class SportUpdateCategories extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sport:pg:updateCategories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh sport categories list';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
      (new PhoenixGamblingLine())->updateCategoryCache();
      return 1;
    }
}
