<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WicketFall extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'batter_id',
        'team_id',
        'league_group_id',
        'league_group_team_id',
        'fixture_id',
        'inning_id',
        'in_which_over',
        'score_when_fall',

    ];

    public function batter(){
        return $this->belongsTo(User::class, 'batter_id', 'id');
    }
}
