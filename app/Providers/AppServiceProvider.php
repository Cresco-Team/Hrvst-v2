<?php

namespace App\Providers;

use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use App\Observers\PostItemObserver;
use App\Observers\VarietyObserver;
use App\Policies\Marketplace\PostItemPolicy;
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
        Gate::policy(PostItem::class, PostItemPolicy::class);

        Gate::define('not-admin', function (User $user) {
            return $user->hasRole('admin') === false;
        });

        Variety::observe(VarietyObserver::class);
        PostItem::observe(PostItemObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }
}
