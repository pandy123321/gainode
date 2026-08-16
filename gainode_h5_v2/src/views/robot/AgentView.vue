<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ProjectApi } from '../../api/services'
import { useProjectStore, type ProjectOrderModel } from '../../stores/project'
import { useUserStore } from '../../stores/user'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'
import ConfirmDialog from '../../components/ConfirmDialog.vue'

const router = useRouter()
const projectStore = useProjectStore()
const userStore = useUserStore()

const tradeLogs = ref<{ match: string; time: string; profit: string; status: string }[]>([])
const nodeName = ref('')
const nodeRegion = ref('')
const claiming = ref(false)
const showClaimDialog = ref(false)
const isClaimDisabled = computed(() => {
  const order = projectStore.state.runningOrder
  if (!order) return true
  return projectStore.getClaimableAmount(order) === '+0.00 USDT'
})

onMounted(async () => {
  await loadData()
  loadIpInfo()
})

function goHome() {
  router.push('/home')
}

async function loadData() {
  const res = await ProjectApi.getOrderList()
  if (res.code === 0 && res.data) {
    const list = (res.data.data || res.data) as any[]
    const orders = list.map(projectStore.parseOrder)
    projectStore.state.runningOrder = orders.find((o) => o.isDefault === 1) || orders[0] || null
    const order = projectStore.state.runningOrder
    if (order) loadTradeLogs(order.projectId)
  }
}

async function loadTradeLogs(projectId: number) {
  const res = await ProjectApi.getTradeLogs(projectId)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : data.data || []
    tradeLogs.value = list.slice(0, 5).map((e: any) => {
      const profit = parseFloat(e.actual_profit?.toString() || '0') || 0
      return {
        match: e.event_name?.toString() || '',
        time: e.created_time || '',
        profit: `${profit >= 0 ? '+' : ''}${profit.toFixed(2)} USDT`,
        status:
          e.status?.toString() === '2' || e.phase?.toString() === '3'
            ? t('completed')
            : t('running'),
      }
    })
  }
}

async function loadIpInfo() {
  const res = await ProjectApi.getIpInfo()
  if (res.code === 0 && res.data) {
    nodeName.value = res.data.ip?.toString() || ''
    nodeRegion.value = res.data.country?.toString() || ''
  }
}

async function handleClaim() {
  if (claiming.value || isClaimDisabled.value) return
  showClaimDialog.value = true
}

async function confirmClaim() {
  if (claiming.value) return
  const order = projectStore.state.runningOrder
  if (!order) return
  claiming.value = true
  try {
    const res = await ProjectApi.receive(order.id)
    if (res.code === 0) {
      showToast('领取成功')
      userStore.fetchAfterLogin()
      loadData()
    }
  } finally {
    claiming.value = false
    showClaimDialog.value = false
  }
}
</script>

<template>
  <div class="agent-screen">
    <!-- Running Robot Card -->
    <div v-if="!projectStore.state.runningOrder" class="robot-card empty-card">
      <div class="card-row">
        <div class="robot-img-wrap">
          <img src="/images/robotAvatar2.png" alt="" />
        </div>
        <div class="card-text">
          <strong>{{ t('no_robot') }}</strong>
          <p>{{ t('buy_robot_to_start') }}</p>
        </div>
        <button class="go-buy-btn" @click="goHome">{{ t('go_buy') }}</button>
      </div>
      <div class="info-banner">
        <svg viewBox="0 0 20 20" fill="none" width="14" height="14">
          <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5" />
          <path
            d="M10 9v4M10 7h.01"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        <span>{{ t('robot_info_banner') }}</span>
      </div>
    </div>

    <template v-else>
      <div class="robot-card running-card">
        <div class="card-row">
          <div class="robot-img-wrap active">
            <img src="/images/robotAvatar2.png" alt="" />
          </div>
          <div class="card-text">
            <div class="name-row">
              <strong>{{ projectStore.state.runningOrder.projectName }}</strong>
              <span
                class="status-tag"
                :class="projectStore.state.runningOrder.status === 2 ? 'green' : 'cyan'"
              >
                <i></i>{{ projectStore.getStatusText(projectStore.state.runningOrder) }}
              </span>
            </div>
            <p>
              {{
                projectStore.state.runningOrder.status === 2
                  ? t('principal_locked_auto_running')
                  : projectStore.state.runningOrder.status === 3
                    ? t('order_ended')
                    : t('pending_review_auto_run')
              }}
            </p>
          </div>
          <button class="switch-btn" @click="router.push('/my-agents')">
            <svg viewBox="0 0 20 20" fill="none" width="14" height="14">
              <path
                d="M4 10h12M12 6l4 4-4 4"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            {{ t('switch_agent') }}
          </button>
        </div>
        <div class="node-banner">
          <i class="dot"></i>
          <div>
            <strong>{{ t('edge_node_sg07') }}{{ nodeRegion }}</strong>
            <span>{{ t('ip_connected_info') }}{{ nodeName }}</span>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <span>{{ t('locked_principal') }}</span>
          <strong>{{ projectStore.state.runningOrder.amount }} USDT</strong>
        </div>
        <div class="stat-card">
          <span>{{ t('remaining_cycle') }}</span>
          <strong>{{
            projectStore.state.runningOrder.status === 2
              ? `${t('remaining_label')}${projectStore.getRemainingDays(projectStore.state.runningOrder)}${t('days_unit')}`
              : projectStore.getStatusText(projectStore.state.runningOrder)
          }}</strong>
        </div>
      </div>

      <!-- Claim Card -->
      <div class="claim-card">
        <div class="claim-header">
          <span>{{ t('today_claimable_earnings') }}</span>
          <small>{{ t('to_earnings_balance') }}</small>
        </div>
        <div class="claim-body">
          <div>
            <strong class="claim-amount">{{
              projectStore.getClaimableAmount(projectStore.state.runningOrder)
            }}</strong>
          </div>
          <button
            class="claim-btn"
            :class="{ loading: claiming, disabled: isClaimDisabled }"
            :disabled="claiming || isClaimDisabled"
            @click="handleClaim"
          >
            <span v-if="claiming" class="btn-spinner"></span>
            {{ claiming ? '' : t('claim') }}
          </button>
        </div>
      </div>
    </template>

    <!-- Signals Quick Card -->
    <div class="quick-card" @click="router.push('/signals')">
      <div class="quick-icon">
        <svg viewBox="0 0 24 24" fill="none" width="24" height="24">
          <path
            d="M2 12h2l3-7 4 14 3-7h2l2-4h4"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
      <div class="quick-text">
        <strong>{{ t('live_signals') }}</strong>
        <span>{{ t('ev_monitoring') }}</span>
      </div>
      <span class="running-badge"><i></i>{{ t('running') }}</span>
    </div>

    <!-- Today Executions -->
    <div class="section-header">
      <h3>{{ t('today_executions') }}</h3>
      <button class="view-all-btn" @click="router.push('/arbitrage-records')">
        {{ t('view_all') }}
      </button>
    </div>

    <div v-if="!tradeLogs.length" class="empty-text">{{ t('empty') }}</div>
    <div v-else class="execution-list">
      <div v-for="(log, i) in tradeLogs" :key="i" class="execution-row">
        <div class="exec-left">
          <strong>{{ log.match }}</strong>
          <span>{{ t('time_label') }}: {{ log.time }}</span>
        </div>
        <div class="exec-right">
          <strong>{{ log.profit }}</strong>
          <span>{{ log.status }}</span>
        </div>
      </div>
    </div>

    <ConfirmDialog
      v-model:visible="showClaimDialog"
      :title="t('claim_confirm_title')"
      :message="t('claim_confirm_content')"
      :loading="claiming"
      @confirm="confirmClaim"
    />
  </div>
</template>

<style scoped lang="scss">
$elevated: #0e1620;
$border: #1e2830;
$green: #3ddc97;
$cyan: #00d9ff;
$muted: #8a9cb0;
$input: #12181f;

.agent-screen {
  padding: 16px 16px 100px;
}

.robot-card {
  padding: 16px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid $border;
  margin-bottom: 12px;

  &.running-card {
    border-color: rgba($green, 0.25);
  }

  .card-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .robot-img-wrap {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    border: 1px solid $border;
    overflow: hidden;
    flex-shrink: 0;

    &.active {
      border-color: $green;
      border-width: 1.5px;
    }
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  .card-text {
    flex: 1;
    min-width: 0;

    strong {
      font-size: 16px;
      color: white;
    }
    p {
      font-size: 12px;
      color: $muted;
      margin: 4px 0 0;
    }
  }

  .name-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .status-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;

    i {
      width: 6px;
      height: 6px;
      border-radius: 50%;
    }

    &.green {
      color: $green;
      background: rgba($green, 0.15);
      border: 1px solid rgba($green, 0.4);
      i {
        background: $green;
      }
    }
    &.cyan {
      color: $cyan;
      background: rgba($cyan, 0.15);
      border: 1px solid rgba($cyan, 0.4);
      i {
        background: $cyan;
      }
    }
  }

  .go-buy-btn {
    position: relative;
    z-index: 1;
    padding: 7px 16px;
    background: $green;
    border: none;
    border-radius: 20px;
    color: #0a0e14;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
  }

  .switch-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 7px 12px;
    background: none;
    border: 1px solid $green;
    border-radius: 20px;
    color: $green;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
  }

  .info-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding: 10px 12px;
    background: #1a1a2e;
    border-radius: 10px;
    color: $muted;
    font-size: 11px;
  }

  .node-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
    padding: 10px 12px;
    background: #0a1a14;
    border-radius: 10px;
    border: 1px solid rgba($green, 0.2);

    .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: $green;
      flex-shrink: 0;
    }
    strong {
      display: block;
      font-size: 13px;
      color: white;
      font-weight: 600;
    }
    span {
      font-size: 11px;
      color: $muted;
    }
  }
}

.stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;

  .stat-card {
    padding: 10px;
    background: $elevated;
    border-radius: 12px;
    border: 1px solid $border;

    span {
      display: block;
      font-size: 12px;
      color: $muted;
    }
    strong {
      display: block;
      font-size: 14px;
      font-weight: 700;
      color: white;
      margin-top: 2px;
    }
  }
}

.claim-card {
  padding: 16px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid rgba($green, 0.2);
  margin-bottom: 12px;

  .claim-header {
    span {
      font-size: 14px;
      font-weight: 600;
      color: white;
    }
    small {
      font-size: 12px;
      color: $muted;
    }
  }

  .claim-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;

    .claim-amount {
      font-size: 22px;
      font-weight: 700;
      color: $green;
    }
  }

  .claim-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 8px;
    background: $green;
    border: none;
    border-radius: 8px;
    color: #0a0e14;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s;

    &:disabled {
      cursor: not-allowed;
    }

    &.disabled {
      background: #2a3340;
      color: $muted;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .btn-spinner {
      width: 14px;
      height: 14px;
      border: 2px solid rgba(#0a0e14, 0.3);
      border-top-color: #0a0e14;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
  }
}

.quick-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid $border;
  margin-bottom: 20px;
  cursor: pointer;

  .quick-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    background: rgba($green, 0.1);
    border-radius: 10px;
    color: $green;
  }

  .quick-text {
    flex: 1;
    strong {
      display: block;
      font-size: 15px;
      color: white;
      font-weight: 700;
    }
    span {
      font-size: 12px;
      color: $muted;
    }
  }

  .running-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: rgba($green, 0.12);
    border: 1px solid rgba($green, 0.3);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    color: $green;

    i {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: $green;
    }
  }
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;

  h3 {
    font-size: 16px;
    font-weight: 700;
    color: white;
    margin: 0;
  }
  .view-all-btn {
    background: none;
    border: none;
    color: $green;
    font-size: 12px;
    cursor: pointer;
  }
}

.empty-text {
  text-align: center;
  padding: 20px;
  color: $muted;
  font-size: 13px;
}

.execution-list {
  display: grid;
  gap: 8px;
}

.execution-row {
  display: flex;
  justify-content: space-between;
  padding: 14px 16px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;

  .exec-left {
    strong {
      display: block;
      font-size: 14px;
      color: white;
      font-weight: 600;
    }
    span {
      font-size: 12px;
      color: $muted;
    }
  }

  .exec-right {
    text-align: right;
    strong {
      display: block;
      font-size: 14px;
      color: $green;
      font-weight: 600;
    }
    span {
      font-size: 12px;
      color: $muted;
    }
  }
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
