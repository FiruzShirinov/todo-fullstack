<script setup lang="ts">
const props = defineProps<{
  title: string
  message: string
  confirmLabel?: string
  loading?: boolean
}>()

const emit = defineEmits<{ confirm: []; cancel: [] }>()
</script>

<template>
  <BaseModal
    :title="props.title"
    @close="emit('cancel')"
  >
    <p style="margin-top: 0; color: var(--muted)">
      {{ props.message }}
    </p>
    <div
      class="modal__footer"
      style="padding-left: 0; padding-right: 0; border-top: none"
    >
      <button
        type="button"
        class="btn btn--ghost"
        :disabled="props.loading"
        @click="emit('cancel')"
      >
        Отмена
      </button>
      <button
        type="button"
        class="btn btn--danger"
        :disabled="props.loading"
        @click="emit('confirm')"
      >
        {{ props.confirmLabel ?? 'Удалить' }}
      </button>
    </div>
  </BaseModal>
</template>
