<script setup lang="ts">
import { ref, computed } from 'vue'
import { MessageSquare, Trash2, Send } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import type { AnnouncementComment } from '@/types/announcement'

interface Props {
  offeringId: number
  canComment?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canComment: true,
})

const comments = ref<AnnouncementComment[]>([])
const newComment = ref('')
const isLoading = ref(true)
const isSubmitting = ref(false)
const isDeleting = ref<number | null>(null)

// Load comments on mount
async function loadComments() {
  isLoading.value = true
  try {
    const response = await fetch(`/offerings/${props.offeringId}/comments`)
    if (!response.ok) throw new Error('Failed to load comments')
    const data = await response.json()
    comments.value = data.data || []
  } catch (error) {
    console.error('Error loading comments:', error)
  } finally {
    isLoading.value = false
  }
}

// Submit new comment
async function submitComment() {
  if (!newComment.value.trim() || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const response = await fetch(`/offerings/${props.offeringId}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ comment: newComment.value }),
    })

    if (!response.ok) throw new Error('Failed to post comment')

    const data = await response.json()
    comments.value.unshift(data.comment)
    newComment.value = ''
  } catch (error) {
    console.error('Error posting comment:', error)
  } finally {
    isSubmitting.value = false
  }
}

// Delete comment
async function deleteComment(commentId: number) {
  if (isDeleting.value !== null) return

  isDeleting.value = commentId
  try {
    const response = await fetch(`/comments/${commentId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (!response.ok) throw new Error('Failed to delete comment')

    comments.value = comments.value.filter(c => c.id !== commentId)
  } catch (error) {
    console.error('Error deleting comment:', error)
  } finally {
    isDeleting.value = null
  }
}

function getRoleBadgeVariant(role: string) {
  return role === 'dealer' ? 'default' : role === 'farmer' ? 'secondary' : 'outline'
}

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const commentCount = computed(() => comments.value.length)

// Auto-load on mount
loadComments()
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-2">
      <MessageSquare class="size-5 text-muted-foreground" />
      <h3 class="font-semibold">Comments</h3>
      <span class="text-sm text-muted-foreground">({{ commentCount }})</span>
    </div>

    <!-- Comment Form (if can comment) -->
    <div v-if="canComment" class="space-y-2">
      <Textarea
        v-model="newComment"
        placeholder="Write a comment..."
        rows="3"
        :disabled="isSubmitting"
        maxlength="1000"
      />
      <div class="flex items-center justify-between">
        <span class="text-xs text-muted-foreground">
          {{ newComment.length }}/1000
        </span>
        <Button
          size="sm"
          :disabled="!newComment.trim() || isSubmitting"
          @click="submitComment"
        >
          <Send class="mr-2 size-4" />
          {{ isSubmitting ? 'Posting...' : 'Post Comment' }}
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="flex gap-3">
        <Skeleton class="size-10 rounded-full" />
        <div class="flex-1 space-y-2">
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-16 w-full" />
        </div>
      </div>
    </div>

    <!-- Comments List -->
    <div v-else-if="comments.length > 0" class="space-y-4">
      <div
        v-for="comment in comments"
        :key="comment.id"
        class="flex gap-3 rounded-lg border bg-card p-4"
      >
        <Avatar class="size-10">
          <AvatarImage v-if="comment.user.avatar" :src="comment.user.avatar" />
          <AvatarFallback>{{ getInitials(comment.user.name) }}</AvatarFallback>
        </Avatar>

        <div class="flex-1 space-y-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="font-semibold">{{ comment.user.name }}</span>
              <Badge :variant="getRoleBadgeVariant(comment.user.role)" class="text-xs">
                {{ comment.user.role }}
              </Badge>
              <span class="text-xs text-muted-foreground">
                {{ comment.created_at_human }}
              </span>
            </div>

            <Button
              v-if="comment.can_delete"
              variant="ghost"
              size="sm"
              :disabled="isDeleting === comment.id"
              @click="deleteComment(comment.id)"
            >
              <Trash2 class="size-4 text-destructive" />
            </Button>
          </div>

          <p class="whitespace-pre-wrap text-sm">{{ comment.comment }}</p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg border border-dashed p-8 text-center">
      <MessageSquare class="mx-auto mb-2 size-8 text-muted-foreground/50" />
      <p class="text-sm text-muted-foreground">No comments yet. Be the first to comment!</p>
    </div>
  </div>
</template>
