<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useRobotStore } from '../../../stores/robot'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import BottomNav from '../../../components/BottomNav.vue'

const router = useRouter()
const robot = useRobotStore()

const statusLabel = computed(() => t(`robot.status.${robot.summary?.status ?? 'inactive'}`))

// 仅当拿到了 robot_id 才拉取详情（含 allowed_actions / source_status）
watch(
  () => robot.summary?.robot_id,
  (id) => {
    if (id) robot.fetchDetail(id)
  },
  { immediate: true },
)

function reload() {
  robot.fetch()
}

function go(path: string) {
  router.push(path)
}

onMounted(() => {
  if (!robot.loaded) reload()
})
</script>

<template>
  <main class="robot-root">
    <header class="page-header">
      <h1>{{ t('page.m_robot_001.title') }}</h1>
    </header>

    <FiveStateContainer
      :state="
        robot.loading
          ? 'loading'
          : robot.error
            ? 'error'
            : robot.sourceUnavailable
              ? 'restricted'
              : 'default'
      "
      :error-message="robot.error || ''"
      :restricted-message="t('page.m_robot_001.restricted')"
      @retry="reload"
    >
      <template v-if="!robot.hasRobot">
        <section class="card" data-testid="robot-empty">
          <p class="empty">{{ t('page.m_robot_001.empty') }}</p>
        </section>
      </template>

      <template v-else>
        <!-- 状态 Hero -->
        <section class="hero" :data-status="robot.summary?.status" data-testid="robot-hero">
          <p class="hero-kicker">{{ t('page.m_robot_001.description') }}</p>
          <h2 class="hero-status">{{ statusLabel }}</h2>
          <div class="hero-meta">
            <span class="meta-item">
              {{ t('page.m_robot_001.level') }}
              <strong>Lv.{{ robot.summary?.level ?? '-' }}</strong>
            </span>
            <span class="meta-item">
              {{ t('page.m_robot_001.capacity') }}
              <strong>{{ robot.summary?.standard_capacity ?? '-' }}</strong>
            </span>
          </div>
        </section>

        <!-- 能力 / allowed_actions（只读展示，写操作 fail-closed） -->
        <section class="card" data-testid="robot-capability">
          <h3 class="card-title">{{ t('page.m_robot_001.capability_title') }}</h3>
          <FiveStateContainer
            :state="robot.detailLoading ? 'loading' : robot.detailError ? 'error' : 'default'"
            :error-message="robot.detailError || ''"
            @retry="robot.summary && robot.fetchDetail(robot.summary.robot_id)"
          >
            <div v-if="robot.detail?.capabilities?.length" class="chip-row">
              <span v-for="c in robot.detail.capabilities" :key="c" class="chip">{{ c }}</span>
            </div>
            <p v-else class="empty">{{ t('page.m_robot_001.capability_empty') }}</p>
          </FiveStateContainer>
        </section>

        <!-- 导航入口（写操作入口本身可访问，但提交态 fail-closed，见各子页） -->
        <nav class="entry-list" aria-label="Robot 功能">
          <button class="entry" data-testid="entry-start" @click="go('/robot/start')">
            <span class="entry-name">{{ t('page.m_robot_001.entry_start') }}</span>
          </button>
          <button class="entry" data-testid="entry-upgrade" @click="go('/robot/upgrade')">
            <span class="entry-name">{{ t('page.m_robot_001.entry_upgrade') }}</span>
          </button>
          <button class="entry" data-testid="entry-levels" @click="go('/robot/levels')">
            <span class="entry-name">{{ t('page.m_robot_001.entry_levels') }}</span>
          </button>
          <button class="entry" data-testid="entry-rewards" @click="go('/robot/rewards')">
            <span class="entry-name">{{ t('page.m_robot_001.entry_rewards') }}</span>
          </button>
          <button class="entry" data-testid="entry-activity" @click="go('/robot/activity')">
            <span class="entry-name">{{ t('page.m_robot_001.entry_activity') }}</span>
          </button>
        </nav>
      </template>
    </FiveStateContainer>

    <BottomNav active="robot" />
  </main>
</template>

<style scoped>
.robot-root {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
  padding-bottom: 72px;
}
.page-header {
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.hero {
  padding: var(--space-6) var(--space-4);
  background: linear-gradient(180deg, var(--brand-blue-50), var(--white));
}
.hero-kicker {
  margin: 0 0 var(--space-2);
  color: var(--gray-500);
  font-size: 13px;
}
.hero-status {
  margin: 0 0 var(--space-4);
  font-size: 24px;
  font-weight: 800;
}
.hero-meta {
  display: flex;
  gap: var(--space-6);
}
.meta-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  color: var(--gray-500);
  font-size: 13px;
}
.meta-item strong {
  color: var(--gray-900);
  font-size: 18px;
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-title {
  margin: 0 0 var(--space-3);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}
.chip {
  padding: var(--space-1) var(--space-3);
  background: var(--brand-blue-50);
  color: var(--brand-blue-700);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.entry-list {
  margin: var(--space-3) var(--space-4) 0;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.entry {
  width: 100%;
  min-height: 48px;
  padding: 0 var(--space-4);
  border: none;
  border-bottom: 1px solid var(--gray-100);
  background: transparent;
  text-align: left;
  font-size: 15px;
  font-weight: 600;
  color: var(--gray-800);
  cursor: pointer;
}
.entry:last-child {
  border-bottom: none;
}
</style>
