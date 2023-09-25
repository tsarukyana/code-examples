<?php

namespace App\Actions\Event;

use App\Actions\Common\StoreFile;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CreateEvent
{
    use AsAction;

    /**
     * Handles the input array and creates a new Event.
     *  This code snippet is a PHP function that handles the input array and creates a new Event object.
     *  It takes an array $input as a parameter, which contains the event data.
     *  The function first retrieves the time offset from the request header or falls back to the app configuration.
     *  It then parses the event start and end times from the input array, converts them to UTC timezone, and formats them using a specific date and time format.
     *
     *  The function then creates an array $eventData with the event data extracted from the input array.
     *  It includes properties such as title, start time, end time, church ID, photo path, location, location view, location description, general info, and coordinates.
     *
     *  Next, the function creates a new Event object by calling the create method on the Event model class, passing the $eventData array as an argument.
     *  If a photo path is provided and it is an instance of the UploadedFile class, the function updates the photo path of the event by calling the updateQuietly method on the event object.
     *
     *  Finally, the function returns the newly created event object.
     *
     * @param array $input The input array containing the event data.
     * @return Event The newly created event.
     * @throws \Exception Exception thrown if an error occurs while creating the event.
     */
    public function handle(array $input)
    {
        // Get the time offset from the request header or fallback to the app config
        $timeOffset = request()->header('x-time-offset') ?? config('app.time_offset');

        // Parse the event start time and convert it to UTC timezone
        $startTime = Carbon::parse($input['event_start_time'], $timeOffset)
            ->timezone('UTC')
            ->format(Event::DATE_TIME_HOUR_MINUTE_FORMAT);

        // Parse the event end time and convert it to UTC timezone
        $endTime = Carbon::parse($input['event_end_time'], $timeOffset)
            ->timezone('UTC')
            ->format(Event::DATE_TIME_HOUR_MINUTE_FORMAT);

        // Create an array with the event data
        $eventData = [
            'title' => $input['title'],
            'event_start_time' => $startTime,
            'event_end_time' => $endTime,
            'church_id' => $input['church_id'],
            'photo_path' => $input['photo_path'] ?? null,
            'location' => $input['location'],
            'location_view' => $input['location_view'],
            'location_description' => $input['location_description'] ?? null,
            'general_info' => $input['general_info'] ?? null,
            'coordinate' => $input['coordinate'] ?? new Point(
                    $input['lat'] ?? ($input['latitude'] ?? 40.16557),
                    $input['lng'] ?? ($input['longitude'] ?? 44.2946)
                ),
        ];

        // Create a new event
        $event = Event::create($eventData);

        // Update the photo path if it is provided and is an instance of UploadedFile
        if (!empty($input['photo_path']) && $input['photo_path'] instanceof UploadedFile) {
            $event->updateQuietly(['photo_path' => StoreFile::run('events/' . $event->id . '/images', $input['photo_path'])]);
        }

        // Return the newly created event
        return $event;
    }
}
