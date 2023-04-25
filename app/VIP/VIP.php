<?php namespace App\VIP;

use App\Models\Settings;

class VIP {

    public function level(int $level): VIPLevelData {
        $default = $this->defaultLevel($level);

        return new VIPLevelData(
            $level,
            Settings::get('[VIP] Name ' . $level, $default->name),
            floatval(Settings::get('[VIP] Deposit Requirement ' . $level, $default->depositRequirement)),
            floatval(Settings::get('[VIP] Wager Requirement ' . $level, $default->wagerRequirement)),
            floatval(Settings::get('[VIP] Number of Withdrawals ' . $level, $default->numberOfWithdrawals)),
            floatval(Settings::get('[VIP] Max. Withdrawal '. $level, $default->maxWithdrawal)),
            floatval(Settings::get('[VIP] Withdraw Fee ' . $level, $default->withdrawFee)),
            floatval(Settings::get('[VIP] One Time Bonus ' . $level, $default->oneTimeBonus)),
            floatval(Settings::get('[VIP] Referral Deposit Fee ' . $level, $default->referralDepositFee)),
            floatval(Settings::get('[VIP] Invite Bonus ' . $level, $default->inviteBonus)),
            floatval(Settings::get('[VIP] Level Protection ' . $level, $default->levelProtection))
        );
    }

    public function defaultLevel(int $level): ?VIPLevelData {
        switch ($level) {
            case 0: return new VIPLevelData($level, "Player", 0, 0, 1, 500, 2.5, 0, 1, 1, 0);
            case 1: return new VIPLevelData($level, "Pro I", 100, 800, 2, 2500, 2.5, 5, 2, 5, 100);
            case 2: return new VIPLevelData($level, "Pro II", 500, 4000, 2, 5000, 2.5, 10, 3, 10, 200);
            case 3: return new VIPLevelData($level, "Pro III", 1000, 15000, 2, 5000, 2.5, 20, 4, 20, 300);
            case 4: return new VIPLevelData($level, "Epic I", 2500, 35000, 3, 5000, 2.5, 50, 5, 50, 400);
            case 5: return new VIPLevelData($level, "Epic II", 5000, 75000, 3, 5000, 2, 100, 6, 100, 500);
            case 6: return new VIPLevelData($level, "Elite I", 10000, 125000, 3, 10000, 2, 200, 7, 200, 600);
            case 7: return new VIPLevelData($level, "Elite II", 15000, 200000, 4, 10000, 1.5, 500, 8, 500, 700);
            case 8: return new VIPLevelData($level, "Grand Elite I", 30000, 500000, 4, 10000, 1.5, 1000, 9, 1000, 800);
            case 9: return new VIPLevelData($level, "Grand Elite II", 50000, 750000, 5, 20000, 1, 2000, 10, 2000,  900);
            case 10: return new VIPLevelData($level, "Legend", 100000, 1000000, 5, 20000, 1, 5000, 20, 5000, 1000);
            default: return null;
        }
    }

}

class VIPLevelData {

    public string $name;
    private int $level;
    public float $depositRequirement, $wagerRequirement, $numberOfWithdrawals, $maxWithdrawal, $withdrawFee, $oneTimeBonus, $referralDepositFee, $inviteBonus, $levelProtection;

    public function __construct(int $level, string $name, float $depositRequirement, float $wagerRequirement, float $numberOfWithdrawals,
                                float $maxWithdrawal, float $withdrawFee, float $oneTimeBonus, float $referralDepositFee, float $inviteBonus,
                                float $levelProtection) {
        $this->level = $level;
        $this->name = $name;
        $this->depositRequirement = $depositRequirement;
        $this->wagerRequirement = $wagerRequirement;
        $this->numberOfWithdrawals = $numberOfWithdrawals;
        $this->maxWithdrawal = $maxWithdrawal;
        $this->withdrawFee = $withdrawFee;
        $this->oneTimeBonus = $oneTimeBonus;
        $this->referralDepositFee = $referralDepositFee;
        $this->inviteBonus = $inviteBonus;
       $this->levelProtection = $levelProtection;
    }

    public function set(string $key, string $value): VIPLevelData {
        switch ($key) {
            case 'name': $key = '[VIP] Name ' . $this->level; break;
            case 'depositRequirement': $key = '[VIP] Deposit Requirement ' . $this->level; break;
            case 'wagerRequirement': $key = '[VIP] Wager Requirement ' . $this->level; break;
            case 'numberOfWithdrawals': $key = '[VIP] Number of Withdrawals ' . $this->level; break;
            case 'maxWithdrawal': $key = '[VIP] Max. Withdrawal ' . $this->level; break;
            case 'withdrawFee': $key = '[VIP] Withdraw Fee ' . $this->level; break;
            case 'oneTimeBonus': $key = '[VIP] One Time Bonus ' . $this->level; break;
            case 'referralDepositFee': $key = '[VIP] Referral Deposit Fee ' . $this->level; break;
            case 'inviteBonus': $key = '[VIP] Invite Bonus ' . $this->level; break;
           case 'levelProtection': $key = '[VIP] Level Protection ' . $this->level; break;
        }

        Settings::set($key, $value);
        return $this;
    }

}
