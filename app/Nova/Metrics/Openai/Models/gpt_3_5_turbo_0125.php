<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_3_5_turbo_0125 extends Model
{
    // Price in dollar per million tokens
    public float $input = 0.50;
    public float $output = 1.50;

    public $name = 'gpt-3.5-turbo-0125';

    public $width = '1/2';
}
