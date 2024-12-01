<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($staffTask) {
            // Validate unique task_type for a reservation_id
            self::validateUniqueTaskType($staffTask);

            // Ensure assigned_at is set based on task_type
            self::setAssignedAt($staffTask);
        });

        static::updating(function ($staffTask) {
            // Validate unique task_type for a reservation_id
            self::validateUniqueTaskType($staffTask);

            // Ensure assigned_at is set based on task_type
            self::setAssignedAt($staffTask);
        });
    }

    /**
     * Validate that the same task_type doesn't already exist for the given reservation_id.
     */
    protected static function validateUniqueTaskType($staffTask)
    {
        $exists = self::where('reservation_id', $staffTask->reservation_id)
            ->where('task_type', $staffTask->task_type)
            ->when($staffTask->exists, function ($query) use ($staffTask) {
                $query->where('id', '!=', $staffTask->id); // Exclude the current record during update
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'task_type' => "The {$staffTask->task_type} task is already assigned for this reservation.",
            ]);
        }
    }

    /**
     * Set the assigned_at field based on the task_type and related reservation.
     */
    protected static function setAssignedAt($staffTask)
    {
        if ($staffTask->reservation_id) {
            $reservation = Reservation::find($staffTask->reservation_id);
            if ($reservation) {
                if ($staffTask->task_type == 'Checked-in') {
                    $staffTask->assigned_at = $reservation->check_in_date;
                } elseif ($staffTask->task_type == 'Checked-out') {
                    $staffTask->assigned_at = $reservation->check_out_date;
                }
            }
        }
    }
}
