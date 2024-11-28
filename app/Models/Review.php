<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
       'user_id',
       'room_id',
       'amount',
       'rating',
       'review_text'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'category_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'category_id');
    }
}