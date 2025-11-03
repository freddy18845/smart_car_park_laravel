<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SMSOnlineGHService
{
    protected static $baseUrl = "https://api.smsonlinegh.com/v5/message/sms/send";
    protected static $apiKey = "b1ac9765ed4f9d7fcaf94dd0b24cdbef0d72b605d182f770857a72f25e74e974"; // put in .env instead!

    public static function sendSMS(array $recipients, string $message, string $sender = 'MyApp')
    {
        $response = Http::withHeaders([
            'Authorization' => 'key ' . self::$apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post(self::$baseUrl, [
            'sender'       => 'SmartCarPak',
            'text'         => $message,
            'type'         => 0,
            'destinations' => $recipients,
        ]);

        if ($response->failed()) {
            \Log::error("SMS sending failed: " . $response->body());
        }

        return $response->successful();
    }
}
