<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendReservationReminder extends Command
{
    protected $signature = 'reservations:send-reminders';
    protected $description = 'Send SMS reminders for reservations about to expire (only once)';

    public function handle()
    {
        $now = Carbon::now();

        // Find reservations that will expire in the next 5 minutes
        $targetTime = $now->copy()->addMinutes(5);

        $reservations = Reservation::with(['user', 'parkingSpace'])
            ->whereIn('status', ['booked', 'reserved'])
            ->whereBetween('end_time', [$now, $targetTime])
            ->where('reminder_sent', false) // ✅ Only those not reminded yet
            ->get();

        if ($reservations->isEmpty()) {
            $this->info('✅ No reservations need reminders right now.');
            return;
        }

        foreach ($reservations as $reservation) {
            try {
                $minutesLeft = $now->diffInMinutes($reservation->end_time, false);
                $userPhone = $reservation->user->phone;
                $parkingName = $reservation->parkingSpace->name ?? 'your reserved parking area';

                // ✅ Send SMS reminder
                \App\Services\SMSOnlineGHService::sendSMS(
                    [$userPhone],
                    "Hello! Your reservation at {$parkingName} expires in {$minutesLeft} minutes. Please return or extend your time."
                );

                // ✅ Mark reminder as sent
                $reservation->update(['reminder_sent' => true]);

                Log::info("📩 Reminder sent to {$userPhone} for reservation #{$reservation->id}");
            } catch (\Exception $e) {
                Log::error("❌ Failed to send SMS for reservation #{$reservation->id}: " . $e->getMessage());
            }
        }

        $this->info('✅ Reservation reminder process completed successfully.');
    }
}