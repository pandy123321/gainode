<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useNoticeStore } from '../../../stores/notice'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { Notice } from '../../../api/notice'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const notice = useNoticeStore()

const tab = ref<'unread' | 'all'>('unread')

const state = computed(() => {
  if (notice.loading) return 'loading'
  if (notice.error) return 'error'
  return 'default'
})

const list = computed<Notice[]>(() => {
  if (tab.value === 'unread') return notice.items.filter((n) => n.read_state === 'unread')
  return notice.items
})

function typeLabel(type: string): string {
  return t(`notice.type.${type}`)
}

function title(n: Notice): string {
  return t(n.title_key)
}

function onRow(n: Notice) {
  // 契约缺口 S03-P02-NOTICE-DEEPLINK：object_type → route 映射未冻结，先只标记已读。
  notice.markRead(n.notice_id)
}

onMounted(() => notice.fetch())
</script>

<template>
  <main class="app-page">
    <header class="app-head">
      <h1>{{ t('page.m_notice_001.title') }}</h1>
      <DataStateBadge page-id="M-NOTICE-001" />
    </header>

    <div class="tabs" role="tablist">
      <button
        class="tab"
        :class="{ active: tab === 'unread' }"
        @click="tab = 'unread'"
      >
        {{ t('page.m_notice_001.tab_unread') }}
      </button>
      <button
        class="tab"
        :class="{ active: tab === 'all' }"
        @click="tab = 'all'"
      >
        {{ t('page.m_notice_001.tab_all') }}
      </button>
    </div>

    <FiveStateContainer :state="state" :error-message="notice.error || ''" @retry="notice.fetch">
      <div v-if="!list.length" class="empty" data-testid="notice-empty">
        {{ t('page.m_notice_001.empty') }}
      </div>

      <ul v-else class="notice-list">
        <li
          v-for="n in list"
          :key="n.notice_id"
          class="notice-row"
          :class="{ unread: n.read_state === 'unread' }"
          @click="onRow(n)"
        >
          <span v-if="n.read_state === 'unread'" class="unread-dot" aria-hidden="true" />
          <div class="notice-body">
            <div class="notice-top">
              <span class="notice-type">{{ typeLabel(n.notice_type) }}</span>
              <span v-if="n.priority && n.priority !== 'INFO'" class="priority" :data-p="n.priority">
                {{ n.priority }}
              </span>
            </div>
            <div class="notice-title">{{ title(n) }}</div>
          </div>
        </li>
      </ul>
    </FiveStateContainer>
  </main>
</template>

<style scoped>
.app-page {
  max-width: 640px;
  margin: 0 auto;
  padding: var(--space-6) var(--space-4) var(--space-10);
  color: var(--gray-950);
}
.app-head h1 { margin: 0 0 var(--space-4); font-size: 24px; }
.tabs {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-4);
}
.tab {
  flex: 1;
  height: 40px;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-md);
  background: var(--white);
  color: var(--gray-600);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
.tab.active {
  background: var(--brand-blue-600);
  border-color: var(--brand-blue-600);
  color: var(--white);
}
.empty {
  padding: var(--space-8) var(--space-4);
  text-align: center;
  color: var(--gray-400);
  font-size: 14px;
}
.notice-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.notice-row {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  min-height: 64px;
  padding: var(--space-3) var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-md);
  cursor: pointer;
}
.notice-row.unread { border-color: var(--info-600); }
.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--brand-blue-600);
  flex-shrink: 0;
  margin-top: 6px;
}
.notice-body { flex: 1; }
.notice-top {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-bottom: var(--space-1);
}
.notice-type { font-size: 12px; color: var(--gray-500); }
.priority {
  font-size: 11px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 999px;
  color: var(--white);
}
.priority[data-p='WARNING'] { background: var(--warning-600); }
.priority[data-p='CRITICAL'] { background: var(--danger-600); }
.notice-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--gray-900);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
