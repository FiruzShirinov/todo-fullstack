<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps<{ title: string }>()
const emit = defineEmits<{ close: [] }>()

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div
    class="modal-overlay"
    @click.self="emit('close')"
  >
    <div
      class="modal"
      role="dialog"
      aria-modal="true"
      :aria-label="props.title"
    >
      <div class="modal__header">
        <h2 class="modal__title">
          {{ props.title }}
        </h2>
        <button
          type="button"
          class="modal__close"
          aria-label="Закрыть"
          @click="emit('close')"
        >
          &times;
        </button>
      </div>
      <div class="modal__body">
        <slot />
      </div>
    </div>
  </div>
</template>
