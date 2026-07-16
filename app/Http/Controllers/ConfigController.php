<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ConfigController extends Controller
{
    public function show(Request $request)
    {
        $config = Config::forUser($request->user()->id);
        $globalConfig = json_decode(Storage::disk('local')->get('global-config.json'));

        return [
            'status' => 'success',
            'sex' => $config->sex,
            'height' => round($config->height / 2.54, 1),
            'weight' => round($config->latestWeight->weight * 2.204623),
            'birthdate' => $config->birthdate->format('Y-m-d'),
            'exercise' => $config->exercise,
            'target' => $config->target,
            'exerciseLevels' => $globalConfig->exerciseLevels,
            'targets' => array_keys(get_object_vars($globalConfig->targets)),
        ];
    }

    public function update(Request $request)
    {
        $globalConfig = json_decode(Storage::disk('local')->get('global-config.json'));

        $validator = Validator::make($request->all(), [
            'sex' => 'required|string|in:m,f',
            'height' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'birthdate' => 'required|date|before:today',
            'exercise' => ['required', 'integer', Rule::in(array_keys($globalConfig->exerciseLevels))],
            'target' => ['required', 'string', Rule::in(array_keys(get_object_vars($globalConfig->targets)))],
        ]);

        if ($validator->fails()) {
            return ['status' => 401, 'message' => 'Malformed request.'];
        }

        $valid = $validator->valid();

        $config = Config::forUser($request->user()->id);

        $config->sex = $valid['sex'];
        $config->height = round($valid['height'] * 2.54, 2);
        $config->birthdate = $valid['birthdate'];
        $config->exercise = $valid['exercise'];
        $config->target = $valid['target'];
        $config->save();

        $config->weights()->create(['weight' => round($valid['weight'] / 2.204623, 2)]);

        return ['status' => 'success'];
    }
}
