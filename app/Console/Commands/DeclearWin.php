<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BoardEvent;
use Illuminate\Support\Facades\DB;
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
                ->select('userId', DB::raw('SUM(travelCount) as totalSteps'))
                ->where('roomId', $room->roomId)
                ->groupBy('userId')
                ->orderByDesc('totalSteps')
                ->first();

            if ($winner) {
                // Update the player's status to indicate they have won
                DB::table('board_events')
                    ->where('roomId', $room->roomId)
                    ->where('userId', $winner->userId)
                    ->update(['isWin' => '1']);

                $this->info('Player with userId ' . $winner->userId . ' has been declared the winner for room ' . $room->roomId . ' with ' . $winner->totalSteps . ' steps.');
            } else {
                $this->info('No players found for room ' . $room->roomId . '.');
            }
        }
    
    }
}
