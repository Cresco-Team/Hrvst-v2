<?php

namespace App\Http\Requests\Farmer;

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
            'expected_harvest_date' => [
                'required',
                'date',
                'after:date_planted',
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
            'expected_harvest_date.required' => 'Expected harvest date is required.',
            'expected_harvest_date.date' => 'Invalid harvest date format.',
            'expected_harvest_date.after' => 'Harvest date must be after planting date.',
        ];
    }

    public function validatedWithStatus(): array
    {
        $validated = $this->validated();
        
        // Auto-set status to 'expired' if expected harvest is in the past
        if (Carbon::parse($validated['expected_harvest_date'])->isPast()) {
            $validated['status'] = 'expired';
        } else {
            $validated['status'] = 'active';
        }
        
        return $validated;
    }
}
