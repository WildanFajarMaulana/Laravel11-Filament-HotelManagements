<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomAvaibility extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
       'room_id',
       'date_available',
       'is_available'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
