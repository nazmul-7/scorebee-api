<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixtureMedia extends Model
{
    use HasFactory;
    protected $fillable = [
        'fixture_id',
        'type',
        'extension_type',
        'url'
    ];
}
