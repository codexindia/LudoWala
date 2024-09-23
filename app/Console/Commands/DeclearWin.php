<?php

namespace App\Console\Commands;

use ElephantIO\Client;
use Illuminate\Console\Command;
use App\Models\BoardEvent;
use Illuminate\Support\Facades\DB;
use App\Models\TournamentParticipant;
class DeclearWin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:declear-win';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rooms = DB::table('board_events')
        ->select('roomId')
        ->distinct()
        ->get();
        foreach ($rooms as $room) {
            // Fetch the player with the maximum steps for the current room
            $winner = DB::table('board_events')
            ->join('users', 'board_events.userId', '=', 'users.id')
            ->select('board_events.userId','users.fname',  DB::raw('SUM(board_events.travelCount) as totalSteps'))
            ->where('board_events.roomId', $room->roomId)
            ->groupBy('board_events.userId','users.fname','board_events.playerId')
            ->orderByDesc('totalSteps')
            ->first();

     

            if ($winner) {
                // Update the player's status to indicate they have won
                // DB::table('board_events')
                //     ->where('roomId', $room->roomId)
                //     ->where('userId', $winner->userId)
                //     ->update(['isWin' => '1']);

                    //temp
               //     TournamentParticipant::where('tournamentId',2)->where('userId','=',$winner->userId)->update(['winCount' => 1,'roundsPlayed' => 1]);
                   // TournamentParticipant::where('tournamentId',2)->where(['winCount' => 0,'roundsPlayed' => 0])->delete();
                 
                //endtemp
                    $this->info('Player '.$winner->fname.' with userId ' . $winner->userId . ' has been declared the winner for room ' . $room->roomId . ' with ' . $winner->totalSteps . ' steps.');
          $this->sendSocketEvent($room->roomId, $winner);
            } else {
                $this->info('No players found for room ' . $room->roomId . '.');
            }
            
        }
    
    }
    private function sendSocketEvent($roomId, $data)
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
            'winnerBoard' => $data,
        ]);
        $client->disconnect();
    }
}
