<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiceRolling;
use App\Models\RoomDetails;
use ElephantIO\Engine\SocketIO\Version3X;
use Illuminate\Http\Request;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
use App\Models\BoardEvent;

class GameController extends Controller
{
    private $roomId = 'demo123';
    public function joinRoom(Request $request)
    {
        $checkIfUserJoined = RoomDetails::where('roomId', $this->roomId)->where('userId', $request->user()->id)->latest()->first();
        // return $checkIfUserJoined;


        if ($checkIfUserJoined) {
            $events = BoardEvent::where('roomId', $this->roomId)->get(['userId', 'tokenId', 'playerId', 'position', 'travelCount']);
            //     $this->forwardSocket('roomReJoined', [
            //         'playerId' => $checkIfUserJoined->playerId,
            //         'roomId' => $checkIfUserJoined->roomId
            //    ], $request);
            $currentTurn = RoomDetails::where('roomId', $this->roomId)->where('currentTurn', 1)->first('playerId')->playerId;
            return response()->json([
                'status' => true,
                'playerId' => $checkIfUserJoined->playerId,
                'roomId' => $checkIfUserJoined->roomId,
                'currentTurn' => $currentTurn,
                'message' => 'User Already Joined the Room',
                'events' => $events,
            ]);
        }

        $checkLastRoom = RoomDetails::where('roomId', $this->roomId)->count();

        if ($checkLastRoom > 3) {
            return response()->json([
                'status' => false,
                'message' => 'Room is Full',
            ]);
        }
        $setIntialDice = new DiceRolling();
        $newRoom = new RoomDetails();
        if ($checkLastRoom) {
            $newRoom->playerId = $checkLastRoom;
            //to give first chance to player id 0
            $setIntialDice->currentTurn = 0;
        } else {
            $newRoom->currentTurn = 1;
            $newRoom->playerId = 0;
            //to give first chance to player id 0
            $setIntialDice->currentTurn = 1;
        }
        $newRoom->roomId = $this->roomId;
        $newRoom->userId = $request->user()->id;
        $newRoom->save();


        //set intial dice value
        $setIntialDice->userId = $request->user()->id;
        $setIntialDice->playerId = $newRoom->playerId;
        $setIntialDice->roomId = $this->roomId;


        $setIntialDice->save();
        //set intial dice value end
        //set the initial position of the user
        foreach ($this->getTokenByPid($newRoom->playerId) as $token) {
            $event = new BoardEvent();
            $event->userId = $request->user()->id;
            $event->roomId = $this->roomId;
            $event->tokenId = $token;
            $event->playerId = $newRoom->playerId;
            $event->position = $this->getInitialPositionByPid($newRoom->playerId);
            $event->travelCount = 0;
            $event->save();
        }


        $this->forwardSocket('roomJoined', ['playerId' => $newRoom->playerId, 'roomId' => $this->roomId], $request);

        return response()->json([
            'status' => true,
            'playerId' => $newRoom->playerId,
            'roomId' => $this->roomId,
            'message' => 'Room Joined Successfully',
        ]);
    }
    public function eventStore(Request $request)
    {
        $request->validate([
            'tokenId' => 'required|in:A1,A2,A3,A4,B1,B2,B3,B4,C1,C2,C3,C4,D1,D2,D3,D4',
        ]);
        $userId = $request->user()->id;
        $gameMode = 'tournament';
        $checkUserJoined = RoomDetails::where('userId', $userId)->where('roomType', $gameMode)->first();
        $getLastDice = DiceRolling::where('userId', $userId)->where('roomId', $this->roomId)->first();
        //to get the last event of the user
        if ($checkUserJoined->playerId != $this->getPlayerId($request->tokenId) && $checkUserJoined->currentTurn == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Not Your Turn',
            ]);
        }
        $checkUserJoined->update(['currentTurn' => 0]);



        $getLastEvent = BoardEvent::where('userId', $request->user()->id)->where('tokenId', $request->tokenId)->where('roomId', $this->roomId)->first();
        // return $getLastEvent;
        $diceValue = $getLastDice->diceValue;


        if ($getLastEvent) {
            $event = $getLastEvent;
        } else {
            $event = new BoardEvent();
        }
        $event->userId = $request->user()->id;
        $event->roomId = $this->roomId;
        $event->tokenId = $request->tokenId;
        //to determine  get the playerId of the user
        $event->playerId = $this->getPlayerId($request->tokenId);
        //to determine the position of the user
        if ($getLastEvent) {

            $event->travelCount = $getLastEvent->travelCount + $diceValue;
            $event->position = $getLastEvent->position + $diceValue;
            //to check if the user crossed 52 position then reset from 1
            if ($event->position > 52) {
                $event->position = $event->position - 52;
            }
            //entering to wining area and check they complete their travel or not
            if ($this->getPlayerId($request->tokenId) == 0 && $event->travelCount > 51) {
                $event->position = 220 + ($event->position - 12);
            } elseif ($this->getPlayerId($request->tokenId) == 1 && $event->travelCount > 51) {
                $event->position = 330 + ($event->position - 25);
            } elseif ($this->getPlayerId($request->tokenId) == 2 && $event->travelCount > 51) {
                $event->position = 440 + ($event->position - 38);
            } elseif ($this->getPlayerId($request->tokenId) == 3 && $event->travelCount > 51) {
                $event->position = 110 + ($event->position - 51);
            }
        } else {
            //to determine the initial position of the user
            $event->travelCount = $diceValue;
            $event->position = $this->getInitialPosition($request->tokenId) + $diceValue;
        }
        //to determine the user is safe or not
        $safePositions = [14, 53, 40, 27, 9, 22, 48, 35];
        $event->isSafe = in_array($event->position, $safePositions) ? '1' : '0';
        $event->save();
        //to determine the next turn
        $nextTurn = RoomDetails::where('roomId', $this->roomId)->count() == $event->playerId + 1 ? 0 : $event->playerId + 1;
        $changeNext = RoomDetails::where('roomId', $this->roomId)->where('playerId', operator: $nextTurn)->update(['currentTurn' => 1]);
        //to check if the token is returned to the home
        $CheckAnyTokenReturned = BoardEvent::where('position', $event->position)->where('roomId', $this->roomId)->whereNot('tokenId', $request->tokenId)->where('isSafe', '0')->first();

        //to forward the event to the socket
        $this->forwardSocket('tokenMoved', [
            'tokenId' => $request->tokenId,
            'playerId' => $event->playerId,
            'position' => $event->position,
            'travelCount' => $event->travelCount,
            'nextTurn' => $nextTurn
        ], $request);
        //to check if the token is returned to the home
        if ($CheckAnyTokenReturned) {
            $CheckAnyTokenReturned->position = $this->getInitialPosition($CheckAnyTokenReturned->tokenId);
            $CheckAnyTokenReturned->travelCount = 0;
            $CheckAnyTokenReturned->save();
            $this->forwardSocket(
                'tokenMoved',
                [
                    'tokenId' => $CheckAnyTokenReturned->tokenId,
                    'playerId' => $this->getPlayerId($CheckAnyTokenReturned->tokenId),
                    'position' => $CheckAnyTokenReturned->position,
                    'travelCount' => $CheckAnyTokenReturned->travelCount,
                    'nextTurn' => $nextTurn,
                ],
                $request
            );
            return response()->json([
                'status' => true,
                'tokenId' => $request->tokenId,
                'diceValue' => $diceValue,
                'message' => 'Event Stored Successfully',
            ]);
        }
        //remove dice chance 

        $getLastDice->update(['currentTurn' => 0]);
        //remove dice chance 
        //give dice chance to next player
         RoomDetails::where('roomId', $this->roomId)->where('playerId', $nextTurn)->first()->update(['currentTurn' => 1]);

        //to return the response
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
                //   'token' => "Bearer 4441|bOAG2ubqGDG5XuZoEXlJ6BCQezaRrTyod7FsIZrbc23ccc4b",               
                'token' => 'Bearer ' . $request->bearerToken(),
            ]
        ];
        // Create a new Socket.IO client
        // $client = new Client(new Version3X('wss://socket.ludowalagames.com:3000', $options));
        $client = Client::create('wss://socket.ludowalagames.com:3000', $options);


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
    private function getPlayerId($tokenId)
    {
        return match ($tokenId) {
            'A1', 'A2', 'A3', 'A4' => 0,
            'B1', 'B2', 'B3', 'B4' => 1,
            'C1', 'C2', 'C3', 'C4' => 2,
            'D1', 'D2', 'D3', 'D4' => 3,
            default => null,
        };
    }
    private function getInitialPosition($tokenId)
    {
        return match ($tokenId) {
            'A1', 'A2', 'A3', 'A4' => 14,
            'B1', 'B2', 'B3', 'B4' => 27,
            'C1', 'C2', 'C3', 'C4' => 40,
            'D1', 'D2', 'D3', 'D4' => 53,
            default => null,
        };
    }
    public function getTokenByPid($playerId)
    {
        return match ($playerId) {
            0 => ['A1', 'A2', 'A3', 'A4'],
            1 => ['B1', 'B2', 'B3', 'B4'],
            2 =>  ['C1', 'C2', 'C3', 'C4'],
            3 => ['D1', 'D2', 'D3', 'D4'],
            default => null,
        };
    }
    private function getInitialPositionByPid($tokenId)
    {
        return match ($tokenId) {
            0 => 14,
            1 => 27,
            2 => 40,
            3 => 53,
            default => null,
        };
    }
    public function reFetch(Request $request)
    {
        $request->validate([
            'roomId' => 'required|exists:room_details,roomId',
        ]);
        $currentTurn = BoardEvent::where('roomId', $this->roomId)->latest('updated_at')->first('playerId')->playerId;
        $events = BoardEvent::where('roomId', $this->roomId)->get(['userId', 'tokenId', 'playerId', 'position', 'travelCount']);
        return response()->json([
            'status' => true,
            'events' => $events,
            'currentTurn' => $currentTurn,
            'message' => 'Events Fetched Successfully',
        ]);
    }
}
