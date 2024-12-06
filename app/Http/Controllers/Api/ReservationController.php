<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function createReservation(Request $request)
    {
        // Validasi input
        $validatedData = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'payment_method' => 'required|string|in:credit_card,bank_transfer,cash',
            'payment_status' => 'required|boolean',
            'proof' => 'nullable|file|mimes:jpg,png,pdf|max:2048', // File upload proof
        ]);

        // Mulai transaksi database
        DB::beginTransaction();

        try {
            // Mengecek ketersediaan kamar
            $room = Room::find($validatedData['room_id']);
            if ($room->status !== 'available') {
                return response()->json([
                    'message' => 'The selected room is not available for reservation.',
                ], 400);
            }

            // Menghitung total harga
            $checkInDate = strtotime($validatedData['check_in_date']);
            $checkOutDate = strtotime($validatedData['check_out_date']);
            $nights = ($checkOutDate - $checkInDate) / (60 * 60 * 24);
            $totalPrice = $nights * $room->price_per_night;

            // Membuat Reservation
            $reservation = Reservation::create([
                'user_id' => Auth::id(),
                'room_id' => $validatedData['room_id'],
                'check_in_date' => $validatedData['check_in_date'],
                'check_out_date' => $validatedData['check_out_date'],
                'total_price' => $totalPrice,
                'reservation_code' => Reservation::generateUniqueTrxId(),
                'reservation_status' => 'pending',
            ]);

            // Menyimpan file proof pembayaran jika ada
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('payments/proofs', 'public');
            }

            // Membuat Payment terkait dengan Reservation
            Payment::create([
                'reservation_id' => $reservation->id,
                'payment_method' => $validatedData['payment_method'],
                'amount' => $totalPrice,
                'payment_status' => $validatedData['payment_status'],
                'proof' => $proofPath,
            ]);

            // Commit transaksi
            DB::commit();

            // Mengembalikan response
            return response()->json([
                'message' => 'Reservation created successfully.',
                'reservation' => $reservation,
                'payment' => $reservation->payment,
            ]);

        } catch (\Exception $e) {
            // Jika terjadi error, rollback transaksi
            DB::rollBack();

            // Menangani error dan memberikan response yang sesuai
            return response()->json([
                'message' => 'Failed to create reservation. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function historyReservationByUser(Request $request)
    {
        $userId = $request->user()->id;
        // Validasi apakah user_id valid
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Ambil riwayat reservasi berdasarkan user_id
        $reservations = Reservation::with('room') // Memuat relasi room
                                    ->where('user_id', $userId)
                                    ->orderBy('created_at', 'desc') // Urutkan berdasarkan tanggal pembuatan
                                    ->get();

        // Cek apakah ada data reservasi
        if ($reservations->isEmpty()) {
            return response()->json(['message' => 'No reservation found for this user.'], 404);
        }

        // Mengembalikan data reservasi dengan room terkait
        return response()->json([
            'message' => 'Reservation history fetched successfully.',
            'reservations' => $reservations,
        ]);
    }

    public function cancelReservation(Request $request, $reservation_id)
    {
        // Validasi bahwa user yang meminta adalah pemilik reservasi
        $reservation = Reservation::find($reservation_id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found.',
            ], 404);
        }

        // Cek apakah pengguna yang login adalah pemilik reservasi
        if ($reservation->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not authorized to cancel this reservation.',
            ], 403);
        }

        // Cek apakah check-in belum dilakukan
        $checkInDate = strtotime($reservation->check_in_date);
        if ($checkInDate <= time()) {
            return response()->json([
                'message' => 'Cannot cancel the reservation. Check-in date has passed.',
            ], 400);
        }

        // Update status reservasi menjadi 'canceled'
        $reservation->reservation_status = 'canceled';
        $reservation->save();

        // Mengubah status kamar menjadi 'available'
        $room = $reservation->room;
        if ($room) {
            $room->status = 'available';
            $room->save();
        }

        return response()->json([
            'message' => 'Reservation has been canceled successfully.',
            'reservation' => $reservation,
        ]);
    }
}
