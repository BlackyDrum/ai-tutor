<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\Openai\Models\gpt_3_5_turbo_0125;
use App\Nova\Metrics\Openai\Models\gpt_3_5_turbo_1106;
use App\Nova\Metrics\Openai\Models\gpt_4;
use App\Nova\Metrics\Openai\Models\gpt_4_0125_preview;
use App\Nova\Metrics\Openai\Models\gpt_4_1106_preview;
use App\Nova\Metrics\Openai\Models\gpt_4_1106_vision_preview;
use App\Nova\Metrics\Openai\Models\gpt_4_32k;
use App\Nova\Metrics\Openai\TotalCosts;
use Laravel\Nova\Dashboard;

class OpenAI extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            new TotalCosts(),
            ...self::models(),
        ];
    }

    public $showRefreshButton = true;

    public function name()
    {
        return 'OpenAI';
    }

    public static function models()
    {
        return [
            new gpt_4_0125_preview(),
            new gpt_4_1106_vision_preview(),
            new gpt_4_1106_preview(),
            new gpt_4(),
            new gpt_4_32k(),
            new gpt_3_5_turbo_0125(),
            new gpt_3_5_turbo_1106(),
        ];
    }

    /**
     * Get the URI key for the dashboard.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'openai';
    }
}
