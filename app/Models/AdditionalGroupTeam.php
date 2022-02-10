<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalGroupTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'additional_group_id',
        'team_id',
        'match_plays',
        'match_wins',
        'match_losses',
        'match_ties',
        'net_run',
        'group_points',
        'net_run_rate',
    ];
}
