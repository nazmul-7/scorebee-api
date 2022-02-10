<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'from',
        'to',
        'title',
        'type',
        'msg',
        'club_id',
        'tournament_id',
        'fixture_id',
        'team_id'
    ];
}
