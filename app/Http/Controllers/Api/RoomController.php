<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * Get all rooms
     */
    public function index()
    {
        // Mengambil semua kamar beserta relasi
        $rooms = Room::with(['roomFacilitys', 'roomAvaibilitys', 'reviews'])
            ->whereNot('status', 'maintance') // Filter kamar yang statusnya 'available'
            ->get();

        return response()->json([
            'message' => 'Rooms retrieved successfully.',
            'data' => $rooms,
        ]);
    }

    /**
     * Get room detail by slug
     */
    public function roomDetail($slug)
    {
        // Ambil detail kamar berdasarkan slug
          $room = Room::with(['roomFacilitys', 'roomAvaibilitys', 'reviews.user' => function($query) {
        $query->withTrashed(); // Ambil juga yang sudah di-soft delete
    }])
        ->where('room_slug', $slug)
        ->first();

        if (!$room) {
            return response()->json([
                'message' => 'Room not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Room detail retrieved successfully.',
            'data' => $room,
        ]);
    }

    public function createRating(Request $request, $reservation_id)
    {
        // Validasi data yang diterima
        $validatedData = $request->validate([
            'rating' => 'required|integer|between:1,5', // Rating harus antara 1 dan 5
            'review_text' => 'nullable|string|max:1000', // Ulasan optional
        ]);

        // Ambil data reservation berdasarkan ID
        $reservation = Reservation::with('room')->find($reservation_id);

        // Validasi apakah reservation ditemukan
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        // Pastikan user yang login memiliki reservation ini dan statusnya 'completed'
        if ($reservation->user_id !== Auth::id() || $reservation->reservation_status !== 'completed') {
            return response()->json(['message' => 'You must complete your reservation to give a rating.'], 403);
        }

        // Ambil room_id dari data reservation
        $room_id = $reservation->room_id;

        // Membuat ulasan dan rating baru
        $review = Review::create([
            'reservation_id' => $reservation_id,
            'user_id' => Auth::id(),
            'room_id' => $room_id,
            'rating' => $validatedData['rating'],
            'review_text' => $validatedData['review_text'],
        ]);

        // Mengembalikan response berhasil
        return response()->json([
            'message' => 'Rating and review created successfully.',
            'review' => $review,
        ], 201);
    }

}
