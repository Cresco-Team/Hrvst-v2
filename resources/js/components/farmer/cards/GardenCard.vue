<script setup lang="ts">
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Offering } from '@/pages/farmer/garden/Index.vue';
import { Archive, CalendarClock, MoreVertical, Pencil, PhilippinePeso, Trash, Weight } from 'lucide-vue-next';

const props = defineProps<{
    offering: Offering
}>()
</script>

<template>
    <Card class="group py-0 gap-2 overflow-hidden transition-all hover:shadow-lg ">
        <AspectRatio :ratio="16/9" class="relative overflow-hidden">
            <img 
                :src="offering.image_url" 
                :alt="offering.variety.name.charAt(0)" 
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                {{ offering.created_at_human }}
            </div>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle>
                {{ offering.variety.name }}
            </CardTitle>
            <CardDescription class="flex justify-between">
                <p>{{ offering.variety.vegetable }}</p>
                <Badge>
                    {{ offering.weight_kg }} kg
                </Badge>
            </CardDescription>
            <Separator />
        </CardHeader>

        <CardContent class="p-5 pt-2 grid gap-2">
            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <PhilippinePeso :size="15" />
                    Price:
                </div>
                <span>{{ offering.asking_price.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <Weight :size="15" />
                    Kg:
                </div>
                <span>{{ offering.weight_kg }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <CalendarClock :size="15" />
                    Expiry:
                </div>
                <p>
                    {{ offering.expiration_date }}
                    <span class="text-xs text-muted-foreground">
                        ({{ offering.days_until_expiration }} days)
                    </span>
                </p>
            </div>
        </CardContent>

        <CardFooter class="flex gap-3 p-5 pt-0">
            <Button variant="outline" class="flex-1 gap-2">
                <Pencil />
                Edit
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon-sm">
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        @click="$emit('open-edit', offering)"
                    >
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>
                    
                    <DropdownMenuItem
                        @click="$emit('open-archive', offering)"
                        class="text-orange-600 dark:text-orange-400"
                    >
                        <Archive class="mr-2 size-4" />
                        Archive
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />
                    
                    <DropdownMenuItem
                        @click="$emit('open-delete', offering)"
                        class="text-destructive"
                    >
                        <Trash class="mr-2 size-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </CardFooter>
    </Card>
</template>