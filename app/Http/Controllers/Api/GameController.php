<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiceRolling;
use App\Models\RoomDetails;
use App\Models\TournamentParticipant;
use App\Models\Tournaments;
use Carbon\Carbon;
use ElephantIO\Engine\SocketIO\Version3X;
use Illuminate\Http\Request;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
use App\Models\BoardEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    private $roomId;

    public function joinRoom(Request $request)
    {
        //   return false;
        $gameType = 'tournament';
        if($gameType == "tournament")
        {
            $tournamentId = 6;
            $tournament = Tournaments::where('id', $tournamentId)->first();
            $endTime = null;
            if($tournament->currentRound == 1)
            {
                $endTime = Carbon::parse($tournament->startTime)->addMinutes(10)->toDateTimeString();
            }else{
                $endTime = Carbon::parse($tournament->nextRoundTime)->addMinutes(10)->toDateTimeString();
            }
            $tournamentParticipant = TournamentParticipant::where('userId', $request->user()->id)->where('tournamentId', $tournamentId)->first();
    //   if($tournamentParticipant->roundPlayed != $tournament->currentRound)
    //   {
    //       return response()->json([
    //           'status' => false,
    //           'message' => 'You are not eligible to join this round',
    //       ]);
    //   }
        }
        $checkIfUserJoined = RoomDetails::where('userId', $request->user()->id)->where('roomType', $gameType)->first();
        // return $checkIfUserJoined;
        if ($checkIfUserJoined) {
            $players = RoomDetails::where('roomId', $checkIfUserJoined->roomId)
                ->join('users', 'users.id', '=', 'room_details.userId')
                ->get(['room_details.userId', 'room_details.playerId', 'users.fname', 'users.lname']);
            $events = BoardEvent::where('roomId', $checkIfUserJoined->roomId)->get(['userId', 'tokenId', 'playerId', 'position', 'travelCount']);
          
            $currentTurn = RoomDetails::where('roomId', $checkIfUserJoined->roomId)->where('currentTurn', 1)->first('playerId')->playerId;
            return response()->json([
                'status' => true,
                'playerId' => $checkIfUserJoined->playerId,
                'roomId' => $checkIfUserJoined->roomId,
                'currentTurn' => $currentTurn,
                'players' => $players,
                'message' => 'User Already Joined the Room',
                'events' => $events,
                'endTime' =>  $endTime
            ]);
        }
        $lastRoom = RoomDetails::where(column: 'roomType', operator: 'tournament')->latest('created_at')->first();
        if ($lastRoom == null) {
            $roomId = 'LW' . rand(1000000000, 9999999999);
        } else {
            $checkLastRoom =  RoomDetails::where('roomId', $lastRoom->roomId)->count();
            if ($checkLastRoom  >= 4) {
                $roomId = 'LW' . rand(1000000000, 9999999999);
            } else {
                $roomId = $lastRoom->roomId;
            }
        }

        $setIntialDice = new DiceRolling();
        $newRoom = new RoomDetails();
        if (isset($checkLastRoom) && $checkLastRoom < 4) {
            $newRoom->playerId = $checkLastRoom;
            //to give first chance to player id 0
            $setIntialDice->currentTurn = 0;
        } else {
            $newRoom->currentTurn = 1;
            $newRoom->playerId = 0;
            //to give first chance to player id 0
            $setIntialDice->currentTurn = 1;
        }
        $newRoom->roomId = $roomId;
        $newRoom->userId = $request->user()->id;
        $newRoom->save();


        //set intial dice value
        $setIntialDice->userId = $request->user()->id;
        $setIntialDice->playerId = $newRoom->playerId;
        $setIntialDice->roomId = $roomId;


        $setIntialDice->save();
        //set intial dice value end
        //set the initial position of the user
        foreach ($this->getTokenByPid($newRoom->playerId) as $token) {
            $event = new BoardEvent();
            $event->userId = $request->user()->id;
            $event->roomId = $roomId;
            $event->tokenId = $token;
            $event->playerId = $newRoom->playerId;
            $event->position = $this->getInitialPositionByPid($newRoom->playerId);
            $event->travelCount = 0;
            $event->save();
        }
        $players = RoomDetails::where('roomId', $roomId)
            ->join('users', 'users.id', '=', 'room_details.userId')
            ->get(['room_details.userId', 'room_details.playerId', 'users.fname', 'users.lname']);
        //   $playerData = RoomDetails::where('roomId', $this->roomId)->with('userDetail:fname,lname')->get(['userId', 'playerId']);
        $this->forwardSocket('roomJoined', ['playerId' => $newRoom->playerId, 'roomId' => $roomId, 'fname' => $request->user()->fname, 'lname' => $request->user()->lname], $request);
        $events = BoardEvent::where('roomId', $roomId)->get(['userId', 'tokenId', 'playerId', 'position', 'travelCount']);
        $currentTurn = RoomDetails::where('roomId', $roomId)->where('currentTurn', 1)->first('playerId')->playerId;
        return response()->json([
            'status' => true,
            'players' => $players,
            'playerId' => $newRoom->playerId,
            'events' => $events,
            'roomId' => $roomId,
            'currentTurn' => $newRoom->playerId,
            'endTime' =>  $endTime,
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
        $this->roomId = $checkUserJoined->roomId;
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
            //adjust after big dice value in passing area

            //need to add implementation 


            //to check if the user crossed 52 position then reset from 1
            if ($event->position > 52 && $event->position < 60) {
                $event->position = $event->position - 52;
            }


            //Log::info("before".$event->position);
            //entering to wining area and check they complete their travel or not
            if ($this->getPlayerId($request->tokenId) == 0 && $event->travelCount > 50 && $event->position < 220) {

                $event->position = 220 + $event->position - 12;
            } elseif ($this->getPlayerId($request->tokenId) == 1 && $event->travelCount > 50 && $event->position < 330) {
                $event->position = 330 + $event->position - 25;
            } elseif ($this->getPlayerId($request->tokenId) == 2 && $event->travelCount > 50 && $event->position < 440) {
                $event->position = 440 +  $event->position - 38;
            } elseif ($this->getPlayerId($request->tokenId) == 3 && $event->travelCount > 50 && $event->position < 110) {
                $event->position = 110 +  $event->position - 51;
            }
        } else {
            //to determine the initial position of the user
            $event->travelCount = $diceValue;
            $event->position = $this->getInitialPosition($request->tokenId) + $diceValue;
        }
        // Log::info("after" . $event->position);
        //to determine the user is safe or not
        $safePositions = [14, 1, 40, 27, 9, 22, 48, 35];
        $event->isSafe = in_array($event->position, $safePositions) ? '1' : '0';
        $event->save();
        //to determine the next turn
        $nextTurn = RoomDetails::where('roomId', $this->roomId)->count() == $event->playerId + 1 ? 0 : $event->playerId + 1;



        //to check if the token is returned to the home
        $CheckAnyTokenReturned = BoardEvent::where('position', $event->position)->where('roomId', $this->roomId)->whereNot('playerId', $event->playerId)->where('isSafe', '0')->first();


        //to check is this already a token on the same position
        if ($CheckAnyTokenReturned || $diceValue == 6) {
            $nextTurn =  $event->playerId;
        }
        //check if complete the travel and give another chance
        if ($event->travelCount >= 56) {
            $nextTurn =  $event->playerId;
            $event->isWin = '1';
            //$event->position += 1;
            $event->save();
        }
        //   if ($CheckAnyTokenReturned != true && $diceValue != 6) {
        $changeNext = RoomDetails::where('roomId', $this->roomId)->where('playerId', operator: $nextTurn)->update(['currentTurn' => 1]);
        //   }

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
            $CheckAnyTokenReturned->isSafe = in_array($CheckAnyTokenReturned->position, $safePositions) ? '1' : '0';

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
        if ($CheckAnyTokenReturned != true || $getLastDice->diceValue  != 6) {
            $getLastDice->update(['currentTurn' => 0]);
            //remove dice chance 
            //give dice chance to next player
            DiceRolling::where('roomId', $this->roomId)->where('playerId', $nextTurn)->first()->update(['currentTurn' => 1]);
        }
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
        $checkUserJoined = RoomDetails::where('userId', $request->user()->id)->where('roomType', 'tournament')->first();
        $this->roomId = $checkUserJoined->roomId;
        $diceModel = DiceRolling::where('roomId', $this->roomId)->where('userId', $request->user()->id)->first();
        if ($diceModel->currentTurn == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Not Your Turn(dice)',
            ]);
        }
        $diceModel->update(['diceValue' => $diceValue]);

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
            'D1', 'D2', 'D3', 'D4' => 1,
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
            3 => 1,
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
