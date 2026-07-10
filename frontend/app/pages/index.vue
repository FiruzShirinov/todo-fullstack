<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { Task, TaskFilters, TaskInput, TaskStatus } from '~/types'
import { TASK_STATUSES } from '~/utils/validation'
import { pluralRu } from '~/utils/format'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Мои задачи · Задачи' })

const PER_PAGE = 8
const SORTS = ['due_date', 'status', 'title', 'created_at'] as const

const auth = useAuthStore()
const tasksApi = useTasks()
const route = useRoute()
const router = useRouter()

// --- Filters, seeded from the URL query so links are shareable ---------------
function parseStatus(v: unknown): TaskFilters['status'] {
  return TASK_STATUSES.includes(v as TaskStatus) ? (v as TaskStatus) : ''
}
function parseSort(v: unknown): TaskFilters['sort'] {
  return SORTS.includes(v as (typeof SORTS)[number]) ? (v as TaskFilters['sort']) : 'due_date'
}

const filters = reactive<TaskFilters>({
  search: (route.query.search as string) ?? '',
  status: parseStatus(route.query.status),
  sort: parseSort(route.query.sort),
  direction: route.query.direction === 'desc' ? 'desc' : 'asc',
  page: Number(route.query.page) > 0 ? Number(route.query.page) : 1,
})

// Local search box value, debounced into filters.search below.
const searchInput = ref(filters.search)

// --- Data fetching -----------------------------------------------------------
const queryKey = computed(() =>
  [filters.search, filters.status, filters.sort, filters.direction, filters.page].join('|'),
)

const { data, pending, error, refresh } = await useAsyncData(
  'tasks',
  () =>
    tasksApi.list({
      search: filters.search,
      status: filters.status,
      sort: filters.sort,
      direction: filters.direction,
      page: filters.page,
      per_page: PER_PAGE,
    }),
  { watch: [queryKey] },
)

const tasks = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)

// --- Keep the URL in sync with the current filters ---------------------------
watch(queryKey, () => {
  const query: Record<string, string> = {}
  if (filters.search) query.search = filters.search
  if (filters.status) query.status = filters.status
  if (filters.sort !== 'due_date') query.sort = filters.sort
  if (filters.direction !== 'asc') query.direction = filters.direction
  if (filters.page > 1) query.page = String(filters.page)
  router.replace({ query })
})

// --- Debounced search --------------------------------------------------------
let debounceTimer: ReturnType<typeof setTimeout> | undefined
watch(searchInput, (value) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    if (value !== filters.search) {
      filters.search = value
      filters.page = 1
    }
  }, 350)
})

// Clearing the search bypasses the debounce for an instant response.
function onClearSearch() {
  clearTimeout(debounceTimer)
  searchInput.value = ''
  filters.search = ''
  filters.page = 1
}

// Reset to first page whenever a non-page filter changes.
function onStatus(v: TaskFilters['status']) {
  filters.status = v
  filters.page = 1
}
function onSort(v: TaskFilters['sort']) {
  filters.sort = v
  filters.page = 1
}
function onDirection(v: TaskFilters['direction']) {
  filters.direction = v
  filters.page = 1
}
function onPage(page: number) {
  filters.page = page
}

// --- Create / edit modal -----------------------------------------------------
const showForm = ref(false)
const editingTask = ref<Task | null>(null)
const submitting = ref(false)
const serverErrors = ref<Record<string, string[]>>({})
const actionError = ref('')

function openCreate() {
  editingTask.value = null
  serverErrors.value = {}
  showForm.value = true
}
function openEdit(task: Task) {
  editingTask.value = task
  serverErrors.value = {}
  showForm.value = true
}
function closeForm() {
  showForm.value = false
}

async function onSubmit(values: TaskInput) {
  submitting.value = true
  serverErrors.value = {}
  actionError.value = ''
  try {
    if (editingTask.value) {
      await tasksApi.update(editingTask.value.id, values)
    } else {
      await tasksApi.create(values)
      filters.page = 1
    }
    showForm.value = false
    await refresh()
  } catch (e: unknown) {
    const err = e as { status?: number; data?: { message?: string; errors?: Record<string, string[]> } }
    if (err?.status === 422 && err.data?.errors) {
      serverErrors.value = err.data.errors
    } else {
      actionError.value = err?.data?.message || 'Не удалось сохранить задачу. Попробуйте ещё раз.'
    }
  } finally {
    submitting.value = false
  }
}

// --- Delete confirmation -----------------------------------------------------
const deletingTask = ref<Task | null>(null)
const deleting = ref(false)

function openDelete(task: Task) {
  deletingTask.value = task
}
async function confirmDelete() {
  if (!deletingTask.value) return
  deleting.value = true
  actionError.value = ''
  try {
    await tasksApi.remove(deletingTask.value.id)
    deletingTask.value = null
    // Step back a page if we just removed the last item on it.
    if (tasks.value.length === 1 && filters.page > 1) filters.page -= 1
    else await refresh()
  } catch (e: unknown) {
    const err = e as { data?: { message?: string } }
    actionError.value = err?.data?.message || 'Не удалось удалить задачу.'
    deletingTask.value = null
  } finally {
    deleting.value = false
  }
}

const hasActiveFilters = computed(() => !!filters.search || !!filters.status)

const totalLabel = computed(() => {
  const total = meta.value?.total ?? 0
  return `${total} ${pluralRu(total, ['задача', 'задачи', 'задач'])}`
})

const errorMessage = computed(() => {
  const e = error.value as { data?: { message?: string } } | null
  return e?.data?.message || 'Проверьте подключение и попробуйте ещё раз.'
})

const scopeLabel = computed(() => {
  if (auth.isAdmin) return 'режим админа'
  if (auth.isViewer) return 'только просмотр'
  return ''
})
</script>

<template>
  <section class="tasks">
    <header class="tasks__head">
      <div>
        <h1 class="tasks__title">
          {{ auth.canViewAll ? 'Все задачи' : 'Мои задачи' }}
        </h1>
        <p class="tasks__subtitle">
          <span
            v-if="scopeLabel"
            class="badge"
            :class="auth.isAdmin ? 'badge--admin' : 'badge--viewer'"
          >{{ scopeLabel }}</span>
          <span>{{ totalLabel }}</span>
        </p>
      </div>
      <button
        type="button"
        class="btn btn--primary"
        @click="openCreate"
      >
        + Новая задача
      </button>
    </header>

    <TaskToolbar
      v-model:search="searchInput"
      :status="filters.status"
      :sort="filters.sort"
      :direction="filters.direction"
      @update:status="onStatus"
      @update:sort="onSort"
      @update:direction="onDirection"
      @clear="onClearSearch"
    />

    <div
      v-if="actionError"
      class="alert alert--error"
      role="alert"
    >
      {{ actionError }}
    </div>

    <!-- Scrollable content region — keeps head, toolbar and pagination fixed -->
    <div class="tasks__content">
      <!-- Loading -->
      <div
        v-if="pending && !data"
        class="state"
      >
        <span class="spinner" />
        <p style="margin-top: 0.75rem">
          Загрузка задач…
        </p>
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="state"
      >
        <p class="state__title">
          Не удалось загрузить задачи
        </p>
        <p>{{ errorMessage }}</p>
        <button
          type="button"
          class="btn btn--ghost"
          style="margin-top: 1rem"
          @click="() => refresh()"
        >
          Повторить
        </button>
      </div>

      <!-- Empty -->
      <div
        v-else-if="tasks.length === 0"
        class="state"
      >
        <p class="state__title">
          {{ hasActiveFilters ? 'Задачи не найдены' : 'Задач пока нет' }}
        </p>
        <p>{{ hasActiveFilters ? 'Измените поисковый запрос или фильтры.' : 'Создайте первую задачу, чтобы начать.' }}</p>
        <button
          v-if="!hasActiveFilters"
          type="button"
          class="btn btn--primary"
          style="margin-top: 1rem"
          @click="openCreate"
        >
          + Новая задача
        </button>
      </div>

      <!-- List -->
      <div
        v-else
        class="tasks__list"
        :class="{ 'tasks__list--loading': pending }"
      >
        <TaskCard
          v-for="task in tasks"
          :key="task.id"
          :task="task"
          :show-owner="auth.canViewAll"
          @edit="openEdit"
          @delete="openDelete"
        />
      </div>
    </div>

    <PaginationBar
      v-if="meta"
      :meta="meta"
      @change="onPage"
    />

    <!-- Create / edit modal -->
    <BaseModal
      v-if="showForm"
      :title="editingTask ? 'Редактировать задачу' : 'Новая задача'"
      @close="closeForm"
    >
      <TaskForm
        :task="editingTask"
        :submitting="submitting"
        :server-errors="serverErrors"
        @submit="onSubmit"
        @cancel="closeForm"
      />
    </BaseModal>

    <!-- Delete confirmation -->
    <ConfirmDialog
      v-if="deletingTask"
      title="Удалить задачу"
      :message="`Удалить задачу «${deletingTask.title}»? Это действие нельзя отменить.`"
      :loading="deleting"
      @confirm="confirmDelete"
      @cancel="deletingTask = null"
    />
  </section>
</template>

<style scoped>
/* Fill the height main gives us so only the content region scrolls. */
.tasks {
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex: 1;
}

.tasks__head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-shrink: 0;
}

.tasks__title {
  margin: 0;
  font-size: 1.6rem;
}

.tasks__subtitle {
  margin: 0.25rem 0 0;
  color: var(--muted);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* The only scrolling area on the page. */
.tasks__content {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  /* room so the last card isn't flush against the pagination bar */
  padding-bottom: 0.25rem;
}

.tasks__list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  transition: opacity 0.15s ease;
}

.tasks__list--loading {
  opacity: 0.6;
}

@media (max-width: 560px) {
  .tasks__head {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
