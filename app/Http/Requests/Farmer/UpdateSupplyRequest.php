<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostTimeSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->can('update', $this->route('supply'));
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => [
                'sometimes',
                'date',
                Rule::when(
                    $this->scheduledDateIsChanging(),
                    ['after:today'],
                ),
                'before:'.now()->addMonths(3)->toDateString(),
            ],
            'time_slot' => ['sometimes', Rule::enum(PostTimeSlot::class)],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.vegetable_id' => ['required_with:items', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'scheduled_date.before' => 'Scheduled date cannot be more than 3 months away.',
            'items.min' => 'At least one supply item is required.',
            'items.*.vegetable_id.required_with' => 'Each item must have a vegetable.',
            'items.*.vegetable_id.exists' => 'Selected vegetable does not exist.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
        ];
    }

    private function scheduledDateIsChanging(): bool
    {
        if (! $this->filled('scheduled_date')) {
            return false;
        }

        $currentDate = $this->route('supply')?->scheduled_date;

        if ($currentDate === null) {
            return true;
        }

        try {
            return ! Carbon::parse($this->input('scheduled_date'))->isSameDay($currentDate);
        } catch (\Exception) {
            return true;
        }
    }
}
