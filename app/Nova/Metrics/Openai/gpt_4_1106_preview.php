<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_4_1106_preview extends Model
{
    // Price in dollar per million tokens
    public static float $input = 10.0;
    public static float $output = 30.0;

    public static string $modelName = 'gpt-4-1106-preview';

    public $name = 'gpt-4-1106-preview';
}
