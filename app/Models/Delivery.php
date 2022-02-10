<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'inning_id',
        'over_id',
        'bowler_id',
        'batter_id',
        'tournament_id',
        'fixture_id',
        'non_striker_id',
        'extras',
        'runs',
        'ball_type',
        'match_type',
        'run_type',
        'shot_x',
        'shot_y',
        'shot_position',
        'deep_position',
        'boundary_type',
        'wicket_by',
        'wicket_type',
        'assist_by',
        'caught_by',
        'stumped_by',
        'run_out_by',
        'drop_catch_by',
        'run_saved',
        'run_saved_by',
        'run_missed',
        'run_missed_by',
        'run_out_batter',
        'is_retired',
        'is_absent',
        'is_time_out',
        'is_obstructing_field',
        'over_number',
        'is_powerplay',
        'power_play_type',
        'ball_number',
        'ball_count',
    ];

    public function tournament(){
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }
    public function batter(){
        return $this->belongsTo(User::class, 'batter_id');
    }
    public function bowler(){
        return $this->belongsTo(User::class, 'bowler_id');
    }
    public function non_striker(){
        return $this->belongsTo(User::class, 'non_striker_id');
    }
    public function stumpBy(){
        return $this->belongsTo(User::class, 'stumped_by');
    }
    public function runOutBy(){
        return $this->belongsTo(User::class, 'run_out_by');
    }
    public function wicketBy(){
        return $this->belongsTo(User::class, 'wicket_by');
    }
    // public function stumpBy(){
    //     return $this->belongsTo(User::class, 'assist_by');
    // }
    public function caughtBy(){
        return $this->belongsTo(User::class, 'caught_by');
    }
    public function assistBy(){
        return $this->belongsTo(User::class, 'assist_by');
    }
    public function oversDeliveries(){
        return $this->hasMany(Delivery::class, 'over_number', 'over_number');
    }

    // stumpBy caughtBy assistBy runOutBy
    public function ballOutPosition(){
        return $this->hasMany(Delivery::class, 'shot_position', 'shot_position');
    }
    public function batters(){
        return $this->hasMany(InningBatterResult::class, 'batter_id', 'batter_id');
    }
    public function singleBatter(){
        return $this->belongsTo(InningBatterResult::class, 'batter_id', 'batter_id');
    }
    public function fixture(){
        return $this->belongsTo(Fixture::class);
    }
    // stumpBy runOutBy caught_by assist_by retired_type
}
