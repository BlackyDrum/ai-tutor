<?php

namespace App\Nova\Metrics\Openai;

class gpt_4 extends Model
{
    // Price in dollar per million tokens
    public float $input = 30.0;
    public float $output = 60.0;

    public $name = 'gpt-4';

    public $width = '1/2';
}
