<?php

namespace App\Providers;

use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use App\Observers\PostObserver;
use App\Observers\VarietyObserver;
use App\Policies\Marketplace\PostPolicy;
use App\Policies\Profiles\DealerPolicy;
use App\Policies\Profiles\FarmerPolicy;
use App\Policies\VegetablePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        URL::forceScheme('https');
        $this->configureDefaults();
        Model::automaticallyEagerLoadRelationships();

        Gate::policy(FarmerProfile::class, FarmerPolicy::class);
        Gate::policy(DealerProfile::class, DealerPolicy::class);
        Gate::policy(Vegetable::class, VegetablePolicy::class);
        Gate::policy(Post::class, PostPolicy::class);

        Gate::define('not-admin', function (User $user) {
            return $user->hasRole('admin') === false;
        });

        Variety::observe(VarietyObserver::class);
        Post::observe(PostObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
            : null
        );
    }
}
