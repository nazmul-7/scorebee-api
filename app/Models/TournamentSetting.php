<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'tournament_id',

        'total_groups',
        'min_teams',
        'max_teams',
        'group_winners',
        'third_position',

        'second_round_type',
        'third_round_type',
        'fourth_round_type',

        'first_round_face_off',
        'second_round_face_off',
        'third_round_face_off',
        'fourth_round_face_off',

        'start_date',
        'end_date',
        'match_length'
    ];
}
