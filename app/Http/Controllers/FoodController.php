<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    public function index()
    {
        try {
            return [
                'status' => 'success',
                'data' => Food::orderBy('consumed_at', 'desc')
                    ->get()
                    ->map(function ($rec) {
                        return [
                            'id' => $rec->id,
                            'consumed' => $rec->consumed,
                            'consumed_at' => $rec->consumed_at
                        ];
                    })
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consumed' => 'required|string',
            'date' => 'nullable|string',
            'time' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $valid = $validator->valid();

        // Create the datetime from the request data
        $consumedAt = isset($valid['date']) && isset($valid['time']) ? Carbon::parse("{$valid['date']} {$valid['time']}", 'America/Guayaquil')
            ->setTimezone('UTC') : now();

        $rec = new Food();
        $rec->user_id = $request->user()->id;
        $rec->consumed = $valid['consumed'];
        $rec->consumed_at = $consumedAt; // Use the parsed datetime

        $rec->save();

        return ['status' => 'success', 'rec' => $rec];
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'consumed' => 'required|string',
            'date' => 'required|string',
            'time' => 'required|string'
        ]);

        if ($validator->fails()) { // Fixed: was failed()
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $rec = Food::find($id);
        if (!$rec) {
            return ['status' => 404, 'message' => 'Record not found.'];
        }

        $valid = $validator->valid();

        // Explicitly specify the input timezone and convert to UTC
        $dateTime = Carbon::parse("{$valid['date']} {$valid['time']}", 'America/Guayaquil')
            ->setTimezone('UTC');

        $rec->consumed = $valid['consumed'];
        $rec->consumed_at = $dateTime; // Fixed: was setting created_at
        $rec->save();

        return ['status' => 'success', 'rec' => $rec];
    }

    public function destroy(int $id)
    {
        $rec = Food::find($id);
        if (!$rec) {
            return ['status' => 404, 'Record not found'];
        }

        $rec->delete();

        return ['status' => 'success'];
    }

    /**
     * Calculate duration between start time and now, return in hours & minutes
     *
     * @param string|\DateTime $start
     * @return string
     */
    private function getReadableDuration($start, $end)
    {
        $startTime = Carbon::parse($start);
        $now = Carbon::parse($end);

        $diff = $startTime->diff($now);

        if ($diff->h > 0 && $diff->i > 0) {
            return "{$diff->h} hour" . ($diff->h > 1 ? 's' : '') . " and {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return "{$diff->h} hour" . ($diff->h > 1 ? 's' : '');
        } else {
            return "{$diff->i} minute" . ($diff->i > 1 ? 's' : '');
        }
    }

    /**
     * Convert UTC datetime to user's timezone from ISO 8601 end parameter
     *
     * @param string|\DateTime $utcDateTime
     * @param string $userIso8601
     * @return Carbon
     */
    public function convertToUserTimezone($utcDateTime, $userIso8601)
    {
        // Parse the user's ISO 8601 datetime to extract the timezone
        $userTime = Carbon::parse($userIso8601);

        // Get the timezone from the user's datetime
        $userTimezone = $userTime->getTimezone();

        // Convert the database UTC time to the user's timezone
        return Carbon::parse($utcDateTime)
            ->setTimezone('UTC') // Ensure it's treated as UTC
            ->setTimezone($userTimezone)
            ->format('g:i A');
    }

    public function latest(Request $request, string $end)
    {
        $user_id = $request->user()->id;

        $meal = Food::where('user_id', $user_id)
            ->orderBy('consumed_at', 'desc')
            ->first();

        $duration = $this->getReadableDuration($meal->consumed_at, $end);
        $time = $this->convertToUserTimezone($meal->consumed_at, $end);

        $response = "You ate {$meal->consumed} {$duration} ago at {$time}";

        return $response;
    }
}
