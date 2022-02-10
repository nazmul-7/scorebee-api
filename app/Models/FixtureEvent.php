<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixtureEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'fixture_id',
        'event_name',
        'event_duration',
        'comment',
        'type',
    ];
}
