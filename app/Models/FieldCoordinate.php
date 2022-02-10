<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldCoordinate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'x_coordinate',
        'y_coordinate',
        'batsman_type'
    ];
}
