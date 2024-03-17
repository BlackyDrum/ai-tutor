<?php

namespace App\Nova\Metrics\Openai;

class gpt_4_1106_preview extends Model
{
    // Price in dollar per million tokens
    public float $input = 10.0;
    public float $output = 30.0;

    public $name = 'gpt-4-1106-preview';
}
