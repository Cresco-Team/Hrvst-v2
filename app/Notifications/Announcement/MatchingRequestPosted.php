<?php

namespace App\Notifications\Announcement;

use App\Models\Announcement\DealerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MatchingRequestPosted extends Notification
{
    use Queueable;

    public function __construct(
        public DealerRequest $request
    ) {}

    /**
     * Notification delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database representation
     */
    public function toArray(object $notifiable): array
    {
        // Get varieties that match this farmer's plantings
        $matchingItems = $this->request->items->filter(function ($item) use ($notifiable) {
            return $notifiable->farmerProfile->plantings()
                ->where('variety_id', $item->variety_id)
                ->where('status', 'active')
                ->exists();
        });

        $varietyNames = $matchingItems->map(fn($item) => 
            $item->variety->vegetable->name . ' ' . $item->variety->name
        )->join(', ');

        return [
            'type' => 'matching_request',
            'request_id' => $this->request->id,
            'dealer_id' => $this->request->dealer_id,
            'dealer_name' => $this->request->dealer->user->name,
            'transaction_date' => $this->request->transaction_date->toDateString(),
            'days_until_transaction' => now()->diffInDays($this->request->transaction_date, false),
            'matching_varieties' => $varietyNames,
            'total_items' => $this->request->items->count(),
            'message' => "Dealer needs {$varietyNames} by {$this->request->transaction_date->format('M d')}",
        ];
    }
}
