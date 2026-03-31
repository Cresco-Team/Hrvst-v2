<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateDealerAction;
use App\Actions\Admin\CreateFarmerAction;
use App\Actions\Admin\ResetUserPinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateDealerRequest;
use App\Http\Requests\Admin\CreateFarmerRequest;
use App\Models\Address\Municipality;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function createFarmerForm(): Response
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        return Inertia::render('admin/users/CreateFarmer', [
            'municipalities' => Municipality::orderBy('name')
                ->get(['id', 'name', 'latitude', 'longitude'])
                ->toArray(),
        ]);
    }

    public function storeFarmer(
        CreateFarmerRequest $request,
        CreateFarmerAction $createFarmer,
    ): RedirectResponse {
        Gate::authorize('viewAny', FarmerProfile::class);

        ['plain_pin' => $pin] = $createFarmer->handle(
            validated: $request->safe()->except('farm_photo'),
            farmPhoto: $request->file('farm_photo'),
        );

        return redirect()->route('admin.farmers.index')
            ->with('flash', [
                'type' => 'pin',
                'message' => 'Farmer created successfully.',
                'pin' => $pin,
            ]);
    }

    public function createDealerForm(): Response
    {
        Gate::authorize('viewAny', DealerProfile::class);

        return Inertia::render('admin/users/CreateDealer');
    }

    public function storeDealer(
        CreateDealerRequest $request,
        CreateDealerAction $createDealer,
    ): RedirectResponse {
        Gate::authorize('viewAny', DealerProfile::class);

        ['plain_pin' => $pin] = $createDealer->handle(
            validated: $request->safe()->except('document'),
            document: $request->file('document'),
        );

        return redirect()->route('admin.dealers.index')
            ->with('flash', [
                'type' => 'pin',
                'message' => 'Dealer created successfully.',
                'pin' => $pin,
            ]);
    }

    public function resetPin(User $user, ResetUserPinAction $resetPin): RedirectResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $pin = $resetPin->handle($user);

        return back()->with('flash', [
            'type' => 'pin',
            'message' => 'PIN reset successfully.',
            'pin' => $pin,
        ]);
    }
}
