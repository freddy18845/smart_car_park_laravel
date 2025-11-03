<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\SubSpace;
use App\Models\ParkingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationController extends Controller
{
    // ✅ Create a reservation
 public function store(Request $request)
{
    // Ensure the user is authenticated
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
    }

    $validated = $request->validate([
        'parking_space_id' => 'required|exists:parking_spaces,id',
        'parking_spot_id'  => 'required|exists:parking_spots,id',
        'sub_space_id'     => 'nullable|exists:sub_spaces,id',
        'start_time'       => 'required|date|after:now',
        'end_time'         => 'required|date|after:start_time',
        'vehicle_number'   => 'nullable|string|max:50',
        'type'             => 'required|in:walk-in,booking',
    ]);

    try {
        $reservation = DB::transaction(function () use ($validated, $userId) {
            // Lock and validate availability
            if (!empty($validated['sub_space_id'])) {
                $subSpace = SubSpace::lockForUpdate()->find($validated['sub_space_id']);

                if (!$subSpace) {
                    throw ValidationException::withMessages([
                        'sub_space_id' => 'The selected sub-space does not exist.',
                    ]);
                }

                if ($subSpace->status !== 'available') {
                    throw ValidationException::withMessages([
                        'sub_space_id' => "Sub-space ID {$validated['sub_space_id']} is currently {$subSpace->status}.",
                    ]);
                }

                // Prevent overlap
                $overlap = Reservation::where('sub_space_id', $validated['sub_space_id'])
                    ->whereIn('status', ['reserved', 'occupied', 'booked'])
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time'])
                    ->exists();

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'sub_space_id' => "Sub-space ID {$validated['sub_space_id']} is already reserved during that time.",
                    ]);
                }
            } else {
                $spot = ParkingSpot::lockForUpdate()->find($validated['parking_spot_id']);

                if (!$spot) {
                    throw ValidationException::withMessages([
                        'parking_spot_id' => 'The selected parking spot does not exist.',
                    ]);
                }

                if ($spot->status !== 'available') {
                    throw ValidationException::withMessages([
                        'parking_spot_id' => "Parking spot is currently {$spot->status}.",
                    ]);
                }
            }

            // ✅ Auto-set reservation status
            $status = $validated['type'] === 'booking' ? 'booked' : 'reserved';

            // ✅ Create reservation
            $reservation = Reservation::create([
                'user_id'          => $userId,
                'parking_space_id' => $validated['parking_space_id'],
                'parking_spot_id'  => $validated['parking_spot_id'],
                'sub_space_id'     => $validated['sub_space_id'] ?? null,
                'start_time'       => $validated['start_time'],
                'end_time'         => $validated['end_time'],
                'vehicle_number'   => $validated['vehicle_number'] ?? null,
                'status'           => $status,
                'type'             => $validated['type'],
            ]);

            // ✅ Update sub-space AFTER reservation is confirmed
           
if (!empty($validated['sub_space_id'])) {
    // Re-fetch to ensure we have the correct instance
    $subSpace = SubSpace::find($validated['sub_space_id']);
    
    if ($subSpace) {
        $subSpace->update([
            'status' => $validated['type'] === 'booking' ? 'booked' : 'occupied',
            'current_user_id' => $userId,
        ]);
    }
}

            return $reservation;
        });

        // ✅ Return response with loaded relationships
        return response()->json([
            'message' => 'Reservation created successfully',
            'data' => $reservation->load([
                'user:id,first_name,last_name,phone',
                'parkingSpace',
                'parkingSpot',
                'subSpace'
            ]),
        ], 201);

    } catch (ValidationException $e) {
        throw $e; // Let Laravel handle validation errors
    } catch (\Throwable $e) {
        \Log::error('❌ Reservation Store Failed: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error' => 'An unexpected error occurred while creating the reservation.',
            'details' => $e->getMessage(),
        ], 500);
    }
}


    // ✅ Update reservation
   public function update(Request $request, $id)
{
    $validated = $request->validate([
        'end_time' => 'required|date|after:start_time',
        'status'   => 'required|in:booked,reserved,occupied,completed,cancelled',
    ]);

    return DB::transaction(function () use ($validated, $id) {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', Auth::id())
            ->lockForUpdate()
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $allowedTransitions = [
            'booked'   => ['occupied', 'completed', 'cancelled'],
            'reserved' => ['booked', 'occupied', 'completed', 'cancelled'],
            'occupied' => ['completed', 'cancelled'],
        ];

        if (!in_array($validated['status'], $allowedTransitions[$reservation->status] ?? [])) {
            throw ValidationException::withMessages([
                'status' => "Cannot change status from {$reservation->status} to {$validated['status']}."
            ]);
        }

        $reservation->update([
            'end_time' => $validated['end_time'],
            'status'   => $validated['status'],
        ]);

        $status = $this->getSpaceStatusFromReservationStatus($validated['status']);
        if ($reservation->sub_space_id) {
            SubSpace::where('id', $reservation->sub_space_id)->update(['status' => $status]);
        } else {
            ParkingSpot::where('id', $reservation->parking_spot_id)->update(['status' => $status]);
        }

        return response()->json([
            'message' => 'Reservation updated successfully',
            'data' => $reservation->fresh(['user:id,first_name,last_name,phone', 'parkingSpace', 'parkingSpot', 'subSpace']),
        ]);
    });
}


    // ✅ Get space status from reservation status
   private function getSpaceStatusFromReservationStatus($status)
{
    return match ($status) {
        'booked'     => 'booked',      // <— new case added
        'reserved'   => 'occupied',
        'occupied'   => 'occupied',
        'completed'  => 'available',
        'cancelled'  => 'available',
        default      => 'available',
    };
}


    // ✅ Update reservation status only
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:reserved,occupied,completed,cancelled',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found'], 404);
            }

            $oldStatus = $reservation->status;
            $newStatus = $validated['status'];

            $allowedTransitions = [
                'reserved' => ['occupied', 'completed', 'cancelled'],
                'occupied' => ['completed', 'cancelled'],
            ];

            if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change status from {$oldStatus} to {$newStatus}."
                ]);
            }

            $reservation->update(['status' => $newStatus]);

            if (in_array($newStatus, ['completed', 'cancelled'])) {
                if ($reservation->sub_space_id) {
                    SubSpace::where('id', $reservation->sub_space_id)->update(['status' => 'available']);
                } else {
                    ParkingSpot::where('id', $reservation->parking_spot_id)->update(['status' => 'available']);
                }
            }

            return response()->json([
                'message' => "Reservation status updated to {$newStatus} successfully",
                'data' => $reservation->fresh(['parkingSpace', 'parkingSpot', 'subSpace']),
            ]);
        });
    }

    // ✅ Extend reservation time
    public function extendTime(Request $request, $id)
    {
        $validated = $request->validate([
            'additional_hours' => 'required|integer|min:1|max:24',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found'], 404);
            }

            if (!in_array($reservation->status, ['reserved', 'occupied'])) {
                throw ValidationException::withMessages([
                    'reservation' => 'Only active reservations can be extended.'
                ]);
            }

            $newEndTime = Carbon::parse($reservation->end_time)->addHours($validated['additional_hours']);

            if ($reservation->sub_space_id) {
                $overlap = Reservation::where('sub_space_id', $reservation->sub_space_id)
                    ->where('id', '!=', $reservation->id)
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->where('start_time', '<', $newEndTime)
                    ->where('end_time', '>', $reservation->end_time)
                    ->exists();

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'additional_hours' => "Cannot extend reservation. Sub-space ID {$reservation->sub_space_id} has conflicts."
                    ]);
                }
            }

            $reservation->update(['end_time' => $newEndTime]);

            return response()->json([
                'message' => "Reservation extended by {$validated['additional_hours']} hours successfully",
                'data' => $reservation->fresh(['parkingSpace', 'parkingSpot', 'subSpace']),
                'new_end_time' => $newEndTime->toDateTimeString(),
            ]);
        });
    }

    // ✅ List reservations
    public function index()
    {
        $now = Carbon::now();

        return DB::transaction(function () use ($now) {
            $expired = Reservation::where('user_id', Auth::id())
                ->where('end_time', '<', $now)
                ->where('status', '!=', 'completed')
                ->lockForUpdate()
                ->get();

            foreach ($expired as $reservation) {
                $reservation->update(['status' => 'completed']);
                if ($reservation->sub_space_id) {
                    SubSpace::where('id', $reservation->sub_space_id)->update(['status' => 'available']);
                } else {
                    ParkingSpot::where('id', $reservation->parking_spot_id)->update(['status' => 'available']);
                }
            }

            $reservations = Reservation::with([
                'user:id,first_name,last_name,phone',
                'parkingSpace',
                'parkingSpot',
                'subSpace'
            ])
                ->where('user_id', Auth::id())
                ->orderBy('start_time', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reservations,
                'expired_updated' => $expired->count(),
            ]);
        });
    }

    public function filter(Request $request)
{
    $query = Reservation::with(['user:id,first_name,last_name,phone', 'parkingSpace', 'parkingSpot', 'subSpace']);

    if ($request->has('status') && $request->status != '') {
        $query->where('status', $request->status);
    }

    if ($request->has('user_id') && $request->user_id != '') {
        $query->where('user_id', $request->user_id);
    }

    if ($request->has('type') && $request->type != '') {
        $query->where('type', $request->type);
    }

    $reservations = $query->latest()->get();

    return response()->json([
        'message' => 'Filtered reservations retrieved successfully',
        'data' => $reservations,
    ]);
}
public function updateActiveReservationLocation(Request $request)
{
    $validated = $request->validate([
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    $reservation = Reservation::where('user_id', Auth::id())
        ->where('status', 'reserved')
        ->where('end_time', '>', now())
        ->latest('start_time')
        ->first();

    if (!$reservation) {
        return response()->json(['message' => 'No active reservation found'], 404);
    }

    // Update the location
    $reservation->update([
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
    ]);

    // Check if reservation is almost due (e.g., less than 10 minutes left)
    $minutesLeft = now()->diffInMinutes($reservation->end_time, false);

    if ($minutesLeft <= 10) {
        // Get parking space coordinates
        $parkingLat = $reservation->parkingSpace->latitude;
        $parkingLng = $reservation->parkingSpace->longitude;

        // Calculate distance in km
        $distance = $this->calculateDistance(
            $validated['latitude'], 
            $validated['longitude'], 
            $parkingLat, 
            $parkingLng
        );

        // If user is too far (e.g., more than 0.5 km), send SMS
        if ($distance > 0.5) {
            try {
                // Replace with your SMS sending service
                SMSOnlineGHService::sendSMS(
                    [$reservation->user->phone], 
                    "Your reservation at {$reservation->parkingSpace->name} is about to expire. Please head to the parking space."
                );
            } catch (\Exception $e) {
                \Log::error("Failed to send reservation SMS: " . $e->getMessage());
            }
        }
    }

    return response()->json([
        'message' => 'Location updated successfully',
        'reservation_id' => $reservation->id,
        'minutes_left' => $minutesLeft ?? null,
    ]);
}

/**
 * Calculate distance between two coordinates using Haversine formula
 */
private function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // distance in km
}


}
