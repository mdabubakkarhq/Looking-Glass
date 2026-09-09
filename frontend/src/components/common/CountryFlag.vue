<script setup lang="ts">
const props = withDefaults(defineProps<{
  code: string
  size?: 'sm' | 'md' | 'lg'
}>(), {
  size: 'md',
})

const sizeClass: Record<string, string> = {
  sm: 'fi-sm',
  md: '',
  lg: 'fi-lg',
}

const spanSizeClass: Record<string, string> = {
  sm: 'text-xs',
  md: 'text-base',
  lg: 'text-2xl',
}

const isValid = props.code && props.code.length === 2
const lowerCode = isValid ? props.code.toLowerCase() : ''
</script>

<template>
  <span
    v-if="isValid"
    class="fi"
    :class="[`fi-${lowerCode}`, sizeClass[size]]"
    :title="code.toUpperCase()"
    role="img"
    :aria-label="code.toUpperCase() + ' flag'"
  />
  <span v-else :class="spanSizeClass[size]" role="img" aria-label="Unknown location">🌐</span>
</template>