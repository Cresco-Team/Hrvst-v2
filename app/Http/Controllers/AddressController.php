<?php

namespace App\Http\Controllers;

use App\Models\Address\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Return all barangays belonging to the given municipality.
     * Guest-accessible: required for the registration form cascade.
     */
    public function barangays(Request $request): JsonResponse
    {
        $request->validate([
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
        ]);

        $barangays = Barangay::where('municipality_id', $request->integer('municipality_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($barangays);
    }
}
