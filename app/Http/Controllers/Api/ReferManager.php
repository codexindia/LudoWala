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
        $leaderboard = User::select('users.id as userId','users.fname','users.lname', DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE walletType = "deposit_wallet" AND userId = ref.id AND remark = "fund_added") as total_deposit'), DB::raw('ROW_NUMBER() OVER (ORDER BY referral_count DESC) as rank'))
            ->leftJoin('users as ref', 'users.id', '=', 'ref.refBy')
            ->leftJoin(DB::raw("(SELECT refBy, COUNT(*) as referral_count FROM users WHERE refBy IS NOT NULL GROUP BY refBy) as rc"), 'users.id', '=', 'rc.refBy')
            ->orderByDesc('referral_count')
            ->orderBy('users.id')
            ->limit(10)
            ->get();

        $leaderboard->transform(function ($item) {
           
            $item->total_deposit ??= 0;
            $item->profilePic = "https://api.dicebear.com/9.x/micah/svg?seed=".$item->fname.'+'.$item->lname;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $leaderboard,
        ]);
    }
}
