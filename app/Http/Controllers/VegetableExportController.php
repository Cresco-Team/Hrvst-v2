<?php

namespace App\Http\Controllers;

use App\Actions\Vegetable\ExportVegetableActivityAction;
use App\Models\Product\Vegetable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VegetableExportController extends Controller
{
    public function download(Vegetable $vegetable, ExportVegetableActivityAction $export): StreamedResponse
    {
        return $export->handle($vegetable);
    }
}
