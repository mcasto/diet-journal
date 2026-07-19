<?php

namespace App\Http\Controllers;

use App\Models\ScrappedDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScrappedDayController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $valid = $validator->valid();

        $rec = ScrappedDay::updateOrCreate(
            ['user_id' => $request->user()->id, 'date' => $valid['date']],
            ['reason' => $valid['reason'] ?? null]
        );

        return ['status' => 'success', 'rec' => $rec];
    }

    public function destroy(Request $request, string $date)
    {
        ScrappedDay::where('user_id', $request->user()->id)
            ->whereDate('date', $date)
            ->delete();

        return ['status' => 'success'];
    }
}
