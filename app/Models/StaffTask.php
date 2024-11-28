<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffTask extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
       'user_id',
       'reservation_id',
       'task_type',
       'assigned_at',
       'completed_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'category_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'category_id');
    }
}
