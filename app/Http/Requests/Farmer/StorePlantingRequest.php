<?php

namespace App\Http\Requests\Farmer;

use App\Models\Product\Variety;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StorePlantingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer');
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'exists:varieties,id'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'date_planted' => [
                'required', 
                'date', 
                'before_or_equal:today',
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Please select a variety to plant.',
            'variety_id.exists' => 'The selected variety does not exist.',
            'weight_kg.required' => 'Weight is required.',
            'weight_kg.numeric' => 'Weight must be a number.',
            'weight_kg.min' => 'Weight must be at least 0.1 kg.',
            'weight_kg.max' => 'Weight cannot exceed 99,999 kg.',
            'date_planted.required' => 'Planting date is required.',
            'date_planted.date' => 'Invalid date format.',
            'date_planted.before_or_equal' => 'Cannot plant in the future.',
            'date_planted.after_or_equal' => 'Planting date cannot be more than 1 year in the past.',
        ];
    }

    /**
     * Get validated data with auto-calculated expected_harvest_date.
     */
    public function validatedWithExpectedHarvest(): array
    {
        $validated = $this->validated();
        
        $variety = Variety::findOrFail($validated['variety_id']);
        $datePlanted = Carbon::parse($validated['date_planted']);
        
        $validated['expected_harvest_date'] = $datePlanted
            ->addWeeks($variety->weeks_to_harvest)
            ->toDateString();
        
        // Auto-set status to 'expired' if expected harvest is in the past
        if (Carbon::parse($validated['expected_harvest_date'])->isPast()) {
            $validated['status'] = 'expired';
        } else {
            $validated['status'] = 'active';
        }
        
        return $validated;
    }
}
