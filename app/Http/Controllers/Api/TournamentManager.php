<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tournaments; // Add this line to import the Tournament class

class TournamentManager extends Controller
{
   public function getTournamentList(Request $request)
   {
    $tournament = Tournaments::where('status','live')->get();
    return response()->json([
        'status' => true,
        'data' => $tournament,
    ]);
   }
}
