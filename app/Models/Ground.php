<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ground extends Model
{
    use HasFactory;

    protected $fillable = [
        'ground_name',
        'country',
        'state',
        'city',
        'capacity',
        'user_id',
    ];

    protected $hidden = ['pivot'];

    public function tournament_grounds(){
        return $this->hasMany(TournamentGround::class, 'ground_id', 'id');
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_grounds');
    }
}
