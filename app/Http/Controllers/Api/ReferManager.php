<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Add this line
use Illuminate\Support\Facades\Http; // Add this line

class ReferManager extends Controller
{
    public function leaderBoard(Request $request)
    {
        $leaderboard = User::select('users.id', 'users.fname', 'users.lname', 'users.referCode')
        ->addSelect(DB::raw('COUNT(DISTINCT referred_users.id) as referral_count'))
        ->addSelect(DB::raw('COALESCE(SUM(CASE WHEN transactions.remark = "fund_added" THEN transactions.amount ELSE 0 END), 0) as total_referral_deposits'))
        ->leftJoin('users as referred_users', 'users.referCode', '=', 'referred_users.refBy')
        ->leftJoin('transactions', function ($join) {
            $join->on('referred_users.id', '=', 'transactions.userId')
                ->where('transactions.remark', '=', 'fund_added');
        })
        ->groupBy('users.id', 'users.fname', 'users.lname', 'users.referCode')
        ->havingRaw('referral_count > 0')  // Only include users with at least one referral
        ->orderByDesc('referral_count')
        ->orderByDesc('total_referral_deposits')
        ->limit(10)
        ->get();

        // $leaderboard->transform(function ($item) {
        //     $item->total_deposit = $item->transactions_sum_amount;
        //     $item->profilePic = "https://api.dicebear.com/9.x/initials/png?seed=".$item->fname.'+'.$item->lname;
        //     return $item;
        // });

      

        return response()->json([
            'status' => true,
            'data' => $leaderboard,
        ]);
    }
    public function myReferrals(Request $request)
    {
        $userId = $request->user()->id;
        $referrals = User::select('users.fname', 'users.lname')
            ->selectSub(function ($query) {
                $query->selectRaw('COALESCE(SUM(amount), 0)')
                    ->from('transactions')
                    ->where('walletType', 'winning_wallet')
                    ->whereColumn('userId', 'users.id');
            }, 'total_winning')
            //->selectRaw('(@row_number:=@row_number + 1) as rank')
            ->where('refBy', $userId)
            ->orderBy('total_winning', 'DESC')
            ->paginate(10);
            // $referrals = User::select('users.fname', 'users.lname')
            // ->selectSub(function ($query) {
            //     $query->selectRaw('COALESCE(SUM(amount), 0)')
            //         ->from('transactions')
            //         ->where('walletType', 'winning_wallet')
            //         ->whereColumn('userId', 'users.id');
            // }, 'total_winning')
            // ->selectRaw('ROW_NUMBER() OVER (ORDER BY total_winning DESC) as rank')
            // ->where('refBy', $userId)
            // ->orderBy('total_winning', 'DESC')
            // ->paginate(10);
        $referrals->transform(function ($item) {
            $item->profilePic = "https://api.dicebear.com/9.x/initials/png?seed=".$item->fname.'+'.$item->lname;
          //  $item->rank = $item->getRank();
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $referrals,
        ]);
    }
}
