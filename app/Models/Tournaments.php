<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournaments extends Model
{
    use HasFactory;
    public function participants()
    {
        return $this->hasMany(TournamentParticipant::class,'tournamentId','id');
    }
}
