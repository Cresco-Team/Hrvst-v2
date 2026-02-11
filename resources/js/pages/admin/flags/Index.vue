<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Flag, CheckCircle, XCircle, Trash2, AlertTriangle } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import admin from '@/routes/admin'
import LargeCard from '@/components/shared/cards/LargeCard.vue'

interface FlagSummary {
  pending: number
  reviewed: number
  dismissed: number
  total: number
}

interface PendingFlag {
  id: number
  flagger: {
    id: number
    name: string
  }
  flaggable_type: string
  flaggable_id: number
  flaggable_preview: string
  reason: string
  description: string | null
  status: string
  created_at: string
  created_at_human: string
}

interface PaginatedFlags {
  data: PendingFlag[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Props {
  summary: FlagSummary
  flags?: PaginatedFlags
}

const props = defineProps<Props>()

const isLoadingFlags = () => !props.flags

function handleReview(flagId: number) {
  router.post(`/admin/flags/${flagId}/review`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled via flash message
    },
  })
}

function handleDismiss(flagId: number) {
  router.post(`/admin/flags/${flagId}/dismiss`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled via flash message
    },
  })
}

function handleDeleteContent(flagId: number) {
  router.delete(`/admin/flags/${flagId}/content`, {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled via flash message
    },
  })
}

function getTypeBadgeVariant(type: string) {
  if (type === 'DealerRequest') return 'default'
  if (type === 'FarmerOffering') return 'secondary'
  return 'outline'
}

const breadcrumbs = [
  { title: 'Admin', href: admin.dashboard().url },
  { title: 'Flags', href: admin.flags.index().url },
]
</script>

<template>
  <Head title="Content Moderation" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <Heading
        title="Content Moderation"
        description="Review and manage flagged content from the community."
      >
        <template #icon>
          <Flag class="size-8" />
        </template>
      </Heading>

      <!-- Summary cards -->
      <div class="grid gap-4 lg:gap-2 md:grid-cols-4">
        <LargeCard 
          title="Pending"
          :value="summary.pending"
          subtext="awaiting review"
          :icon="AlertTriangle"
          icon-color="text-orange-600"
          card-class="col-span-1 bg-linear-to-br from-pink-500/0 via-rose-500/10 to-red-500/30"
        />
        <LargeCard 
          title="Reviewed"
          :value="summary.reviewed"
          subtext="action taken"
          :icon="CheckCircle"
          card-class="col-span-1 bg-linear-to-br from-pink-500/0 via-rose-500/10 to-red-500/30"
        />
        <LargeCard 
          title="Dismissed"
          :value="summary.dismissed"
          subtext="no action needed"
          :icon="XCircle"
          card-class="col-span-1 bg-linear-to-br from-pink-500/0 via-rose-500/10 to-red-500/30"
        />
        <LargeCard 
          title="Total"
          :value="summary.total"
          subtext="all time"
          :icon="Flag"
          card-class="col-span-1 bg-linear-to-br from-pink-500/0 via-rose-500/10 to-red-500/30"
        />
      </div>

      <!-- Pending flags table -->
      <Card>
        <CardHeader>
          <CardTitle>Pending Flags ({{ summary.pending }})</CardTitle>
        </CardHeader>
        <CardContent>
          <!-- Loading state -->
          <div v-if="isLoadingFlags()" class="space-y-3">
            <Skeleton v-for="i in 5" :key="i" class="h-16 w-full" />
          </div>

          <!-- Table -->
          <Table v-else-if="flags && flags.data.length > 0">
            <TableHeader>
              <TableRow>
                <TableHead>Reported By</TableHead>
                <TableHead>Content Type</TableHead>
                <TableHead>Content Preview</TableHead>
                <TableHead>Reason</TableHead>
                <TableHead>Reported</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="flag in flags.data" :key="flag.id">
                <TableCell class="font-medium">{{ flag.flagger.name }}</TableCell>
                <TableCell>
                  <Badge :variant="getTypeBadgeVariant(flag.flaggable_type)">
                    {{ flag.flaggable_type }}
                  </Badge>
                </TableCell>
                <TableCell class="max-w-md">
                  <p class="truncate text-sm">{{ flag.flaggable_preview }}</p>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{{ flag.reason }}</Badge>
                  <p v-if="flag.description" class="mt-1 max-w-xs truncate text-xs text-muted-foreground">
                    {{ flag.description }}
                  </p>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">
                  {{ flag.created_at_human }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Review button -->
                    <Button
                      variant="outline"
                      size="sm"
                      @click="handleReview(flag.id)"
                    >
                      <CheckCircle class="mr-1 size-4" />
                      Review
                    </Button>

                    <!-- Dismiss button -->
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="handleDismiss(flag.id)"
                    >
                      <XCircle class="mr-1 size-4" />
                      Dismiss
                    </Button>

                    <!-- Delete content (with confirmation) -->
                    <AlertDialog>
                      <AlertDialogTrigger as-child>
                        <Button variant="destructive" size="sm">
                          <Trash2 class="size-4" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Delete Flagged Content?</AlertDialogTitle>
                          <AlertDialogDescription>
                            This will permanently delete the flagged content. This action cannot be undone.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Cancel</AlertDialogCancel>
                          <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            @click="handleDeleteContent(flag.id)"
                          >
                            Delete Content
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Empty state -->
          <div v-else class="flex flex-col items-center justify-center py-16 text-center">
            <CheckCircle class="mb-4 size-12 text-green-600" />
            <h3 class="mb-1 font-semibold">All clear!</h3>
            <p class="text-sm text-muted-foreground">
              No pending flags to review
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
