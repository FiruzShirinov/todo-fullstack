<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { Task } from '~/types'
import { formatDate, isOverdue } from '~/utils/format'

const props = defineProps<{ task: Task; showOwner?: boolean }>()

const emit = defineEmits<{
  edit: [task: Task]
  delete: [task: Task]
}>()

const overdue = computed(() => isOverdue(props.task.due_date, props.task.status))

// Description is clamped to 3 lines by default; "show more" only appears
// when the text actually overflows that clamp (measured, not guessed).
const descRef = ref<HTMLElement | null>(null)
const expanded = ref(false)
const canExpand = ref(false)
let resizeObserver: ResizeObserver | undefined

function measureOverflow() {
  const el = descRef.value
  if (!el) {
    canExpand.value = false
    return
  }
  // While expanded the clamp is off, so measure against the collapsed state.
  if (expanded.value) return
  canExpand.value = el.scrollHeight > el.clientHeight + 1
}

onMounted(() => {
  nextTick(measureOverflow)
  if (typeof ResizeObserver !== 'undefined' && descRef.value) {
    resizeObserver = new ResizeObserver(() => measureOverflow())
    resizeObserver.observe(descRef.value)
  }
})
onBeforeUnmount(() => resizeObserver?.disconnect())
watch(() => props.task.description, () => nextTick(measureOverflow))

function toggleExpanded() {
  expanded.value = !expanded.value
}
</script>

<template>
  <article class="task-card card">
    <div class="task-card__main">
      <div class="task-card__header">
        <h3
          class="task-card__title"
          :title="task.title"
        >
          {{ task.title }}
        </h3>
        <StatusBadge :status="task.status" />
      </div>

      <template v-if="task.description">
        <p
          ref="descRef"
          class="task-card__desc"
          :class="{ 'task-card__desc--expanded': expanded }"
        >
          {{ task.description }}
        </p>
        <button
          v-if="canExpand"
          type="button"
          class="task-card__toggle"
          @click="toggleExpanded"
        >
          {{ expanded ? 'Свернуть' : 'Показать полностью' }}
        </button>
      </template>

      <div class="task-card__meta">
        <span
          class="task-card__due"
          :class="{ 'task-card__due--overdue': overdue }"
        >
          <span aria-hidden="true">📅</span>
          {{ formatDate(task.due_date) }}
          <span
            v-if="overdue"
            class="task-card__overdue-tag"
          >Просрочено</span>
        </span>
        <span
          v-if="showOwner && task.user"
          class="task-card__owner"
        >
          <span aria-hidden="true">👤</span> {{ task.user.name }}
        </span>
      </div>
    </div>

    <div class="task-card__actions">
      <button
        v-if="task.can.update"
        type="button"
        class="btn btn--ghost btn--sm"
        @click="emit('edit', task)"
      >
        Изменить
      </button>
      <button
        v-if="task.can.delete"
        type="button"
        class="btn btn--danger btn--sm"
        @click="emit('delete', task)"
      >
        Удалить
      </button>
    </div>
  </article>
</template>

<style scoped>
.task-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
  min-height: var(--task-card-min-h, 150px);
}

.task-card__main {
  min-width: 0;
  flex: 1;
}

.task-card__header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.4rem;
}

.task-card__title {
  width: 100%;
  max-width: 100%;
  font-size: 1rem;
  font-weight: 650;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-card__desc {
  margin: 0.4rem 0 0;
  color: var(--muted);
  font-size: 0.88rem;
  white-space: pre-wrap;
  word-break: break-word;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-card__desc--expanded {
  -webkit-line-clamp: unset;
  overflow: visible;
}

.task-card__toggle {
  margin: 0.3rem 0 0;
  padding: 0;
  border: none;
  background: none;
  color: var(--primary);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.task-card__toggle:hover {
  text-decoration: underline;
}

.task-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.6rem;
  font-size: 0.82rem;
  color: var(--muted);
}

.task-card__due--overdue {
  color: var(--danger);
  font-weight: 600;
}

.task-card__overdue-tag {
  background: var(--danger-bg);
  color: var(--danger);
  border-radius: 999px;
  padding: 0.05rem 0.45rem;
  font-size: 0.7rem;
  margin-left: 0.25rem;
}

.task-card__actions {
  display: flex;
  gap: 0.4rem;
  flex-shrink: 0;
}

@media (max-width: 560px) {
  .task-card {
    flex-direction: column;
  }
  .task-card__actions {
    width: 100%;
  }
}
</style>
