<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalGroup extends Model
{
    use HasFactory;

    protected $fillable = ['additional_group_name', 'tournament_id'];
}
