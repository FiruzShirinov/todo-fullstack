<script setup lang="ts">
import { nextTick, onMounted, reactive, ref } from 'vue'
import type { Task, TaskInput, TaskStatus } from '~/types'
import { TASK_STATUSES } from '~/utils/validation'
import { validateTaskForm, isFormValid } from '~/utils/validation'
import { statusLabel } from '~/utils/format'

const props = defineProps<{
  task?: Task | null
  submitting?: boolean
  serverErrors?: Record<string, string[]>
}>()

const emit = defineEmits<{
  submit: [values: TaskInput]
  cancel: []
}>()

const form = reactive({
  title: props.task?.title ?? '',
  description: props.task?.description ?? '',
  due_date: props.task?.due_date ?? '',
  status: (props.task?.status ?? 'pending') as TaskStatus,
})

const errors = ref<Record<string, string | undefined>>({})

// Title is a textarea so long titles wrap and stay fully visible; it grows
// with content instead of scrolling horizontally.
const titleRef = ref<HTMLTextAreaElement | null>(null)

function autoGrowTitle() {
  const el = titleRef.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

function onTitleInput() {
  // Keep it a single logical line — strip any pasted newlines.
  if (form.title.includes('\n')) {
    form.title = form.title.replace(/\n/g, ' ')
  }
  autoGrowTitle()
}

onMounted(() => nextTick(autoGrowTitle))

function fieldError(field: string): string | undefined {
  return errors.value[field] || props.serverErrors?.[field]?.[0]
}

function onSubmit() {
  const validation = validateTaskForm({
    title: form.title,
    description: form.description,
    due_date: form.due_date,
    status: form.status,
  })
  errors.value = validation

  if (!isFormValid(validation)) return

  emit('submit', {
    title: form.title.trim(),
    description: form.description,
    due_date: form.due_date,
    status: form.status,
  })
}
</script>

<template>
  <form
    novalidate
    @submit.prevent="onSubmit"
  >
    <div class="field">
      <label
        class="field__label"
        for="task-title"
      >Заголовок</label>
      <textarea
        id="task-title"
        ref="titleRef"
        v-model="form.title"
        rows="1"
        class="field__input field__title-input"
        :class="{ 'field__input--error': fieldError('title') }"
        placeholder="Что нужно сделать?"
        maxlength="255"
        @input="onTitleInput"
        @keydown.enter.prevent="onSubmit"
      />
      <span
        v-if="fieldError('title')"
        class="field__error"
        data-test="error-title"
      >
        {{ fieldError('title') }}
      </span>
    </div>

    <div class="field">
      <label
        class="field__label"
        for="task-description"
      >Описание</label>
      <textarea
        id="task-description"
        v-model="form.description"
        class="field__textarea"
        placeholder="Необязательное описание"
      />
    </div>

    <div class="field">
      <label
        class="field__label"
        for="task-due"
      >Дедлайн</label>
      <input
        id="task-due"
        v-model="form.due_date"
        type="date"
        class="field__input"
        :class="{ 'field__input--error': fieldError('due_date') }"
      >
      <span
        v-if="fieldError('due_date')"
        class="field__error"
        data-test="error-due_date"
      >
        {{ fieldError('due_date') }}
      </span>
    </div>

    <div class="field">
      <label
        class="field__label"
        for="task-status"
      >Статус</label>
      <select
        id="task-status"
        v-model="form.status"
        class="field__select"
        :class="{ 'field__select--error': fieldError('status') }"
      >
        <option
          v-for="s in TASK_STATUSES"
          :key="s"
          :value="s"
        >
          {{ statusLabel(s) }}
        </option>
      </select>
      <span
        v-if="fieldError('status')"
        class="field__error"
      >{{ fieldError('status') }}</span>
    </div>

    <div
      class="modal__footer"
      style="padding-left: 0; padding-right: 0; border-top: none"
    >
      <button
        type="button"
        class="btn btn--ghost"
        :disabled="props.submitting"
        @click="emit('cancel')"
      >
        Отмена
      </button>
      <button
        type="submit"
        class="btn btn--primary"
        :disabled="props.submitting"
      >
        <span
          v-if="props.submitting"
          class="spinner"
          style="width: 14px; height: 14px"
        />
        {{ props.task ? 'Сохранить' : 'Создать задачу' }}
      </button>
    </div>
  </form>
</template>
