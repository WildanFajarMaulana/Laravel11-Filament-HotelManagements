<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    //
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'reservation_status',
        'reservation_code'
     ];

     public static function generateUniqueTrxId()
    {
        $prefix = 'XILDER';

        do {
            $randomString = $prefix . mt_rand(1000,9999);
        }while (self::where('reservation_code', $randomString)->exists());

        return $randomString;
    }

 
    public function user(): BelongsTo
     {
         return $this->belongsTo(User::class, 'category_id');
     }

    public function room(): BelongsTo
     {
         return $this->belongsTo(Room::class, 'category_id');
     }

     public function moneyPayment(): HasOne
     {
         return $this->hasOne(MoneyPayment::class, 'category_id');
     }
}
