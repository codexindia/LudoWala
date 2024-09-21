<?php

namespace App\Console\Commands;

use App\Models\RoomDetails;
use ElephantIO\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DiceRolling;

class changeTurn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
   
    protected $signature = 'turntimeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $socketAdminKey;
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = 30; // 30 seconds
        $now = Carbon::now();

        // Fetch games where the last move was made more than 30 seconds ago
        $timeoutGames = DB::table('dice_rollings')
            ->where('currentTurn', 1)
            ->where('updated_at', '<', $now->copy()->subSeconds($timeout))
            ->get();
        //die($now->subSeconds($timeout));
        foreach ($timeoutGames as $game) {
            // Determine the next player's turn
            //  $nextTurn = ($game->currentTurn + 1) % 4; // Assuming 4 players in the room
            $nextTurn = DiceRolling::where('roomId', $game->roomId)->count() == $game->playerId + 1 ? 0 : $game->playerId + 1;
            // Update dice the currentTurn to the next turn
            DB::table('dice_rollings')
                ->where('roomId', $game->roomId)
                ->where('playerId', $game->playerId)
                ->update(['currentTurn' => 0, 'updated_at' => $now]);
            DB::table('dice_rollings')
                ->where('playerId', $nextTurn)
                ->update(['currentTurn' => 1, 'updated_at' => $now]);
            //change the current turn to 0 for the player who's turn has timed out
            RoomDetails::where('roomId', $game->roomId)->where('playerId', $game->playerId)->update(['currentTurn' => 0, 'updated_at' => $now]);
            RoomDetails::where('roomId', $game->roomId)->where('playerId', $nextTurn)->update(['currentTurn' => 1, 'updated_at' => $now]);

            echo $this->sendSocketEvent($game->roomId, $nextTurn)."\n";;

            echo "Turn changed for game ID {$game->id} to player {$nextTurn}\n";
        }
    }
    private function sendSocketEvent($roomId, $nextTurn)
    {
       // $signature = hash_hmac('sha256', "asdasdasdad", env('SOCKET_ADMIN_KEY'));
  //    return $signature;
        $options = [
            'auth' =>  [
               'token' => env('SOCKET_ADMIN_KEY'),
               'roomId' => $roomId,
            ],
        ];
        $client = Client::create('wss://socket.ludowalagames.com:3000', $options);

        $client->connect();
        $client->emit('sendMessage', [
            'nextTurn' => $nextTurn,
        ]);
        $client->disconnect();
    }
}
