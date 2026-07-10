<script setup lang="ts">
import { computed } from 'vue'
import type { TaskStatus } from '~/types'
import { statusLabel } from '~/utils/format'

const props = defineProps<{ status: TaskStatus }>()

const label = computed(() => statusLabel(props.status))
</script>

<template>
  <span
    class="badge"
    :class="`badge--${props.status}`"
  >{{ label }}</span>
</template>
