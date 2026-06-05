<script lang="ts" setup>
type ConfirmDialogProps = {
  title?: string,
  description?: string,
}

defineProps<ConfirmDialogProps>()

const emits = defineEmits<{
  close: [value: boolean],
}>()
</script>

<template>
  <UModal
    :ui="{ footer: 'justify-end gap-2' }"
  >
    <template #header>
      <div class="flex items-start gap-4">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-error/10">
          <UIcon name="i-lucide-triangle-alert" class="size-5 text-error" />
        </div>
        <div class="min-w-0 flex-1">
          <p v-if="title" class="text-sm font-semibold">{{ title }}</p>
          <p v-if="description" class="mt-1 text-sm text-muted">{{ description }}</p>
        </div>
      </div>
    </template>
    <template #footer>
      <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', false)" />
      <UButton label="Confirm" color="error" @click="emits('close', true)" />
    </template>
  </UModal>
</template>
