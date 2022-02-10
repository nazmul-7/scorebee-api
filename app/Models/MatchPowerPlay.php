<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchPowerPlay extends Model
{
    use HasFactory;
    protected $fillable =[
        'fixture_id',
        'inning_id',
        'type',
        'start',
        'end',
    ];

    public function powerplayOvers(){
        return $this->hasMany(Inning::class);
    }
    
}
