<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Settings\SettingName;
if (!function_exists('get_setting')) {
    function get_setting($key, $group = 'general')
    {
      return app(SettingName::class)->$key;
    }
}
function getTrx($length = 12)
{
    $characters       = 'ABCDEFGHJKMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function creditBal($userId, $amount, $charge = 0, $walletType = 'deposit_wallet', $description = null)
{
    DB::beginTransaction();

    try {

        $newtrx = new Transaction;
        $newtrx->userId = $userId;
        $newtrx->amount = $amount;
        $newtrx->charge = $charge;
        $newtrx->trxType = '+';
        $newtrx->trx = getTrx();
        $newtrx->description = $description;
        $newtrx->walletType = $walletType;
        $newtrx->save();
        User::find($userId)->increment($walletType, $amount);
        DB::commit();
        return 1;
    } catch (Exception $e) {
       // return $e->getMessage();
       // DB::rollBack();
        return false;
    };
}
