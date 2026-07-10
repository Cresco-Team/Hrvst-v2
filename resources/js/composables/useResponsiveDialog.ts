import { useMediaQuery } from '@vueuse/core'
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer'

export function useResponsiveDialog() {
    const isDesktop = useMediaQuery('(min-width: 768px)')

    const Modal = computed(() => ({
        Root: isDesktop.value ? Dialog : Drawer,
        Content: isDesktop.value ? DialogContent : DrawerContent,
        Header: isDesktop.value ? DialogHeader : DrawerHeader,
        Title: isDesktop.value ? DialogTitle : DrawerTitle,
        Description: isDesktop.value ? DialogDescription : DrawerDescription,
        Footer: isDesktop.value ? DialogFooter : DrawerFooter,
    }))

    return { isDesktop, Modal }
}
