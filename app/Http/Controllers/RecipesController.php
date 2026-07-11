<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecipesController extends Controller
{
    public function show(string $consumed)
    {
        $ingredients = Recipe::where('consumed', $consumed)
            ->orderBy('id')
            ->get(['ingredient', 'calories']);

        return ['status' => 'success', 'data' => $ingredients];
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consumed' => 'required|string',
            'ingredients' => 'present|array',
            'ingredients.*.ingredient' => 'required|string',
            'ingredients.*.calories' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $valid = $validator->valid();

        Recipe::where('consumed', $valid['consumed'])->delete();

        foreach ($valid['ingredients'] as $item) {
            Recipe::create([
                'consumed' => $valid['consumed'],
                'ingredient' => $item['ingredient'],
                'calories' => $item['calories'],
            ]);
        }

        return ['status' => 'success'];
    }
}
