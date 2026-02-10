<script setup lang="ts">
import { ref } from 'vue'
import { Flag } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { toast } from 'vue-sonner'
import axios from 'axios'

interface Props {
  flaggableType: 'DealerRequest' | 'FarmerOffering' | 'AnnouncementComment'
  flaggableId: number
}

const props = defineProps<Props>()

const isOpen = ref(false)
const isSubmitting = ref(false)
const reason = ref('')
const description = ref('')

const reasonOptions = [
  { value: 'spam', label: 'Spam or misleading' },
  { value: 'inappropriate', label: 'Inappropriate content' },
  { value: 'scam', label: 'Potential scam' },
  { value: 'harassment', label: 'Harassment or hate speech' },
  { value: 'other', label: 'Other' },
]

async function submitFlag() {
  if (!reason.value || isSubmitting.value) return

  isSubmitting.value = true

  try {
    const { data } = await axios.post('/flags', {
      flaggable_type: props.flaggableType,
      flaggable_id: props.flaggableId,
      reason: reason.value,
      description: description.value || undefined,
    })

    toast.success('Content flagged for review. Thank you for helping keep our community safe.')
    isOpen.value = false
    resetForm()
  } catch (error: any) {
    const message = error.response?.data?.message || 'Failed to submit report. Please try again.'
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

function resetForm() {
  reason.value = ''
  description.value = ''
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="ghost" size="sm" class="gap-2">
        <Flag class="size-4" />
        Report
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>Report Content</DialogTitle>
        <DialogDescription>
          Help us maintain a safe and respectful community. Your report will be reviewed by our moderation team.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <Label for="reason">Reason for reporting</Label>
          <Select v-model="reason">
            <SelectTrigger id="reason">
              <SelectValue placeholder="Select a reason" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in reasonOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="description">Additional details (optional)</Label>
          <Textarea
            id="description"
            v-model="description"
            placeholder="Provide any additional context..."
            rows="4"
            maxlength="1000"
          />
          <p class="text-xs text-muted-foreground">
            {{ description.length }}/1000
          </p>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="isSubmitting" @click="isOpen = false">
          Cancel
        </Button>
        <Button
          variant="destructive"
          :disabled="!reason || isSubmitting"
          @click="submitFlag"
        >
          {{ isSubmitting ? 'Submitting...' : 'Submit Report' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
