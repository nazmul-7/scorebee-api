<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panalty extends Model
{
    use HasFactory;
    protected $fillable = [
        'inning_id',
        'type',
        'runs',
        'team_id',
        'reason',
        'league_group_id',
        'tournament_id',
        'league_group_team_id',
        'fixture_id'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
