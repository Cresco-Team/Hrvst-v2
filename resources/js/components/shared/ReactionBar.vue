<script setup lang="ts">
import axios from 'axios'
import { ThumbsDown, ThumbsUp } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

interface Props {
	reactionableType: 'DealerRequest' | 'FarmerOffering'
	reactionableId: number
	counts: Record<string, number>
	userReaction?: string | null
	variant?: 'thumbs' | 'emoji'
}

const props = withDefaults(defineProps<Props>(), {
	variant: 'thumbs',
	userReaction: null,
})

const isSubmitting = ref(false)
const localCounts = ref({ ...props.counts })
const localUserReaction = ref(props.userReaction)

const thumbsUpCount = computed(() => localCounts.value.thumbs_up || 0)
const thumbsDownCount = computed(() => localCounts.value.thumbs_down || 0)
const hasThumbsUp = computed(() => localUserReaction.value === 'thumbs_up')
const hasThumbsDown = computed(() => localUserReaction.value === 'thumbs_down')

async function toggleReaction(reactionType: string) {
	if (isSubmitting.value) return

	isSubmitting.value = true

	try {
		const { data } = await axios.post('/reactions/toggle', {
			reactionable_type: props.reactionableType,
			reactionable_id: props.reactionableId,
			reaction_type: reactionType,
		})

		localCounts.value = data.reaction_counts
		localUserReaction.value = data.user_reaction
	} catch (error) {
		console.error('Error toggling reaction:', error)
		// Optionally: show a toast notification here
	} finally {
		isSubmitting.value = false
	}
}

// Emoji reactions (for FarmerOffering)
const emojiReactions = ['👍', '❤️', '🔥', '😍', '👏']

function getEmojiCount(emoji: string) {
	return localCounts.value[emoji] || 0
}

function hasEmoji(emoji: string) {
	return localUserReaction.value === emoji
}
</script>

<template>
  <div class="flex items-center gap-2">
    <!-- Thumbs variant (for DealerRequest) -->
    <template v-if="variant === 'thumbs'">
      <Button
        variant="outline"
        size="sm"
        :class="cn(
          'gap-2',
          hasThumbsUp && 'border-green-500 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
        )"
        :disabled="isSubmitting"
        @click="toggleReaction('thumbs_up')"
      >
        <ThumbsUp :class="cn('size-4', hasThumbsUp && 'fill-current')" />
        <span class="text-sm font-medium">{{ thumbsUpCount }}</span>
      </Button>

      <Button
        variant="outline"
        size="sm"
        :class="cn(
          'gap-2',
          hasThumbsDown && 'border-red-500 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400'
        )"
        :disabled="isSubmitting"
        @click="toggleReaction('thumbs_down')"
      >
        <ThumbsDown :class="cn('size-4', hasThumbsDown && 'fill-current')" />
        <span class="text-sm font-medium">{{ thumbsDownCount }}</span>
      </Button>
    </template>

    <!-- Emoji variant (for FarmerOffering) -->
    <template v-else-if="variant === 'emoji'">
      <Button
        v-for="emoji in emojiReactions"
        :key="emoji"
        variant="outline"
        size="sm"
        :class="cn(
          'gap-1.5 px-3',
          hasEmoji(emoji) && 'border-primary bg-primary/10 text-primary'
        )"
        :disabled="isSubmitting"
        @click="toggleReaction(emoji)"
      >
        <span class="text-base">{{ emoji }}</span>
        <span v-if="getEmojiCount(emoji) > 0" class="text-sm font-medium">
          {{ getEmojiCount(emoji) }}
        </span>
      </Button>
    </template>
  </div>
</template>
