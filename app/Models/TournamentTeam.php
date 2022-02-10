<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'tournament_id',
        'status',
        'requested_by',
        'tournament_owner_id',
    ];

    public function team(){
        return $this->belongsTo(Team::class, 'team_id', 'id')->select('id','team_name', 'captain_id', 'team_unique_name', 'team_logo', 'owner_id', 'city');
    }
    public function tournament_team(){
        return $this->belongsTo(Team::class, 'team_id', 'id')->select('id','team_name','owner_id');
    }



}
