<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_3_5_turbo_0125 extends Model
{
    // Price in dollar per million tokens
    public static float $input = 0.50;
    public static float $output = 1.50;

    public static string $modelName = 'gpt-3.5-turbo-0125';

    public $name = 'gpt-3.5-turbo-0125';

    public $width = '1/2';
}
