<?php

namespace App\Http\Controllers;

use App\Models\Calorie;
use App\Models\Config;
use App\Models\Food;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CaloriesController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consumed' => 'required|string',
            'calories' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $valid = $validator->valid();

        try {
            $rec = Calorie::updateOrCreate(
                ['consumed' => $valid['consumed']],
                ['calories' => $valid['calories']]
            );

            return ['status' => 'success', 'rec' => $rec];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function bmr(Config $config, Carbon $date)
    {
        $globalConfig = json_decode(Storage::disk('local')->get('global-config.json'));

        $weight = $config->weightAsOf($date);
        $age = $config->ageAsOf($date);

        // BMR = 10W + 6.25H - 5A + 5

        $base = floor((10 * $weight->weight) + (6.25 * $config->height) - (5 * $age) + 5);

        $bmr = $base * $globalConfig->exerciseLevels[$config->exercise];

        return ceil($bmr * $globalConfig->targets->{$config->target});
    }

    public function remaining(Request $request)
    {
        $config = Config::forUser($request->user()->id);

        $timezone = 'America/Guayaquil';
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'), $timezone)
            : Carbon::now($timezone);

        $calorieMap = Calorie::get(['consumed', 'calories'])
            ->keyBy(fn($c) => mb_strtolower($c->consumed));

        $totalCalories = Food::whereBetween('consumed_at', [
            $date->copy()->startOfDay()->setTimezone('UTC'),
            $date->copy()->endOfDay()->setTimezone('UTC'),
        ])
            ->get()
            ->sum(fn($food) => optional($calorieMap->get(mb_strtolower($food->consumed)))->calories ?? 0);

        return $this->bmr($config, $date) - $totalCalories;
    }
}
