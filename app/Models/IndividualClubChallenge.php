<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualClubChallenge extends Model
{
    use HasFactory;

    protected $fillable = ['challenger_id', 'opponent_id', 'fixture_id', 'status'];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id', 'id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id', 'id');
    }
}
