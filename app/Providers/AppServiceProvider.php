<?php

namespace App\Providers;

use App\Models\Marketplace\DealerDemand;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Observers\VarietyObserver;
use App\Policies\Profiles\DealerPolicy;
use App\Policies\Profiles\FarmerPolicy;
use App\Policies\Marketplace\DemandPolicy;
use App\Policies\Marketplace\SupplyPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Model::automaticallyEagerLoadRelationships();
        Gate::policy(FarmerProfile::class, FarmerPolicy::class);
        Gate::policy(DealerProfile::class, DealerPolicy::class);
        Gate::policy(FarmerSupply::class, SupplyPolicy::class);
        Gate::policy(DealerDemand::class, DemandPolicy::class);
        Variety::observe(VarietyObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
