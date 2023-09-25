<?php

namespace App\Actions\Notification;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class SendFirebaseNotification
{
    use AsAction;

    /**
     * Sends a push notification using Firebase Cloud Messaging.
     *
     * This code snippet is a PHP function that sends a push notification using Firebase Cloud Messaging (FCM).
     * It takes an array of data to be sent in the push notification and an optional array of Firebase tokens representing the devices
     * that should receive the notification. The function sets the necessary headers for the HTTP request, initializes a cURL session,
     * and sets the required options. It then executes the cURL request to send the push notification using the FCM API.
     * If there is an error during the request, it logs the error.
     *
     * Finally, it returns true if the request was successful and false otherwise.
     *
     * @param array $data The data to be sent in the push notification.
     * @param array $firebaseTokens The Firebase tokens of the devices to receive the notification.
     * @return bool Returns true if the push notification was sent successfully, false otherwise.
     */
    public function handle(array $data, array $firebaseTokens = []): bool
    {
        // If the 'registration_ids' and 'to' keys are not set in the data array,
        // set the 'registration_ids' key to the Firebase tokens array.
        if (!isset($data['registration_ids']) && !isset($data['to'])) {
            $data['registration_ids'] = $firebaseTokens;
        }

        // Set the headers for the HTTP request.
        $headers = [
            'Authorization: key=' . config('app.firebase_server_key'),
            'Content-Type: application/json',
        ];

        // Initialize a cURL session and set the necessary options.
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        // Execute the cURL request.
        $res = curl_exec($ch);

        // Check if there was an error during the cURL request and log the error.
        if (curl_errno($ch)) {
            Log::error(curl_error($ch));
        }

        // Close the cURL session.
        curl_close($ch);

        // Return true if the cURL request was successful, false otherwise.
        return $res !== false;
    }
}
