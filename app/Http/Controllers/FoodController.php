<?php

namespace App\Http\Controllers;

use App\Models\Calorie;
use App\Models\Food;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    /**
     * Keyed by lowercased `consumed` name. Eloquent's own relation-matching
     * for `Food::with('calorie')` compares keys with exact PHP string
     * equality, which is case-sensitive even though the DB collation isn't —
     * so calorie lookups are done against this map instead.
     */
    private function calorieMap()
    {
        return Calorie::get(['consumed', 'calories'])
            ->keyBy(fn($c) => mb_strtolower($c->consumed));
    }

    private function formatFood(Food $rec, $calorieMap)
    {
        return [
            'id' => $rec->id,
            'consumed' => $rec->consumed,
            'consumed_at' => $rec->consumed_at,
            'calories' => optional($calorieMap->get(mb_strtolower($rec->consumed)))->calories,
        ];
    }

    public function index(Request $request)
    {
        $timezone = 'America/Guayaquil';

        try {
            $calorieMap = $this->calorieMap();

            // Range mode: pulls an unpaginated span of days (e.g. the Calories
            // page's past-month view), rather than a single day of entries.
            if ($request->filled('from') || $request->filled('to')) {
                $from = $request->filled('from')
                    ? Carbon::parse($request->query('from'), $timezone)->startOfDay()
                    : Carbon::now($timezone)->subMonth()->startOfDay();

                $to = $request->filled('to')
                    ? Carbon::parse($request->query('to'), $timezone)->endOfDay()
                    : Carbon::now($timezone)->endOfDay();

                $data = Food::whereBetween('consumed_at', [$from->setTimezone('UTC'), $to->setTimezone('UTC')])
                    ->orderBy('consumed_at', 'desc')
                    ->get()
                    ->map(fn($rec) => $this->formatFood($rec, $calorieMap));

                return ['status' => 'success', 'data' => $data];
            }

            // Day mode (Food Log): defaults to today, paginated. Pulled as a
            // single day-scoped collection so the daily total reflects the
            // whole day, not just whichever page is currently displayed.
            $date = $request->filled('date')
                ? Carbon::parse($request->query('date'), $timezone)
                : Carbon::now($timezone);

            $start = $date->copy()->startOfDay()->setTimezone('UTC');
            $end = $date->copy()->endOfDay()->setTimezone('UTC');

            $day = Food::whereBetween('consumed_at', [$start, $end])
                ->orderBy('consumed_at', 'desc')
                ->get();

            $dailyCalories = $day->sum(fn($rec) => optional($calorieMap->get(mb_strtolower($rec->consumed)))->calories ?? 0);

            $perPage = (int) $request->query('per_page', 10);
            $page = (int) $request->query('page', 1);

            $paginator = new LengthAwarePaginator(
                $day->forPage($page, $perPage)->map(fn($rec) => $this->formatFood($rec, $calorieMap))->values(),
                $day->count(),
                $perPage,
                $page,
            );

            return [
                'status' => 'success',
                'daily_calories' => $dailyCalories,
                ...$paginator->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function show(int $id)
    {
        $rec = Food::find($id);

        if (!$rec) {
            return ['status' => 404, 'message' => 'Record not found.'];
        }

        return ['status' => 'success', 'rec' => $this->formatFood($rec, $this->calorieMap())];
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

        // mc-todo:
        // get calories by `consumed`
        // if calories exist, then return

        return ['status' => 'success', 'rec' => $rec, 'response' => 'This is a test'];
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
        logger()->info($end);

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
