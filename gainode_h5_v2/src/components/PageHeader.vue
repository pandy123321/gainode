<script setup lang="ts">
defineProps<{ title: string }>()
const emit = defineEmits<{ back: [] }>()
</script>

<template>
  <header class="page-header">
    <button class="back-btn" @click="emit('back')">
      <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <h1>{{ title }}</h1>
    <div class="header-right"><slot name="right" /></div>
  </header>
</template>

<style scoped lang="scss">
.page-header {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #0E1620;

  .back-btn {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
  }

  h1 {
    flex: 1;
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin: 0 12px;
  }

  .header-right {
    display: flex;
    align-items: center;
  }
}
</style>
