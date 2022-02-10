<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamPlayer extends Model
{
    protected $fillable = [
        'club_player_id',
        'team_id',
        'player_id',
        'squad_type',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
    public function tema_player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id')->select('id','app_token');
    }
}
