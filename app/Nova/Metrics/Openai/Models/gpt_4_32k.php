<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_4_32k extends Model
{
    // Price in dollar per million tokens
    public float $input = 60.0;
    public float $output = 120.0;

    public $name = 'gpt-4-32k';

    public $width = '1/2';
}
