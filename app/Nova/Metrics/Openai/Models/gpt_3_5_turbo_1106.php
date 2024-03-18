<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_3_5_turbo_1106 extends Model
{
    // Price in dollar per million tokens
    public float $input = 1.00;
    public float $output = 2.00;

    public $name = 'gpt-3.5-turbo-1106';

    public $width = '1/2';
}
