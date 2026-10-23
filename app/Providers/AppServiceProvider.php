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
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Component;

use function Livewire\on;

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

        on('exception', function ($target, $e, $stopPropagation) {
            if ($e instanceof AuthorizationException && $target instanceof Component) {
                $target->dispatch(
                    'permission-denied',
                    message: 'No tienes los permisos suficientes para realizar esta acción.'
                );

                $stopPropagation();
            }
        });
    }
}
