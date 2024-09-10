<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
use App\Models\BoardEvent;

class GameController extends Controller
{
    public function joinRoom(Request $request)
    {
        return response()->json([
            'status' => true,
            'userId' => $request->user()->id,
            'roomId' => 'demo123',
            'message' => 'Room Joined Successfully',
        ]);
    }
    public function eventStore(Request $request)
    {
        $request->validate([
            'tokenId' => 'required|in:A1,A2,A3,A4,B1,B2,B3,B4,C1,C2,C3,C4,D1,D2,D3,D4',
        ]);
        $getLastEvent = BoardEvent::where('userId', $request->user()->id)->where('roomId', 'demo123')->latest()->first();
        $diceValue = rand(1, 6);
        $event = new BoardEvent();
        $event->userId = $request->user()->id;
        $event->roomId = 'demo123';
        $event->tokenId = $request->tokenId;

        if ($request->tokenId == 'A1' || $request->tokenId == 'A2' || $request->tokenId == 'A3' || $request->tokenId == 'A4') {
            $event->playerId = 0;
        } elseif ($request->tokenId == 'B1' || $request->tokenId == 'B2' || $request->tokenId == 'B3' || $request->tokenId == 'B4') {
            $event->playerId = 1;
        } elseif ($request->tokenId == 'C1' || $request->tokenId == 'C2' || $request->tokenId == 'C3' || $request->tokenId == 'C4') {
            $event->playerId = 2;
        } elseif ($request->tokenId == 'D1' || $request->tokenId == 'D2' || $request->tokenId == 'D3' || $request->tokenId == 'D4') {
            $event->playerId = 3;
        }
        if ($getLastEvent) {
            $event->travelCount = $getLastEvent->travelCount + $diceValue;
            $event->position = $getLastEvent->position + $diceValue;
        } else {
            $event->travelCount = $diceValue;
            if ($event->playerId == 0) {
                $event->position = 14 + $diceValue;
            } elseif ($event->playerId == 1) {
                $event->position = 27 + $diceValue;
            } elseif ($event->playerId == 2) {
                $event->position = 40 + $diceValue;
            } elseif ($event->playerId == 3) {
                $event->position = 51 + $diceValue;
            }
        }
        if ($event->position == 14 || $event->position == 53 || $event->position == 40 || $event->position == 27) {
            $event->isSafe = '1';
        } else {
            $event->isSafe = '0';
        }
        $event->save();
        $this->forwardSocket('eventStored', ['tokenId' => $request->tokenId, 'position' => $event->position,'travelCount' => $event->travelCount], $request);
        return response()->json([
            'status' => true,
            'tokenId' => $request->tokenId,
            'diceValue' => $diceValue,
            'message' => 'Event Stored Successfully',
        ]);
    }
    public function rollDice(Request $request)
    {
        $request->validate([
            // 'roomId' => 'required',
            'playerId' => 'required|in:0,1,2,3',
        ]);
        $diceValue = rand(1, 6);
        $this->forwardSocket('diceRolled', ['diceValue' => $diceValue, 'playerId' =>  (int) $request->playerId], $request);
        return response()->json([
            'status' => true,
            'diceValue' => $diceValue,
            'playerId' => (int) $request->playerId,
            'message' => 'Dice Rolled Successfully',
        ]);
    }

    private function forwardSocket($event, $data = [], Request $request)
    {
        $options = [
            'auth' => [
                'token' => 'Bearer ' . $request->bearerToken(),
            ]
        ];
        // Create a new Socket.IO client
        $client = Client::create('http://socket.ludowalagames.com:3000/', $options);
        //  $client = new Client(new Version2X());

        // Connect to the Socket.IO server
        $client->connect();

        // Set the bearer token for authentication


        // Emit an event to the server
        $client->emit('sendMessage', [
            $event => $data,
        ]);

        // Close the connection
        $client->disconnect();

        return true;
    }
}
