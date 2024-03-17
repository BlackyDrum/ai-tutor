<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_4_32k extends Model
{
    // Price in dollar per million tokens
    public float $input = 60.0;
    public float $output = 120.0;

    public $name = 'gpt-4-32k';

    public $width = '1/2';
}
