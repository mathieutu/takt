<script setup lang="ts">
import { computed, ref } from 'vue'
import {
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

const activeTooltip = ref<string | null>(null)
let tooltipTimer: ReturnType<typeof setTimeout> | null = null

function showTooltip(id: string) {
    tooltipTimer = setTimeout(() => { activeTooltip.value = id }, 200)
}

function hideTooltip() {
    if (tooltipTimer) clearTimeout(tooltipTimer)
    activeTooltip.value = null
}

function logout() {
    window.location.href = '/me/logout'
}
</script>

<template>
    <aside class="flex h-full w-14 flex-col items-center border-r border-border bg-sidebar py-3">

        <nav class="flex flex-1 flex-col items-center gap-1">
            <div
                v-for="item in navItems"
                :key="item.route"
                class="relative"
                @mouseenter="showTooltip(item.route)"
                @mouseleave="hideTooltip"
            >
                <a
                    :href="item.route"
                    class="flex h-9 w-9 items-center justify-center rounded-md transition-colors"
                    :class="isActive(item)
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                </a>
                <Transition name="tooltip">
                    <div
                        v-if="activeTooltip === item.route"
                        class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded px-2 py-1 text-xs bg-foreground text-background"
                    >
                        {{ item.label }}
                    </div>
                </Transition>
            </div>
        </nav>

        <div class="mt-auto flex flex-col items-center gap-1">
            <div class="relative" @mouseenter="showTooltip('settings')" @mouseleave="hideTooltip">
                <a
                    href="/dashboard/settings"
                    class="flex h-9 w-9 items-center justify-center rounded-md transition-colors"
                    :class="path.startsWith('/dashboard/settings')
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                >
                    <Settings class="h-4 w-4" />
                </a>
                <Transition name="tooltip">
                    <div
                        v-if="activeTooltip === 'settings'"
                        class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded px-2 py-1 text-xs bg-foreground text-background"
                    >
                        Paramètres
                    </div>
                </Transition>
            </div>

            <div class="relative" @mouseenter="showTooltip('logout')" @mouseleave="hideTooltip">
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-md cursor-pointer transition-colors text-muted-foreground hover:bg-red-50 hover:text-destructive"
                    @click="logout"
                >
                    <LogOut class="h-4 w-4" />
                </button>
                <Transition name="tooltip">
                    <div
                        v-if="activeTooltip === 'logout'"
                        class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded px-2 py-1 text-xs bg-foreground text-background"
                    >
                        Se déconnecter
                    </div>
                </Transition>
            </div>
        </div>
    </aside>

</template>

<style scoped>
.tooltip-enter-active,
.tooltip-leave-active {
    transition: opacity 0.1s ease;
}
.tooltip-enter-from,
.tooltip-leave-to {
    opacity: 0;
}
</style>
