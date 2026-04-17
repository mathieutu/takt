<script setup lang="ts">
import { computed, ref } from 'vue'
import {
    Menu,
    X,
    LayoutDashboard,
    CalendarDays,
    Users,
    FolderKanban,
    Download,
    Settings,
    LogOut,
} from 'lucide-vue-next'

interface User {
    name: string
    email: string
    role: string
}

const props = defineProps<{
    user: User
    currentPath?: string
}>()

const path = computed(() => props.currentPath ?? window.location.pathname)

const greeting = computed(() => {
    const first = props.user.name.split(' ')[0]
    return first || props.user.email
})

const navItems = [
    { route: '/dashboard',          label: 'Tableau de bord', icon: LayoutDashboard, exact: true },
    { route: '/dashboard/reports',  label: 'Saisie CRA',      icon: CalendarDays },
    { route: '/dashboard/clients',  label: 'Clients',         icon: Users },
    { route: '/dashboard/projects', label: 'Projets',         icon: FolderKanban },
    { route: '/dashboard/exports',  label: 'Export',          icon: Download },
]

function isActive(item: (typeof navItems)[0]): boolean {
    if (item.exact) return path.value === item.route
    return path.value.startsWith(item.route)
}

const drawerOpen = ref(false)

function logout() {
    window.location.href = '/me/logout'
}
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 flex h-14 items-center justify-between border-b border-border bg-sidebar px-4">
        <!-- Logo -->
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-md object-cover">
                <img src="/public/images/logo.png" class="w-full h-full object-cover">
            </div>
            <span class="text-sm font-semibold text-foreground">AssoFlow</span>
        </div>

        <!-- Desktop: greeting -->
        <div class="hidden md:flex items-center gap-1 text-sm">
            <span class="text-muted-foreground">Bonjour,</span>
            <span class="font-medium text-foreground">{{ greeting }}</span>
        </div>

        <!-- Mobile: burger -->
        <button
            type="button"
            class="md:hidden flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
            @click="drawerOpen = true"
        >
            <Menu class="h-5 w-5" />
        </button>
    </header>

    <!-- Mobile drawer -->
    <Transition name="drawer">
        <div v-if="drawerOpen" class="md:hidden fixed inset-0 z-50 flex">
            <div class="absolute inset-0 bg-black/40" @click="drawerOpen = false" />

            <nav class="relative flex h-full w-64 flex-col bg-sidebar border-r border-border py-4 px-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-md">
                            <img src="/public/images/logo.png" class="w-full h-full object-cover">
                        </div>
                        <span class="text-sm font-semibold text-foreground">AssoFlow</span>
                    </div>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent transition-colors"
                        @click="drawerOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex flex-1 flex-col gap-1">
                    <a
                        v-for="item in navItems"
                        :key="item.route"
                        :href="item.route"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="isActive(item)
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        {{ item.label }}
                    </a>
                </div>

                <div class="flex flex-col gap-1 pt-4 border-t border-border">
                    <a
                        href="/dashboard/settings"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="path.startsWith('/dashboard/settings')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                    >
                        <Settings class="h-4 w-4 shrink-0" />
                        Paramètres
                    </a>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm cursor-pointer transition-colors text-muted-foreground hover:bg-red-50 hover:text-destructive"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4 shrink-0" />
                        Se déconnecter
                    </button>
                </div>
            </nav>
        </div>
    </Transition>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
    transition: opacity 0.2s ease;
}
.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
}
</style>
