<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tournaments; // Add this line to import the Tournament class
use App\Models\TournamentParticipant; // Add this line to import the TournamentParticipant class
class TournamentManager extends Controller
{
    public function getTournamentList(Request $request)
    {
        $tournament = Tournaments::withCount('participants')->get();
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
            debitBal($request->user()->id, $tournament->entryFee, 0, 'deposit_wallet', 'Tournament Entry Fee');
            $joinNew = new TournamentParticipant;
            $joinNew->userId = $request->user()->id;
            $joinNew->tournamentId = $request->tournament_id;
            $joinNew->winCount = 0;
            $joinNew->roundsPlayed = 0;
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
}
