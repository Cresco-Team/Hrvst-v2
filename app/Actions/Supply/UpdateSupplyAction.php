<?php

namespace App\Actions\Supply;

use App\Models\Schedule\Post;
use Illuminate\Support\Arr;

final class UpdateSupplyAction
{
    public function handle(Post $post, array $validated): Post
    {
        $post->update(Arr::only($validated, ['scheduled_date', 'time_slot']));

        if (array_key_exists('items', $validated)) {
            $this->syncItems($post, $validated['items']);
        }

        return $post->fresh(['postItems.vegetable']);
    }

    private function syncItems(Post $post, array $items): void
    {
        $incoming = collect($items);
        $submittedIds = $incoming->pluck('id')->filter()->values()->all();

        if (empty($submittedIds)) {
            $post->postItems()->delete();
        } else {
            $post->postItems()->whereNotIn('id', $submittedIds)->delete();
        }

        foreach ($incoming as $item) {
            $data = Arr::only($item, ['vegetable_id', 'quantity_kg']);

            if (! empty($item['id'])) {
                $post->postItems()->where('id', $item['id'])->update($data);
            } else {
                $post->postItems()->create($data);
            }
        }
    }
}
