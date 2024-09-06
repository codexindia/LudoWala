<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function joinRoom(Request $request)
    {
        return response()->json([
            'status' => true,
            'roomId' => 'demo123',
            'message' => 'Room Joined Successfully',
        ]);
    }
}
