<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_4o_mini extends Model
{
    // Price in dollar per million tokens
    public float $input = 0.15;
    public float $output = 0.60;

    public $name = 'gpt-4o-mini';

    public $width = '1/2';
}
