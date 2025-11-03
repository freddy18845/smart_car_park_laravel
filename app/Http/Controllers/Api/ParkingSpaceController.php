<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingSpaceController extends Controller
{
    /**
     * Create a new parking space with spots and sub-spaces
     */
    public function store(Request $request)
    {
        $validated = $this->validateParkingSpace($request);

        DB::transaction(function () use ($validated, &$parkingSpace) {
            $parkingSpace = ParkingSpace::create($validated);

            $this->createSpotsAndSubSpaces($parkingSpace, $validated['spots'] ?? []);
        });

        return response()->json([
            'message' => 'Parking space created successfully',
            'data' => $parkingSpace->load('parkingSpots.subSpaces'),
        ], 201);
    }

    /**
     * Update an existing parking space by ID
     */
    public function update(Request $request, $id)
    {
        $validated = $this->validateParkingSpace($request);

        DB::transaction(function () use ($validated, $id, &$parkingSpace) {
            $parkingSpace = ParkingSpace::findOrFail($id);
            $parkingSpace->update($validated);

            // Delete old spots and sub-spaces
            $parkingSpace->parkingSpots()->delete();

            $this->createSpotsAndSubSpaces($parkingSpace, $validated['spots'] ?? []);
        });

        return response()->json([
            'message' => 'Parking space updated successfully',
            'data' => $parkingSpace->load('parkingSpots.subSpaces'),
        ], 200);
    }

    /**
     * Show a parking space by ID
     */
    public function show($id)
    {
        $parkingSpace = ParkingSpace::with('parkingSpots.subSpaces')->find($id);

        if (!$parkingSpace) {
            return response()->json([
                'message' => 'Parking space not found',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'data' => $parkingSpace,
        ], 200);
    }

    /**
     * Validate parking space request
     */
    private function validateParkingSpace(Request $request)
    {
        return $request->validate([
            'operator_id'   => 'required|exists:users,id',
            'name'          => 'required|string|max:255',
            'location'      => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'other_contact' => 'nullable|string|max:50',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'spots'         => 'nullable|array',
            'spots.*.name'  => 'required|string',
            'spots.*.status'=> 'required|string',
            'spots.*.latitude' => 'nullable|numeric',
            'spots.*.longitude'=> 'nullable|numeric',
            'spots.*.directions' => 'nullable|string',
            'spots.*.distance' => 'nullable|numeric',
            'spots.*.sub_spaces' => 'nullable|array',
            'spots.*.sub_spaces.*.label' => 'required|string',
            'spots.*.sub_spaces.*.status'=> 'required|string',
            'spots.*.sub_spaces.*.latitude' => 'nullable|numeric',
            'spots.*.sub_spaces.*.longitude'=> 'nullable|numeric',
        ]);
    }
public function index()
{
    $spaces = ParkingSpace::with('parkingSpots.subSpaces')->get();

    // Add available sub-space count for each space
    $spaces->transform(function ($space) {
        $availableSubSpaces = 0;

        foreach ($space->parkingSpots as $spot) {
            foreach ($spot->subSpaces as $subSpace) {
                if ($subSpace->status === 'available') {
                    $availableSubSpaces++;
                }
            }
        }

        // Attach count as a custom attribute
        $space->available_sub_spaces = $availableSubSpaces;
        return $space;
    });

    return response()->json([
        'data' => $spaces,
    ], 200);
}

/**
     * Get parking spaces by operator ID
     */
    public function getByOperator($operatorId)
    {
        $spaces = ParkingSpace::with('parkingSpots.subSpaces')
            ->where('operator_id', $operatorId)
            ->get();

        return response()->json([
            'data' => $spaces,
        ], 200);
    }
    /**
     * Helper: create spots and sub-spaces for a parking space
     */
    private function createSpotsAndSubSpaces(ParkingSpace $parkingSpace, array $spots)
    {
        foreach ($spots as $spotData) {
            $subSpaces = $spotData['sub_spaces'] ?? [];
            unset($spotData['sub_spaces']);

            $spot = $parkingSpace->parkingSpots()->create($spotData);

            foreach ($subSpaces as $subSpaceData) {
                $spot->subSpaces()->create($subSpaceData);
            }
        }
    }
}
