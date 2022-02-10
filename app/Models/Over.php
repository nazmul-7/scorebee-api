<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Over extends Model
{
    use HasFactory;

    protected $fillable = [
        'bowler_id',
        'inning_id',
        'over_number'
    ];

    public function oversDelivery(): HasMany
    {
        return $this->hasMany(Delivery::class, 'over_id', 'id');
    }

    public function bowler()
    {
        return $this->belongsTo(User::class, 'bowler_id', 'id');
    }
}
