<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    //
    use SoftDeletes;
    protected $fillable = [
        'reservation_id',
        'payment_method',
        'amount',
        'payment_status',
        'proof'
     ];
 
    public function reservation(): BelongsTo
     {
         return $this->belongsTo(Reservation::class, 'reservation_id');
     }
}
