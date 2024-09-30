<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tournaments; // Add this line to import the Tournament class
use App\Models\TournamentParticipant; // Add this line to import the TournamentParticipant class
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class TournamentManager extends Controller
{
    public function getTournamentList(Request $request)
    {
        $userId = $request->user()->id;
        $tournament = Tournaments::withCount('participants')
            ->withExists(['participants as userJoined' => function ($query) use ($userId) {
                $query->where('userId', $userId);
                
            }])
            ->withExists(['participants as eliminated' => function ($query) use ($userId) {
                // Check if the user is eliminated by seeing if roundsPlayed != currentRound
                $query->where('userId', $userId)
                      ->where('roundsPlayed', '!=', DB::raw('tournaments.currentRound'));
            }])
            ->get();
            $tournament[0]['participants_count'] += $this->getFakeTotal();
        return response()->json([
            'status' => true,
            'data' => $tournament,
        ]);
    }
    public function joinTournament(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
        ]);
        if($request->tournament_id == 8)
        {
            return response()->json([
                'status' => false,
                'message' => 'Entry to this tournament is Closed',
            ]);
        }
        $checkIfAlreadyJoined = TournamentParticipant::where('userId', $request->user()->id)
            ->where('tournamentId', $request->tournament_id)
            ->first();
        if ($checkIfAlreadyJoined) {
            return response()->json([
                'status' => false,
                'message' => 'You have already joined the tournament',
            ]);
        }
        $tournament = Tournaments::find($request->tournament_id);
        if ($tournament->participants->count() < $tournament->maxPlayers) {
            if ($request->user()->deposit_wallet < $tournament->entryFee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance',
                ]);
            }
            $bonusCutAmount =  $tournament->entryFee * 0.02;
            if ($request->user()->bonus_wallet > $bonusCutAmount) {
                debitBal($request->user()->id, $bonusCutAmount, 0, 'bonus_wallet', 'Tournament Entry Fee 2% Bonus Cut');
                debitBal($request->user()->id, $tournament->entryFee - $bonusCutAmount, 0, 'deposit_wallet', 'Tournament Entry Fee');
            } else {
                debitBal($request->user()->id, $tournament->entryFee, 0, 'deposit_wallet', 'Tournament Entry Fee');
            }

            $joinNew = new TournamentParticipant;
            $joinNew->userId = $request->user()->id;
            $joinNew->tournamentId = $request->tournament_id;
            $joinNew->winCount = 0;
            $joinNew->roundsPlayed = 1;
            $joinNew->save();
            return response()->json([
                'status' => true,
                'message' => 'You have successfully joined the tournament',
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Sorry, the tournament is full',
        ]);
    }
    public function getFakeTotal()
    {
        $baseTotal = 300000;
        $incrementAmount = 75;
        $intervalMinutes = 1;
        $maxTotal = 314500; // 3 lakh

        // Get the current time
        $now = Carbon::now();

        // Calculate the time difference since a fixed start point
        $startTime = Carbon::create(2024, 8, 31, 17, 00, 0); // You can adjust this start time
        $diffInMinutes = $startTime->diffInMinutes($now);

        // Calculate how many 5-minute intervals have passed
        $intervals = floor($diffInMinutes / $intervalMinutes);

        // Calculate the total
        $total = $baseTotal + ($intervals * $incrementAmount);

        // Cap the total at 3 lakh (300,000)
        $total = min($total, $maxTotal);

        return $total;
    }
}
