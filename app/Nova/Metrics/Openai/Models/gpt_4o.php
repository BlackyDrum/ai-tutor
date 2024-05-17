<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_4o extends Model
{
    // Price in dollar per million tokens
    public float $input = 5.0;
    public float $output = 15.0;

    public $name = 'gpt-4o';

    public $width = 'full';
}
