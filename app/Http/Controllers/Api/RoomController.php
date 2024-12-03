<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $room = Room::with(['roomFacilitys', 'roomAvaibilitys', 'reviews', 'reservations'])
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

    public function createRating(Request $request)
    {
        // Validasi data yang diterima
        $validatedData = $request->validate([
            'room_id' => 'required|exists:rooms,id',  // Pastikan room_id ada di tabel rooms
            'rating' => 'required|integer|between:1,5', // Rating harus antara 1 dan 5
            'review_text' => 'nullable|string|max:1000', // Ulasan optional
        ]);

        // Ambil data room
        $room = Room::find($validatedData['room_id']);
        
        // Pastikan user yang login sudah melakukan reservasi di room tersebut
        if ($room->reservations()->where('user_id', Auth::id())->where('reservation_status', 'completed')->doesntExist()) {
            return response()->json(['message' => 'You must complete your reservation to give a rating.'], 400);
        }

        // Membuat ulasan dan rating baru
        $review = Review::create([
            'user_id' => Auth::id(),
            'room_id' => $validatedData['room_id'],
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
