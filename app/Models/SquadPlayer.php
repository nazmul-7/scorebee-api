<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SquadPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_squad_id',
        'player_id',
        'playing_role',
        'is_captain',
        'is_wicket_keeper'
    ];
}
