<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\Collections;
use App\Nova\Metrics\ConversationsPerDay;
use App\Nova\Metrics\Embeddings;
use App\Nova\Metrics\MessagesPerDay;
use App\Nova\Metrics\Ratings;
use App\Nova\Metrics\Users;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    public $showRefreshButton = true;

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            new Users(),
            new ConversationsPerDay(),
            new MessagesPerDay(),
            new Ratings(),
            new Collections(),
            new Embeddings(),
        ];
    }

    public function name()
    {
        return 'App';
    }
}
