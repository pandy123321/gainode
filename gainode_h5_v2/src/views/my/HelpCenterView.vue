<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ProjectApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()

interface HelpItem {
  title: string
  content: string
  categoryName: string
}

const helpList = ref<HelpItem[]>([])
const loading = ref(true)
const expandedIndex = ref(-1)

const categoryColors = [
  '#00FFA3', '#00BCD4', '#9C27B0', '#FFB800', '#FF5722',
  '#03A9F4', '#FF6B9D', '#00D9FF', '#FFB800', '#8A9CB0',
]

function categoryColor(i: number) {
  return categoryColors[i % categoryColors.length]
}

onMounted(async () => {
  const res = await ProjectApi.getHelpList()
  if (res.code === 0 && res.data) {
    const data = res.data
    const list = Array.isArray(data) ? data : (data.data || [])
    helpList.value = list.map((e: any) => ({
      title: e.title || '',
      content: e.content || '',
      categoryName: e.category_name || '',
    }))
  }
  loading.value = false
})

function toggle(i: number) {
  expandedIndex.value = expandedIndex.value === i ? -1 : i
}
</script>

<template>
  <div class="help-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('help_center') }}</h1>
    </header>

    <div class="help-content">
      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!helpList.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="faq-list">
        <div
            v-for="(item, i) in helpList" :key="i" class="faq-item"
            :class="{ open: expandedIndex === i }"
            :style="expandedIndex === i ? { borderColor: categoryColor(i) + '4d' } : {}"
          >
          <div class="faq-inner" @click="toggle(i)">
            <span v-if="item.categoryName" class="faq-category" :style="{ color: categoryColor(i), background: categoryColor(i) + '26', borderColor: categoryColor(i) + '4d' }">
              {{ item.categoryName }}
            </span>
            <div class="faq-title-row">
              <span class="faq-title-text">{{ item.title }}</span>
              <svg viewBox="0 0 20 20" fill="none" width="20" height="20" class="chevron">
                <path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
            </div>
            <div v-if="expandedIndex === i" class="faq-content" v-html="item.content"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.help-screen { min-height: 100vh; background: #0A0E14; }

.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.help-content { padding: 16px; }

.loading-wrap { display: flex; justify-content: center; padding: 40px;
  .spinner { width: 32px; height: 32px; border: 3px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.empty-text { text-align: center; padding: 80px 0; color: $muted; font-size: 14px; }

.faq-list { display: grid; gap: 8px; }

.faq-item {
  background: $elevated; border-radius: 12px; border: 1px solid $border;
  overflow: hidden; transition: border-color 0.3s;

  &.open .chevron { transform: rotate(180deg); }

  .faq-inner { padding: 16px; cursor: pointer; }

  .faq-category {
    display: inline-block; padding: 4px 8px;
    border-radius: 6px; border: 1px solid transparent;
    font-size: 11px; font-weight: 600; margin-bottom: 10px;
  }

  .faq-title-row {
    display: flex; align-items: center; justify-content: space-between;
    .faq-title-text { color: white; font-size: 15px; font-weight: 600; }
    .chevron { color: $muted; transition: transform 0.2s; flex-shrink: 0; }
  }

  .faq-content {
    margin-top: 12px; padding: 12px; background: #12181F; border-radius: 8px;
    font-size: 13px; color: $muted; line-height: 1.6;
  }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
