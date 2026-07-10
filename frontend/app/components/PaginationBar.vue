<script setup lang="ts">
import type { PaginationMeta } from '~/types'

const props = defineProps<{ meta: PaginationMeta }>()
const emit = defineEmits<{ change: [page: number] }>()

function go(page: number) {
  if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) return
  emit('change', page)
}
</script>

<template>
  <nav
    v-if="meta.last_page > 1"
    class="pagination"
    aria-label="Пагинация"
  >
    <button
      type="button"
      class="btn btn--ghost btn--sm"
      :disabled="meta.current_page <= 1"
      @click="go(meta.current_page - 1)"
    >
      ← Назад
    </button>

    <span class="pagination__info">
      Страница {{ meta.current_page }} из {{ meta.last_page }}
      <span class="pagination__total">(всего {{ meta.total }})</span>
    </span>

    <button
      type="button"
      class="btn btn--ghost btn--sm"
      :disabled="meta.current_page >= meta.last_page"
      @click="go(meta.current_page + 1)"
    >
      Вперёд →
    </button>
  </nav>
</template>

<style scoped>
.pagination {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 0.75rem 0 0.25rem;
  /* Sits in the fixed bottom band (above the footer) — the list scrolls
     above it, so it never jumps when a shorter last page loads. */
  border-top: 1px solid var(--border);
}

.pagination__info {
  font-size: 0.85rem;
  color: var(--muted);
}

.pagination__total {
  opacity: 0.8;
}
</style>
