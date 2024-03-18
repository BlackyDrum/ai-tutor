<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_4_0125_preview extends Model
{
    // Price in dollar per million tokens
    public float $input = 10.0;
    public float $output = 30.0;

    public $name = 'gpt-4-0125-preview';
}
