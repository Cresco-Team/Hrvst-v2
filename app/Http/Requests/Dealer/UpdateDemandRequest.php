<?php

namespace App\Http\Requests\Dealer;

use App\Enums\PostTimeSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer');
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
            'items.*.vegetable_id' => ['required_with:items', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Transaction date must be in the future.',
            'scheduled_date.before' => 'Transaction date cannot be more than 3 months away.',
            'time_slot.enum' => 'Time slot must be morning, afternoon, or evening.',
            'items.min' => 'At least one item is required.',
            'items.*.vegetable_id.required_with' => 'Each item must have a vegetable.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity is too low.',
        ];
    }

    private function scheduledDateIsChanging(): bool
    {
        if (! $this->filled('scheduled_date')) {
            return false;
        }

        $currentDate = $this->route('demand')?->scheduled_date;

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
