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
        $leaderboard = User::select('users.id as userId','users.fname','users.lname')
            ->withCount('referrals')
            ->withCount(['transactions' => function ($query) {
                $query->where('walletType', 'deposit_wallet')
                    ->where('remark', 'fund_added');
            }])
            ->leftJoinSub(function ($query) {
                $query->select('refBy')
                    ->selectRaw('COUNT(*) as referral_count')
                    ->from('users')
                    ->whereNotNull('refBy')
                    ->groupBy('refBy');
            }, 'rc', 'users.id', '=', 'rc.refBy')
            ->orderByDesc('rc.referral_count')
            ->orderBy('users.id')
            ->limit(10)
            ->get();

        $leaderboard->transform(function ($item) {
           
            $item->total_deposit ??= 0;
            $item->profilePic = "https://api.dicebear.com/9.x/initials/png?seed=".$item->fname.'+'.$item->lname;
            return $item;
        });

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
