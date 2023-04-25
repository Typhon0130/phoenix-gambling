<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Currency\Currency;
use App\Events\ChatMessage;
use App\Models\Invoice;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Rain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phoenix:rain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send rain in chat';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $usersLength = mt_rand(5, 15);
        $all = Invoice::where('status', 1)->where('created_at', '>=', Carbon::today())->get()->toArray();
        if(count($all) < $usersLength) {
            $a = Invoice::where('status', 1)->get()->toArray();
            shuffle($a);
            $all += $a;
        }

        if(count($all) < $usersLength) return;

        shuffle($all);

        $dub = []; $users = [];
        foreach ($all as $invoice) {
            $user = User::where('_id', $invoice['user'])->first();
            if($user == null || in_array($invoice['user'], $dub)) continue;
            array_push($dub, $invoice['user']);
            array_push($users, $user);
        }

        $users = array_slice($users, 0, $usersLength);
        $result = [];

        $currency = Currency::all(false)[array_rand(Currency::all(false))];

        foreach ($users as $user) {
            $user->balance($currency)->add(floatval($currency->option('rain')), Transaction::builder()->message('Rain (Global)')->get());
            array_push($result, $user->toArray());
        }

        $message = Chat::create([
            'data' => [
                'users' => $result,
                'reward' => floatval($currency->option('rain')),
                'currency' => $currency->id(),
                'from' => null
            ],
            'type' => 'rain',
            'channel' => 'all'
        ]);

        event(new ChatMessage($message));
        $this->info('Success');
    }

}
