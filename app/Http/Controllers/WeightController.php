<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;

class WeightController extends Controller
{
    public function index(Request $request)
    {
        $config = Config::forUser($request->user()->id);

        $data = $config->loggedWeights()
            ->map(fn($w) => [
                'date' => $w->created_at->toIso8601String(),
                'weight' => round($w->weight * 2.204623),
            ]);

        return ['status' => 'success', 'data' => $data];
    }
}
