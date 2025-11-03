<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\SubSpace;
use App\Models\ParkingSpot;
use App\Events\ReservationUpdated; // ✅ Import event
use Carbon\Carbon;

class CheckReservations extends Command
{
    protected $signature = 'reservations:check';
    protected $description = 'Automatically complete expired reservations and free sub-spaces or spots';

    public function handle()
    {
        $now = Carbon::now();

        // Find expired reservations that are not yet completed or cancelled
        $expiredReservations = Reservation::where('end_time', '<', $now)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info("✅ No expired reservations found at {$now}");
            return;
        }

        foreach ($expiredReservations as $reservation) {
            $reservation->update(['status' => 'completed']);

            // Free sub-space or parking spot
           if ($reservation->sub_space_id) {
    SubSpace::where('id', $reservation->sub_space_id)
        ->update([
            'status' => 'available',
            'current_user_id' => null, // reset current user
        ]);
} else {
    ParkingSpot::where('id', $reservation->parking_spot_id)
        ->update([
            'status' => 'available',
            'current_user_id' => null, // reset current user
        ]);
}


            // ✅ Broadcast real-time update via Reverb
            event(new ReservationUpdated($reservation));

            $this->info("🟢 Reservation #{$reservation->id} marked completed, freed its space, and broadcasted update.");
        }

        $this->info("🏁 Checked and updated expired reservations at {$now}");
    }
}
