<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\Openai\Models\gpt_3_5_turbo_0125;
use App\Nova\Metrics\Openai\Models\gpt_3_5_turbo_1106;
use App\Nova\Metrics\Openai\Models\gpt_4;
use App\Nova\Metrics\Openai\Models\gpt_4_0125_preview;
use App\Nova\Metrics\Openai\Models\gpt_4_1106_preview;
use App\Nova\Metrics\Openai\Models\gpt_4_turbo;
use App\Nova\Metrics\Openai\Models\gpt_4_32k;
use App\Nova\Metrics\Openai\Costs;
use App\Nova\Metrics\Openai\Models\gpt_4o;
use Laravel\Nova\Dashboard;

class OpenAI extends Dashboard
{
    public $showRefreshButton = true;

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [new Costs(), ...self::models()];
    }

    public function name()
    {
        return 'OpenAI';
    }

    public static function models()
    {
        return [
            new gpt_4_turbo(),
            new gpt_4_0125_preview(),
            new gpt_4_1106_preview(),
            new gpt_4o(),
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
