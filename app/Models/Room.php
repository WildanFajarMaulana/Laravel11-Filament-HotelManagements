<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Room extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
       'room_name',
       'room_slug',
       'room_type',
       'price_per_night',
       'capacity',
       'description',
       'image_url',
       'status'
    ];

   public function setNameAttribute($value)
   {
       $this->attributes['room_name'] = $value;
       $this->attributes['room_slug'] = Str::slug($value);
   }

   public function roomFacilitys(): HasMany
   {
       return $this->hasMany(RoomFacility::class);
   }

   public function roomAvaibilitys(): HasMany
   {
       return $this->hasMany(RoomAvaibility::class);
   }

   public function reviews(): HasMany
   {
       return $this->hasMany(Review::class);
   }

   public function Reservations(): HasMany
   {
       return $this->hasMany(Reservation::class);
   }
}
