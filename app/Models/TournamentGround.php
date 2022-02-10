<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentGround extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'ground_id',
        'tour_owner_id',
    ];

    public function tournaments(){
        return $this->belongsTo(Tournament::class, 'tournament_id', 'id')->select('id','tournament_name', 'tournament_logo', 'city');
    }
}
