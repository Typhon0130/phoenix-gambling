<?php

namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\PhoenixGamblingSportData;
use App\Models\SportBet;
use App\Models\Transaction;
use App\Models\User;
use App\Sport\Provider\PhoenixGambling\Market\NoOpMarketHandler;
use App\Sport\Provider\SportGameSnapshot;
use App\Sport\Sport;
use Illuminate\Console\Command;

class SportRecheckLive extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sport:recheckLive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates status of live sport games in case if their live status was not updated';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
      foreach (Sport::getLine()->getCategories() as $category) {
        foreach ($category->getGames(true) as $game) {
          if($game->sportType() === 'SPORTS' && $game->isLive()) {
            $this->info('Checking ' . $game->id() . '...');

            if((new NoOpMarketHandler())->getData($game->id())->match()->isFinished()) {
              $model = PhoenixGamblingSportData::where('srId', $game->id())->first();
              if(!$model) continue;

              $model->update([
                'status' => 'DISABLED',
                'matchStatus' => 'Closed'
              ]);

              $this->info(' * Changed status to ended');
            }
          }
        }
      }

      return 1;
    }
}
