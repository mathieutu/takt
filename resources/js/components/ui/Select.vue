<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDown, Check } from 'lucide-vue-next'

export type SelectOption = {
    value:     string | number
    label:     string
    group?:    string
    disabled?: boolean
}

const props = withDefaults(defineProps<{
    modelValue?: string | number | null
    options:     SelectOption[]
    placeholder?: string
    name?:        string
    disabled?:    boolean
    error?:       boolean
}>(), {
    modelValue:  null,
    placeholder: 'Sélectionner…',
    disabled:    false,
    error:       false,
})

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null]
    'change':            [value: string | number | null]
}>()

const open         = ref(false)
const containerRef = ref<HTMLElement | null>(null)

const selectedOption = computed(() =>
    props.options.find(o => o.value === props.modelValue) ?? null
)

const groupedOptions = computed(() => {
    const ungrouped: SelectOption[] = []
    const groupMap  = new Map<string, SelectOption[]>()

    for (const opt of props.options) {
        if (opt.group) {
            if (!groupMap.has(opt.group)) groupMap.set(opt.group, [])
            groupMap.get(opt.group)!.push(opt)
        } else {
            ungrouped.push(opt)
        }
    }

    const groups: { label: string | null; options: SelectOption[] }[] = []
    if (ungrouped.length) groups.push({ label: null, options: ungrouped })
    for (const [label, options] of groupMap) groups.push({ label, options })
    return groups
})

function select(opt: SelectOption) {
    if (opt.disabled) return
    emit('update:modelValue', opt.value)
    emit('change', opt.value)
    open.value = false
}

function handleOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node))
        open.value = false
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') open.value = false
}

onMounted(() => {
    document.addEventListener('mousedown', handleOutside)
    document.addEventListener('keydown', onKey)
})
onUnmounted(() => {
    document.removeEventListener('mousedown', handleOutside)
    document.removeEventListener('keydown', onKey)
})
</script>

<template>
    <div ref="containerRef" class="relative">
        <input v-if="name" type="hidden" :name="name" :value="modelValue ?? ''" />

        <button
            type="button"
            :disabled="disabled"
            class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 text-sm text-foreground transition-colors hover:bg-accent/40 focus:outline-none disabled:opacity-50"
            :class="{ 'border-destructive': error, 'ring-1 ring-ring': open }"
            @click="open = !open"
        >
            <span :class="selectedOption ? 'text-foreground' : 'text-muted-foreground'">
                {{ selectedOption?.label ?? placeholder }}
            </span>
            <ChevronDown
                class="h-4 w-4 text-muted-foreground shrink-0 ml-2 transition-transform"
                :class="open ? 'rotate-180' : ''"
            />
        </button>

        <Transition
            enter-active-class="transition-all duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-100"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute top-full left-0 z-20 mt-1 w-full min-w-full rounded-md border border-border shadow-lg overflow-hidden"
                style="background-color: var(--background)"
            >
                <div class="max-h-60 overflow-y-auto p-1">
                    <template v-for="group in groupedOptions" :key="group.label ?? '__ungrouped'">
                        <p v-if="group.label" class="px-2.5 pb-1 pt-2 text-xs font-medium text-muted-foreground">
                            {{ group.label }}
                        </p>
                        <button
                            v-for="opt in group.options"
                            :key="opt.value"
                            type="button"
                            :disabled="opt.disabled"
                            class="flex w-full items-center justify-between rounded px-2.5 py-2 text-sm transition-colors text-left"
                            :class="[
                                opt.value === modelValue
                                    ? 'bg-primary/10 text-primary font-medium'
                                    : 'text-foreground hover:bg-accent/60',
                                opt.disabled ? 'opacity-40' : '',
                            ]"
                            @click="select(opt)"
                        >
                            <span>{{ opt.label }}</span>
                            <Check v-if="opt.value === modelValue" class="h-3.5 w-3.5 shrink-0 text-primary" />
                        </button>
                    </template>

                    <p v-if="options.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                        Aucune option disponible
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>
