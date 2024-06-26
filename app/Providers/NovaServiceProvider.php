<?php

namespace App\Providers;

use App\Nova\Agent;
use App\Nova\Blacklist;
use App\Nova\Collection;
use App\Nova\Conversation;
use App\Nova\Dashboards\Main;
use App\Nova\Dashboards\OpenAI;
use App\Nova\Document;
use App\Nova\Embedding;
use App\Nova\Message;
use App\Nova\Module;
use App\Nova\SharedConversation;
use App\Nova\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Nova::footer(function($request) {
            return Blade::render('<div class="text-center">FH Aachen - University of Applied Sciences</div>');
        });

        Nova::mainMenu(function(Request $request) {
            return [
                MenuSection::dashboard(Main::class)->icon('chart-bar'),

                MenuSection::dashboard(OpenAI::class)->icon('chip'),

                MenuSection::make('FH Aachen', [
                    MenuItem::resource(Module::class),
                ])->icon('academic-cap')->collapsable(),

                MenuSection::make('ChromaDB', [
                    MenuItem::resource(Collection::class),
                    MenuItem::resource(Embedding::class),
                    MenuItem::resource(Document::class),
                ])->icon('database')->collapsable(),

                MenuSection::make('Chat', [
                    MenuItem::resource(Agent::class),
                    MenuItem::resource(Conversation::class),
                    MenuItem::resource(Message::class),
                    MenuItem::resource(SharedConversation::class),
                ])->icon('annotation')->collapsable(),

                MenuSection::make('Users', [
                    MenuItem::resource(User::class),
                    MenuItem::resource(Blacklist::class)->name('Blacklist'),
                ])->icon('user')->collapsable(),

                MenuSection::make('Health', [
                    MenuItem::externalLink('Performance', '/pulse')
                        ->openInNewTab(),
                ])->icon('heart')->collapsable(),

                MenuItem::externalLink('Back to ' . config('app.name'), '/')
            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return $user->admin;
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new Main,
            new OpenAI,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
