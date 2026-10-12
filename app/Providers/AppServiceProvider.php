<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\OwnedRecordPolicy;
use App\Models\{
    Publication,
    Event,
    Researcher,
    ResearchGroup,
    Institution,
    Journal,
    PublicationType,
    BookType
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Publication::class,
            Event::class,
            Researcher::class,
            ResearchGroup::class,
            Institution::class,
            Journal::class,
            PublicationType::class,
            BookType::class
        ] as $model) {
            Gate::policy($model, OwnedRecordPolicy::class);
        }
    }
}
