<script setup lang="ts">
import type { TaskFilters } from '~/types'
import { TASK_STATUSES } from '~/utils/validation'
import { statusLabel } from '~/utils/format'

/**
 * Controlled filter bar. Emits granular events so the page owns the state
 * and keeps it in sync with the URL query.
 */
defineProps<{
  search: string
  status: TaskFilters['status']
  sort: TaskFilters['sort']
  direction: TaskFilters['direction']
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:status': [value: TaskFilters['status']]
  'update:sort': [value: TaskFilters['sort']]
  'update:direction': [value: TaskFilters['direction']]
  clear: []
}>()
</script>

<template>
  <div class="toolbar card">
    <div class="toolbar__search">
      <span
        class="toolbar__search-icon"
        aria-hidden="true"
      >🔍</span>
      <input
        :value="search"
        type="search"
        class="field__input toolbar__search-input"
        placeholder="Поиск задач…"
        aria-label="Поиск задач"
        @input="emit('update:search', ($event.target as HTMLInputElement).value)"
      >
      <button
        v-if="search"
        type="button"
        class="toolbar__search-clear"
        aria-label="Очистить поиск"
        @click="emit('clear')"
      >
        &times;
      </button>
    </div>

    <div class="toolbar__filters">
      <label class="toolbar__control">
        <span class="sr-only">Фильтр по статусу</span>
        <select
          class="field__select"
          :value="status"
          aria-label="Фильтр по статусу"
          @change="emit('update:status', ($event.target as HTMLSelectElement).value as TaskFilters['status'])"
        >
          <option value="">Все статусы</option>
          <option
            v-for="s in TASK_STATUSES"
            :key="s"
            :value="s"
          >{{ statusLabel(s) }}</option>
        </select>
      </label>

      <label class="toolbar__control">
        <span class="sr-only">Сортировка</span>
        <select
          class="field__select"
          :value="sort"
          aria-label="Сортировка"
          @change="emit('update:sort', ($event.target as HTMLSelectElement).value as TaskFilters['sort'])"
        >
          <option value="due_date">По дедлайну</option>
          <option value="status">По статусу</option>
          <option value="title">По заголовку</option>
          <option value="created_at">По дате создания</option>
        </select>
      </label>

      <button
        type="button"
        class="btn btn--ghost"
        :aria-label="`Сортировать ${direction === 'asc' ? 'по возрастанию' : 'по убыванию'}`"
        @click="emit('update:direction', direction === 'asc' ? 'desc' : 'asc')"
      >
        {{ direction === 'asc' ? '↑ По возр.' : '↓ По убыв.' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 0.75rem;
  padding: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 1.25rem;
}

.toolbar__search {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.toolbar__search-input {
  padding-left: 2.1rem;
  padding-right: 2.1rem;
}

.toolbar__search-icon {
  position: absolute;
  left: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.85rem;
  opacity: 0.6;
}

.toolbar__search-clear {
  position: absolute;
  right: 0.4rem;
  top: 50%;
  transform: translateY(-50%);
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: none;
  border-radius: 50%;
  font-size: 1.1rem;
  line-height: 1;
  color: var(--muted);
  cursor: pointer;
}

.toolbar__search-clear:hover {
  background: var(--bg);
  color: var(--text);
}

.toolbar__filters {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

@media (max-width: 560px) {
  .toolbar__filters {
    width: 100%;
  }
  .toolbar__control {
    flex: 1;
  }
  .toolbar__control .field__select {
    width: 100%;
  }
}
</style>
