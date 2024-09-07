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
            'token' => 'Bearer '.$request->bearerToken(),
            ]
        ];
        // Create a new Socket.IO client
        $client = Client::create('http://socket.ludowalagames.com:3000/', $options);
      //  $client = new Client(new Version2X());

        // Connect to the Socket.IO server
        $client->initialize();

        // Set the bearer token for authentication
      

        // Emit an event to the server
        $client->emit('sendMessage', $request->test);

        // Close the connection
        $client->close();

        return true;
    }
}
