<?php

namespace App\Http\Controllers;

use App\Models\Calorie;
use Exception;
use Illuminate\Http\Request;
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
}
