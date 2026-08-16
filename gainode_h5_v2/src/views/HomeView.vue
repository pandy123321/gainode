<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ProjectApi } from '../api/services'
import { useProjectStore, type ProjectModel } from '../stores/project'
import { useUserStore } from '../stores/user'
import { showToast } from '../utils/toast'
import { t } from '../i18n'

const projectStore = useProjectStore()
const userStore = useUserStore()

const showActivationDialog = ref(false)
const selectedProject = ref<ProjectModel | null>(null)
const activating = ref(false)

function parseRate(rate: string): number {
  const r = rate.replace('%', '').trim()
  const val = parseFloat(r) || 0
  return val > 1 ? val / 100 : val
}

function calcProfit(p: ProjectModel): string {
  const price = parseFloat(p.projectPrice) || 0
  const minRate = parseRate(p.minDayRate)
  const maxRate = parseRate(p.maxDayRate)
  const days = p.projectDay
  const minProfit = (price * minRate * days).toFixed(2)
  const maxProfit = (price * maxRate * days).toFixed(2)
  return `${t('profit_label')}${minProfit}-${maxProfit} USDT`
}

function isPresale(p: ProjectModel): boolean {
  if (!p.startDate) return false
  return new Date(p.startDate) > new Date()
}

function presaleSeconds(p: ProjectModel): number {
  if (!p.startDate) return 0
  const diff = Math.floor((new Date(p.startDate).getTime() - Date.now()) / 1000)
  return diff > 0 ? diff : 0
}

function presaleCountdown(seconds: number): string {
  if (seconds <= 0) return t('presale_status')
  const d = Math.floor(seconds / 86400)
  const h = Math.floor((seconds % 86400) / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  if (d > 0) return t('starts_in_days_hours', { d: String(d), h: String(h) })
  return t('starts_in_hours_minutes', { h: String(h), m: String(m) })
}

function getTag(p: ProjectModel) {
  const status = projectStore.getPurchasedStatus(p.id)
  const presale = isPresale(p)
  if (status === 2) return { text: t('running'), color: '#3DDC97' }
  if (status === 1) return { text: t('waiting'), color: '#00D9FF' }
  if (presale) return { text: t('presale_status'), color: '#00D9FF' }
  if (p.canBuy) return { text: t('available_tag'), color: '#3DDC97' }
  return { text: t('closed'), color: '#8A9CB0' }
}

function getDateInfo(p: ProjectModel): string {
  if (isPresale(p)) return presaleCountdown(presaleSeconds(p))
  return `${t('remaining_label')}${p.totalCnt - p.salesCnt}`
}

function openActivation(p: ProjectModel) {
  selectedProject.value = p
  showActivationDialog.value = true
}

function closeActivation() {
  showActivationDialog.value = false
  selectedProject.value = null
}

function getAvailableBalance(): number {
  if (!userStore.state.wallets.length) return 0
  const w = userStore.state.wallets[0]
  if (!w) return 0
  const funding = w['Funding']
  if (funding && typeof funding === 'object') {
    return parseFloat(funding['available'] || '0') || 0
  }
  return 0
}

const activationInfo = computed(() => {
  const p = selectedProject.value
  if (!p) return null
  const price = parseFloat(p.projectPrice) || 0
  const available = getAvailableBalance()
  return {
    name: p.name,
    avatar: p.image || '/images/robotAvatar1.png',
    tag: getTag(p).text,
    deposit: `${p.projectPrice} USDT`,
    duration: `${p.projectDay} ${t('days_unit')}`,
    currentBalance: `${available.toFixed(2)} USDT`,
    afterLock: `${(available - price).toFixed(2)} USDT`,
  }
})

async function confirmActivation() {
  if (!selectedProject.value || activating.value) return
  activating.value = true
  const res = await ProjectApi.createOrder(selectedProject.value.id)
  activating.value = false
  if (res.code === 0) {
    showToast(t('activation_success'))
    closeActivation()
    await projectStore.fetchProjects()
    await userStore.fetchAfterLogin()
  }
}

onMounted(async () => {
  userStore.loadFromStorage()
  if (userStore.state.loggedIn) {
    await Promise.all([
      projectStore.fetchProjects(),
      userStore.fetchAfterLogin(),
    ])
  } else {
    await projectStore.fetchProjects()
  }
})
</script>

<template>
  <div class="home-screen">
    <div class="home-bg">
      <div class="bg-glow"></div>
      <div class="dot-grid"></div>
    </div>

    <main class="home-content">
      <section class="hero-banner">
        <div class="hero-text">
          <h2>{{ t('deposit_principal') }}</h2>
          <p class="hero-desc">{{ t('fixed_computing') }}</p>
          <div class="hero-tags">
            <span class="tag tag-pink">{{ t('exclusive_power') }}</span>
            <span class="tag tag-green">{{ t('auto_arbitrage') }}</span>
            <span class="tag tag-cyan">{{ t('realtime_returns') }}</span>
          </div>
        </div>
        <img src="/images/hero.png" alt="hero" class="hero-img" />
      </section>

      <h3 class="section-title">{{ t('choose_robot') }}</h3>

      <div v-if="projectStore.state.loading" class="loading-wrap">
        <div class="spinner"></div>
      </div>

      <div v-else class="robot-list">
        <div
          v-for="p in projectStore.state.projects"
          :key="p.id"
          class="robot-card"
          :class="{
            'is-running': projectStore.getPurchasedStatus(p.id) === 2,
            'is-waiting': projectStore.getPurchasedStatus(p.id) === 1,
            'is-presale': isPresale(p),
          }"
        >
          <div class="card-top">
            <div class="robot-avatar" :style="{ borderColor: getTag(p).color }">
              <img :src="p.image || '/images/robotAvatar1.png'" :alt="p.name" />
            </div>
            <div class="card-info">
              <div class="card-name-row">
                <span class="robot-name">{{ p.name }}</span>
                <span class="status-badge" :style="{ '--badge-color': getTag(p).color }">
                  <i class="badge-dot"></i>
                  {{ getTag(p).text }}
                </span>
              </div>
              <p class="card-meta">{{ t('deposit_label') }}{{ p.projectPrice }} USDT · {{ p.projectDay }}{{ t('days_unit') }}</p>
            </div>
          </div>
          <div class="card-bottom">
            <div class="card-profit-info">
              <span class="profit-text">{{ calcProfit(p) }}</span>
              <span class="date-text">{{ getDateInfo(p) }}</span>
            </div>
            <button
              v-if="projectStore.getPurchasedStatus(p.id) === 2"
              class="action-btn running-btn"
            >
              <i class="btn-dot"></i>{{ t('running') }}
            </button>
            <button
              v-else-if="projectStore.getPurchasedStatus(p.id) === 1"
              class="action-btn waiting-btn"
            >
              <i class="btn-dot"></i>{{ t('waiting') }}
            </button>
            <button
              v-else-if="isPresale(p)"
              class="action-btn presale-btn"
            >
              {{ presaleCountdown(presaleSeconds(p)) }}
            </button>
            <button
              v-else-if="!p.canBuy"
              class="action-btn closed-btn"
            >
              {{ t('closed') }}
            </button>
            <button
              v-else
              class="action-btn activate-btn"
              @click="openActivation(p)"
            >
              {{ t('activate') }}
            </button>
          </div>
        </div>
      </div>

      <section class="notice-card">
        <h4>{{ t('activation_notice') }}</h4>
        <p>{{ t('activation_notice_text') }}</p>
      </section>
    </main>

    <!-- Activation Dialog -->
    <Teleport to="body">
      <div v-if="showActivationDialog && activationInfo" class="dialog-overlay" @click.self="closeActivation">
        <div class="activation-dialog">
          <h3>{{ t('confirm_activation') }}</h3>
          <div class="dialog-robot-info">
            <img :src="activationInfo.avatar" alt="" class="dialog-avatar" />
            <div>
              <strong>{{ activationInfo.name }} · {{ activationInfo.tag }}</strong>
              <p>{{ t('cycle_label') }}: {{ activationInfo.duration }} · {{ t('principal_plan') }}</p>
            </div>
          </div>
          <div class="dialog-principal">
            <span>{{ t('principal_to_lock') }}</span>
            <strong>{{ activationInfo.deposit }}</strong>
          </div>
          <div class="dialog-divider"></div>
          <div class="dialog-row"><span>{{ t('pay_from') }}</span><span>{{ t('available_balance') }}</span></div>
          <div class="dialog-row"><span>{{ t('current_available') }}</span><span class="white">{{ activationInfo.currentBalance }}</span></div>
          <div class="dialog-row"><span>{{ t('available_after_lock') }}</span><span class="green">{{ activationInfo.afterLock }}</span></div>
          <button class="dialog-confirm-btn" :class="{ loading: activating }" :disabled="activating" @click="confirmActivation">
            <span class="btn-spinner"></span>
            <span class="btn-text">{{ t('confirm') }}</span>
          </button>
          <button class="dialog-cancel-btn" @click="closeActivation">{{ t('cancel') }}</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped lang="scss">
$bg-elevated: #0E1620;
$bg-input: #12181F;
$border-default: #1E2830;
$action-primary: #3DDC97;
$text-primary: #F4F7F8;
$text-muted: #8A9CB0;
$cyan: #00D9FF;
$pink: #FF6B9D;

.home-screen {
  position: relative;
  min-height: 100vh;
  background: #0A0E14;
  overflow: hidden;
}

.home-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;

  .bg-glow {
    position: absolute;
    top: -10%;
    right: 20%;
    width: 50vw;
    height: 50vw;
    background: radial-gradient(circle, rgba(0, 255, 163, 0.04) 0%, transparent 70%);
    border-radius: 50%;
  }

  .dot-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 28px 28px;
  }
}

.home-content {
  position: relative;
  padding: 20px 16px 88px;
}

.hero-banner {
  display: flex;
  align-items: center;
  padding: 20px;
  background: $bg-elevated;
  border-radius: 16px;
  border: 1px solid rgba($action-primary, 0.2);
  margin-bottom: 24px;

  .hero-text {
    flex: 1;

    h2 {
      font-size: 16px;
      font-weight: 700;
      color: white;
      letter-spacing: -0.5px;
      margin: 0;
    }

    .hero-desc {
      font-size: 12px;
      color: $text-muted;
      margin: 8px 0 12px;
    }
  }

  .hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  .hero-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-left: 12px;
  }
}

.tag {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;

  &.tag-pink {
    color: $pink;
    background: rgba($pink, 0.15);
    border: 1px solid rgba($pink, 0.3);
  }

  &.tag-green {
    color: $action-primary;
    background: rgba($action-primary, 0.15);
    border: 1px solid rgba($action-primary, 0.3);
  }

  &.tag-cyan {
    color: $cyan;
    background: rgba($cyan, 0.15);
    border: 1px solid rgba($cyan, 0.3);
  }
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: white;
  margin: 0 0 16px;
}

.loading-wrap {
  display: flex;
  justify-content: center;
  padding: 40px 0;

  .spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba($action-primary, 0.2);
    border-top-color: $action-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}

.robot-list {
  display: grid;
  gap: 12px;
}

.robot-card {
  padding: 16px;
  background: $bg-elevated;
  border-radius: 14px;
  border: 1.5px solid $border-default;

  &.is-running {
    border-color: rgba($action-primary, 0.4);
  }

  &.is-waiting,
  &.is-presale {
    border-color: rgba($cyan, 0.3);
  }

  .card-top {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .robot-avatar {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  .card-info {
    flex: 1;
    min-width: 0;
  }

  .card-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .robot-name {
    font-size: 16px;
    font-weight: 700;
    color: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .status-badge {
    --badge-color: #{$action-primary};
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    color: var(--badge-color);
    background: color-mix(in srgb, var(--badge-color) 15%, transparent);
    border: 1px solid color-mix(in srgb, var(--badge-color) 40%, transparent);
    white-space: nowrap;

    .badge-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--badge-color);
    }
  }

  .card-meta {
    font-size: 12px;
    color: $text-muted;
    margin-top: 6px;
  }

  .card-bottom {
    display: flex;
    align-items: center;
    margin-top: 12px;
    padding: 12px;
    background: $bg-input;
    border-radius: 10px;
  }

  .card-profit-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;

    .profit-text {
      font-size: 13px;
      font-weight: 600;
      color: $action-primary;
    }

    .date-text {
      font-size: 11px;
      color: $text-muted;
    }
  }
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  white-space: nowrap;

  .btn-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }

  &.running-btn {
    color: $action-primary;
    background: transparent;
    border: 1.5px solid $action-primary;

    .btn-dot { background: $action-primary; }
  }

  &.waiting-btn {
    color: $cyan;
    background: transparent;
    border: 1.5px solid $cyan;

    .btn-dot { background: $cyan; }
  }

  &.presale-btn {
    color: $cyan;
    background: rgba($cyan, 0.1);
    border: 1px solid rgba($cyan, 0.4);
  }

  &.closed-btn {
    color: $text-muted;
    background: transparent;
    border: 1.5px solid $text-muted;
  }

  &.activate-btn {
    color: #0A0E14;
    background: $action-primary;
    padding: 8px 20px;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba($action-primary, 0.3);
  }
}

.notice-card {
  margin-top: 24px;
  padding: 16px;
  background: $bg-elevated;
  border-radius: 12px;
  border: 1px solid $border-default;

  h4 {
    font-size: 14px;
    font-weight: 600;
    color: white;
    margin: 0 0 10px;
  }

  p {
    font-size: 11px;
    color: $text-muted;
    line-height: 1.5;
    margin: 0;
  }
}

// Activation Dialog
.dialog-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.6);
  padding: 20px;
}

.activation-dialog {
  width: 100%;
  max-width: 400px;
  padding: 24px;
  background: $bg-elevated;
  border-radius: 20px;
  border: 1px solid $border-default;

  h3 {
    font-size: 20px;
    font-weight: 700;
    color: white;
    text-align: center;
    margin: 0 0 20px;
  }

  .dialog-robot-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg-input;
    border-radius: 12px;
    border: 1px solid rgba($action-primary, 0.15);
    box-shadow: 0 4px 20px rgba($action-primary, 0.12);

    .dialog-avatar {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      object-fit: cover;
    }

    strong {
      display: block;
      font-size: 15px;
      color: white;
    }

    p {
      font-size: 12px;
      color: $text-muted;
      margin: 4px 0 0;
    }
  }

  .dialog-principal {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;

    span {
      font-size: 14px;
      color: $text-muted;
    }

    strong {
      font-size: 20px;
      font-weight: 700;
      color: $action-primary;
    }
  }

  .dialog-divider {
    height: 1px;
    background: $border-default;
    margin: 16px 0;
  }

  .dialog-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 13px;
    color: $text-muted;

    .white { color: white; font-weight: 600; }
    .green { color: $action-primary; font-weight: 600; }
  }

  .dialog-confirm-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%;
    height: 48px;
    margin-top: 24px;
    background: linear-gradient(90deg, #26FFBF, #00D98C);
    border: none;
    border-radius: 12px;
    color: #0A0E14;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba($action-primary, 0.3);
    position: relative;

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .btn-spinner {
      display: none;
      position: absolute;
      inset: 0;
      margin: auto;
      width: 20px; height: 20px; border: 2px solid rgba(#0A0E14, 0.3);
      border-top-color: #0A0E14; border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }

    .btn-text { transition: opacity 0.15s; }

    &.loading {
      .btn-spinner { display: block; }
      .btn-text { opacity: 0; }
    }
  }

  .dialog-cancel-btn {
    display: block;
    width: 100%;
    margin-top: 12px;
    background: none;
    border: none;
    color: $text-muted;
    font-size: 14px;
    cursor: pointer;
    text-align: center;
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .hero-banner {
    padding: 16px;

    .hero-text h2 { font-size: 14px; }
    .hero-img { width: 60px; height: 60px; }
  }

  .robot-card .card-bottom {
    align-items: flex-start;
    gap: 10px;

    .action-btn { align-self: flex-end; }
  }
}
</style>
