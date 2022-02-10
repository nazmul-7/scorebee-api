<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwardLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'user_id',
        'tournament_id',
        'fixture_id',
        'type',
    ];
}
