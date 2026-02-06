<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planting = $this->route('planting');
        
        return $this->user()->hasRole('farmer') 
            && $this->user()->can('update', $planting);
    }

    public function rules(): array
    {
        $planting = $this->route('planting');
        
        return [
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'expected_harvest_date' => [
                'required',
                'date',
                'after:' . $planting->date_planted->toDateString(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'weight_kg.required' => 'Weight is required.',
            'weight_kg.numeric' => 'Weight must be a number.',
            'weight_kg.min' => 'Weight must be at least 0.1 kg.',
            'weight_kg.max' => 'Weight cannot exceed 99,999 kg.',
            'expected_harvest_date.required' => 'Expected harvest date is required.',
            'expected_harvest_date.date' => 'Invalid date format.',
            'expected_harvest_date.after' => 'Harvest date must be after the planting date.',
        ];
    }
}
