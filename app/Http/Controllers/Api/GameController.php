<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

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
    public function eventStore(Request $request)
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
        $client->initialize();

        // Set the bearer token for authentication


        // Emit an event to the server
        $client->emit('sendMessage', [
            'test' => $request->test,
        ]);

        // Close the connection
        $client->close();

        return true;
    }
    public function rollDice(Request $request)
    {
        $request->validate([
           // 'roomId' => 'required',
            'playerId' => 'required',
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
        $client->initialize();

        // Set the bearer token for authentication


        // Emit an event to the server
        $client->emit('sendMessage', [
            $event => $data,
        ]);

        // Close the connection
        $client->close();

        return true;
    }
}
