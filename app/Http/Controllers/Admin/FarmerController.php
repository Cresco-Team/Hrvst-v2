<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profiles\FarmerProfile;
use App\Services\Admin\FarmerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/farmers/Index', [
            'farmers' => FarmerService::paginated(),
            'summary' => FarmerService::summary(),
        ]);
    }

    public function show(int $id): Response
    {
        $farmer = FarmerService::find($id);

        if (!$farmer) {
            abort(404, 'Farmer not found');
        }

        return Inertia::render('admin/farmers/Show', [
            'farmer' => $farmer,
        ]);
    }

    public function destroy(FarmerProfile $farmerProfile)
    {
        //
    }
}
