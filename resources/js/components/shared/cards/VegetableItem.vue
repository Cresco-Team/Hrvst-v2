<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

import { show } from '@/routes/vegetables'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { VegetableResource } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowRight, Vegan } from '@lucide/vue';


interface Props {
    vegetable: VegetableResource
}

defineProps<Props>()
</script>

<template>
    <Link :href="show({ vegetable: vegetable.id }).url" class="group">
        <Item
            variant="outline"
            class="bg-primary/10 h-full transition-all hover:bg-primary/20 hover:shadow-sm"
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

                <ArrowRight
                    class="mr-5 hidden size-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-3 group-hover:text-foreground sm:flex"
                />
            </ItemContent>
        </Item>
    </Link>
</template>
