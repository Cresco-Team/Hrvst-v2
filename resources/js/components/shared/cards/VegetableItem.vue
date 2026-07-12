<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

import { ArrowRight, Vegan } from '@lucide/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { show } from '@/routes/vegetables'
import type { VegetableSharedData } from '@/types';


interface Props {
    vegetable: VegetableSharedData
}

defineProps<Props>()
</script>

<template>
    <Link
        :href="show({ vegetable: vegetable.id }).url"
        class="group"
    >
        <Item
            variant="outline"
            class="transition-all hover:shadow-sm bg-primary/10 hover:bg-primary/5 hover:border-l-4 hover:border-l-primary"
        >
            <ItemMedia variant="image">
                <Avatar>
                    <AvatarImage 
                        :src="vegetable.image_url"
                        :alt="vegetable.display_name"
                    />
                    <AvatarFallback>
                        <Vegan />
                    </AvatarFallback>
                </Avatar>
            </ItemMedia>

            <ItemContent class="flex-row items-center justify-between overflow-hidden">
                <div class="flex flex-col">
                    <ItemTitle class="line-clamp-1">
                        {{ vegetable.display_name }}
                    </ItemTitle>
                    
                    <ItemDescription
                        v-if="vegetable.local_name"
                        class="text-xs"
                    >
                        {{ vegetable.local_name }}
                    </ItemDescription>
                </div>

                <ArrowRight class="mr-5 hidden size-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-3 group-hover:text-foreground sm:flex"/>
            </ItemContent>
        </Item>
    </Link>
</template>
