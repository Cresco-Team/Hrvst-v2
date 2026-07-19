<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateDealerAction;
use App\Actions\Admin\CreateFarmerAction;
use App\Actions\Admin\ResetUserPinAction;
use App\Actions\Admin\UpdateUserPhoneAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateDealerRequest;
use App\Http\Requests\Admin\CreateFarmerRequest;
use App\Http\Requests\Admin\UpdateUserPhoneRequest;
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
            validated: $request->safe()->all(),
        );

        return redirect()->route('admin.users.farmers.create')
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
            validated: $request->safe()->all(),
        );

        return redirect()->route('admin.users.dealers.create')
            ->with('flash', [
                'type' => 'pin',
                'message' => 'Dealer created successfully.',
                'pin' => $pin,
            ]);
    }

    public function updatePhone(UpdateUserPhoneRequest $request, User $user, UpdateUserPhoneAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated('phone_number'));
    
        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Phone number updated.',
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
