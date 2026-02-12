<?php

namespace App\Http\Requests\Farmer;

use App\PlantingStatus;
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
            'asking_price' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'expiration_date' => ['required', 'date', 'after:today', 'before:' . now()->addMonths(6)->toDateString()],
            'image_path' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Please select a vegetable variety to plant.',
            'variety_id.exists' => 'The selected variety does not exist.',
            'weight_kg.required' => 'Weight is required.',
            'weight_kg.numeric' => 'Weight must be a number.',
            'weight_kg.min' => 'Weight must be at least 0.1 kg.',
            'weight_kg.max' => 'Weight cannot exceed 99,999 kg.',
            'asking_price.required' => 'Asking price is required.',
            'asking_price.numeric' => 'Price must be a number.',
            'asking_price.min' => 'Price must be at least ₱0.00.',
            'asking_price.max' => 'Price cannot exceed ₱999.99.',
            'expiration_date.required' => 'Expiration date is required.',
            'expiration_date.date' => 'Expiration date must be a valid date.',
            'expiration_date.after' => 'Expiration date must be in the future.',
            'expiration_date.before' => 'Expiration date cannot be more than 6 months away.',
            'image_path.required' => 'An image of the planting is required.',
            'image_path.image' => 'The uploaded file must be an image.',
            'image_path.mimes' => 'The image must be a file of type: jpeg, jpg, png, webp.',
            'image_path.max' => 'The image cannot exceed 5MB in size.',
        ];
    }

    public function validatedWithStatus(): array
    {
        $validated = $this->validated();
        
        // Auto-set status to 'archived' if expiration date is in the past
        if (Carbon::parse($validated['expiration_date'])->isPast()) {
            $validated['status'] = PlantingStatus::Archived;
        } else {
            $validated['status'] = PlantingStatus::Available;
        }
        
        return $validated;
    }
}
