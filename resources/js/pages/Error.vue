<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';


const props = defineProps({
    status: {
        type: Number,
        required: true
    }
})

const title = computed(() => {
  return {
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page Not Found',
  }[props.status] || 'An Error Occurred'
})

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Whoops, something went wrong on our servers.',
    404: 'Sorry, the page you are looking for could not be found.',
  }[props.status] || 'Something went wrong.'
})

const errorImage = computed(() => {
  return {
    404: '/images/errors/404.svg',
  }[props.status] || '/images/errors/500.svg'
})

</script>

<template>
    <Head :title="title" />
  <div class="flex flex-col min-h-screen items-center justify-center bg-gray-100 px-6 dark:bg-gray-900">
    <img 
        :src="errorImage" 
        :alt="`Error ${props.status} graphic`" 
        class="mx-auto mb-8 h-64 w-auto object-contain"
      />
    <div class="text-center">
      <h1 class="text-6xl font-bold text-red-500">{{ props.status }}</h1>
      <p class="mt-4 text-2xl font-semibold text-gray-800 dark:text-gray-200">
        {{ title }}
      </p>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        {{ description }}
      </p>
      <div class="mt-6">
        <Button as-child size="lg" class="font-bold cursor-pointer">
            <Link href="/dashboard">Go Back Home</Link>
        </Button>
      </div>
    </div>
  </div>
</template>