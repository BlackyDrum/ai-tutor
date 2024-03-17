<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_4_0125_preview extends Model
{
    // Price in dollar per million tokens
    public float $input = 10.0;
    public float $output = 30.0;

    public $name = 'gpt-4-0125-preview';
}
