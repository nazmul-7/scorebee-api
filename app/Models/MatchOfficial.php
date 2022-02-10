<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchOfficial extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'official_type',
        'position',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select('id', 'first_name','last_name');
    }
}
