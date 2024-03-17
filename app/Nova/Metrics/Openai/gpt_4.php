<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_4 extends Model
{
    // Price in dollar per million tokens
    public float $input = 30.0;
    public float $output = 60.0;

    public $name = 'gpt-4';

    public $width = '1/2';
}
