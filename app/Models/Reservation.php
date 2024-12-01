<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
         return $this->belongsTo(User::class, 'user_id');
     }

    public function room(): BelongsTo
     {
         return $this->belongsTo(Room::class, 'room_id');
     }

     public function payment(): HasOne
     {
         return $this->hasOne(Payment::class);
     }

     public function staffTasks(): HasMany
     {
         return $this->hasMany(StaffTask::class);
     }

    protected static function booted()
    {
        static::creating(function ($reservation) {
            $room = $reservation->room;
            if ($room) {
                // Set harga sesuai dengan harga kamar yang dipilih
                $reservation->total_price = $room->price_per_night;
                
                // Set status kamar baru menjadi 'booked'
                $room->status = 'booked';
                $room->save();
            }            
        });

        static::updating(function ($reservation) {
            // Set total_price saat update
            $room = $reservation->room;
            $previousRoom = Room::find($reservation->getOriginal('room_id')); // Ambil room_id sebelumnya
    
            if ($room) {
                // Update total_price berdasarkan harga kamar yang baru dipilih
                $reservation->total_price = $room->price_per_night;
    
                // Jika kamar yang dipilih berbeda, update status kamar
                if ($previousRoom && $previousRoom->id !== $room->id) {
                    // Set status kamar sebelumnya menjadi 'available'
                    $previousRoom->status = 'available';
                    $previousRoom->save();
    
                    // Set status kamar baru menjadi 'booked'
                    $room->status = 'booked';
                    $room->save();
                }
            }
        });
    }
}
