<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubSpace;
use App\Models\ParkingSpot;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubSpaceController extends Controller
{
    /**
     * Update the status of a specific sub-space
     * 
     * @param Request $request
     * @param int $id (sub_space_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'parking_spot_id' => 'required|exists:parking_spots,id',
        'parking_space_id' => 'required|exists:parking_spaces,id',
        'operator_id' => 'required|exists:users,id',
        'status' => 'nullable|in:available,occupied,reserved,maintenance,booked',
        'type' => 'nullable|in:booking,reservation',
        'user_id' => 'nullable|exists:users,id',
    ]);

    return DB::transaction(function () use ($validated, $id) {
        $parkingSpace = ParkingSpace::where('id', $validated['parking_space_id'])
            ->where('operator_id', $validated['operator_id'])
            ->first();

        if (!$parkingSpace) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Operator does not own this parking space'
            ], 403);
        }

        $subSpace = SubSpace::where('id', $id)
            ->where('parking_spot_id', $validated['parking_spot_id'])
            ->lockForUpdate()
            ->first();

        if (!$subSpace) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-space not found'
            ], 404);
        }

        // Determine new status based on type or input
        $newStatus = $validated['status'] ?? $subSpace->status;

        if (isset($validated['type']) && $validated['type'] === 'booking') {
            $newStatus = 'booked';
        }

        $subSpace->update([
            'status' => $newStatus,
            'current_user_id' => $validated['user_id'] ?? $subSpace->current_user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sub-space status updated to {$newStatus} successfully",
            'data' => $subSpace->fresh(),
        ], 200);
    });
}


    /**
     * Bulk update status for multiple sub-spaces
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'sub_space_ids' => 'required|array',
            'sub_space_ids.*' => 'required|exists:sub_spaces,id',
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'parking_space_id' => 'required|exists:parking_spaces,id',
            'operator_id' => 'required|exists:users,id',
            'status' => 'required|in:available,occupied,reserved,maintenance',
        ]);

        return DB::transaction(function () use ($validated) {
            // Verify the operator owns this parking space
            $parkingSpace = ParkingSpace::where('id', $validated['parking_space_id'])
                ->where('operator_id', $validated['operator_id'])
                ->first();

            if (!$parkingSpace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Operator does not own this parking space'
                ], 403);
            }

            // Update all sub-spaces
            $updated = SubSpace::whereIn('id', $validated['sub_space_ids'])
                ->where('parking_spot_id', $validated['parking_spot_id'])
                ->update(['status' => $validated['status']]);

            $subSpaces = SubSpace::whereIn('id', $validated['sub_space_ids'])->get();

            return response()->json([
                'success' => true,
                'message' => "{$updated} sub-space(s) updated to {$validated['status']} successfully",
                'data' => $subSpaces,
                'updated_count' => $updated,
            ], 200);
        });
    }

    /**
     * Get sub-space details
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $subSpace = SubSpace::with(['parkingSpot.parkingSpace'])->find($id);

        if (!$subSpace) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-space not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subSpace,
        ], 200);
    }

    /**
     * Get all sub-spaces for a parking spot
     * 
     * @param Request $request
     * @param int $parkingSpotId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByParkingSpot(Request $request, $parkingSpotId)
    {
        $subSpaces = SubSpace::where('parking_spot_id', $parkingSpotId)
            ->orderBy('label')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subSpaces,
            'count' => $subSpaces->count(),
        ], 200);
    }

    /**
     * Toggle sub-space status (available <-> occupied)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'parking_space_id' => 'required|exists:parking_spaces,id',
            'operator_id' => 'required|exists:users,id',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            // Verify the operator owns this parking space
            $parkingSpace = ParkingSpace::where('id', $validated['parking_space_id'])
                ->where('operator_id', $validated['operator_id'])
                ->first();

            if (!$parkingSpace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Operator does not own this parking space'
                ], 403);
            }

            $subSpace = SubSpace::where('id', $id)
                ->where('parking_spot_id', $validated['parking_spot_id'])
                ->lockForUpdate()
                ->first();

            if (!$subSpace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-space not found'
                ], 404);
            }

            $newStatus = $subSpace->status === 'available' ? 'occupied' : 'available';
            $subSpace->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Sub-space status toggled to {$newStatus} successfully",
                'data' => $subSpace->fresh(),
            ], 200);
        });
    }
}