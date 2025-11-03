<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\SubSpace;
use App\Models\ParkingSpot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoExpireReservations extends Command
{
    protected $signature = 'reservations:auto-expire';
    protected $description = 'Automatically expire past reservations and free up parking spaces';

    public function handle()
    {
        $this->info('Starting reservation expiration process...');
        
        $now = Carbon::now();
        $expiredCount = 0;
        $freedSpaces = 0;

        try {
            DB::transaction(function () use ($now, &$expiredCount, &$freedSpaces) {
                // Find all expired reservations that are still active
                $expiredReservations = Reservation::where('end_time', '<', $now)
                    ->whereIn('status', ['booked', 'reserved', 'occupied'])
                    ->lockForUpdate()
                    ->get();

                $expiredCount = $expiredReservations->count();

                if ($expiredCount === 0) {
                    $this->info('No expired reservations found.');
                    return;
                }

                $this->info("Found {$expiredCount} expired reservation(s).");

                foreach ($expiredReservations as $reservation) {
                    // Determine new status based on current one
                    $newStatus = match ($reservation->status) {
                        'booked'    => 'cancelled',
                        'reserved', 
                        'occupied'  => 'completed',
                        default     => $reservation->status,
                    };

                    $reservation->update(['status' => $newStatus]);

                    // Free up the parking space or sub-space
                    if ($reservation->sub_space_id) {
                        SubSpace::where('id', $reservation->sub_space_id)
                            ->update(['status' => 'available']);
                        $this->line("  - Freed SubSpace ID: {$reservation->sub_space_id}");
                    } else {
                        ParkingSpot::where('id', $reservation->parking_spot_id)
                            ->update(['status' => 'available']);
                        $this->line("  - Freed ParkingSpot ID: {$reservation->parking_spot_id}");
                    }

                    $freedSpaces++;

                    // Log each action
                    Log::info("Reservation ID {$reservation->id} expired", [
                        'reservation_id' => $reservation->id,
                        'user_id'        => $reservation->user_id,
                        'old_status'     => $reservation->getOriginal('status'),
                        'new_status'     => $newStatus,
                        'end_time'       => $reservation->end_time,
                    ]);
                }
            });

            $this->info("✓ Successfully processed {$expiredCount} reservation(s) and freed {$freedSpaces} space(s).");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to expire reservations: ' . $e->getMessage());
            Log::error('Reservation expiration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
