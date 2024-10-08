<?php

namespace App\Console\Commands;

use ElephantIO\Client;
use Illuminate\Console\Command;
use App\Models\BoardEvent;
use App\Models\DiceRolling;
use App\Models\RoomDetails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use App\Models\TournamentParticipant;
use App\Models\Tournaments;
use Carbon\Carbon;

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

        $tournamentId = 9;
        $tournament = Tournaments::where('id', $tournamentId)->first();
        $tournament->nextRoundTime = Carbon::now()->addMinutes(2)->toDateTimeString();
        $tournament->currentRound += 1;
        $tournament->save();
        foreach ($rooms as $room) {
            // Fetch the player with the maximum steps for the current room
            $winner = DB::table('board_events')
                ->join('users', 'board_events.userId', '=', 'users.id')
                ->select('board_events.userId', 'board_events.playerId', 'users.fname',  DB::raw('SUM(board_events.travelCount) as totalSteps'))
                ->where('board_events.roomId', $room->roomId)
                ->groupBy('board_events.userId', 'users.fname', 'board_events.playerId')
                ->orderByDesc('totalSteps')
                ->first();

            $eliminatedPlayers = BoardEvent::select(
                'board_events.userId',
                'board_events.playerId',
                'users.fname',
                DB::raw('SUM(board_events.travelCount) AS totalSteps')
            )
                ->leftJoin('users', 'users.id', '=', 'board_events.userId')
                ->where('board_events.userId', '!=', $winner->userId)
                ->groupBy('board_events.userId', 'board_events.playerId')
                ->get();
            //  if($tournament->currentRound == 3)
            //  {
            //     creditBal($winner->userId, $tournament['2ndRoundWinning'], 0, "winning_wallet", "Tournament 2nd Round Winning");
            //     foreach($eliminatedPlayers->userId as $eliminatedIds)
            //     {
            //         creditBal($eliminatedIds->userId, $tournament['2ndRoundWinning'], 0, "winning_wallet", "Tournament 2nd Round Winning");
            
            //     }
            // }

            if ($winner) {

                //temp need to edit tid
                $changeStatus = TournamentParticipant::where('tournamentId', $tournamentId)->where('userId', '=', $winner->userId)->first();
                $changeStatus->winCount += 1;
                $changeStatus->roundsPlayed += 1;
                $changeStatus->save();

                //change next tournament round time 

                $deleteBoardEvent = BoardEvent::where('roomId', $room->roomId)->delete();
                $deleteRoomDetails = RoomDetails::where('roomId', $room->roomId)->delete();
                $deleteDice = DiceRolling::where('roomId', $room->roomId)->delete();

                // TournamentParticipant::where('tournamentId',2)->where(['winCount' => 0,'roundsPlayed' => 0])->delete();

                //endtemp
                $this->info('Player ' . $winner->fname . ' with userId ' . $winner->userId . ' has been declared the winner for room ' . $room->roomId . ' with ' . $winner->totalSteps . ' steps.');

                // echo $winner;
                $winner->eliminatedPlayers = $eliminatedPlayers;
                //   $this->info($winner);
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
