<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ProjectApi } from '../../api/services'
import { useProjectStore } from '../../stores/project'
import { useUserStore } from '../../stores/user'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'
import ConfirmDialog from '../../components/ConfirmDialog.vue'

const router = useRouter()
const projectStore = useProjectStore()
const userStore = useUserStore()
const orders = ref<any[]>([])
const loading = ref(true)
const claimingId = ref<number | null>(null)
const settingDefaultId = ref<number | null>(null)
const showClaimDialog = ref(false)
const pendingClaimId = ref<number | null>(null)

onMounted(() => loadOrders())

async function loadOrders() {
  const res = await ProjectApi.getOrderList()
  if (res.code === 0 && res.data) {
    const list = (res.data.data || res.data) as any[]
    orders.value = list.map(projectStore.parseOrder)
  }
  loading.value = false
}

function filteredOrders() {
  return orders.value.filter((o: any) => o.status === 1 || o.status === 2 || o.status === 3)
}

async function handleClaim(orderId: number, amount: string) {
  if (claimingId.value === orderId) return
  if (amount === '+0.00 USDT') {
    showToast('可领取金额为0')
    return
  }
  pendingClaimId.value = orderId
  showClaimDialog.value = true
}

async function confirmClaim() {
  if (claimingId.value === pendingClaimId.value) return
  const orderId = pendingClaimId.value
  if (orderId === null) return
  claimingId.value = orderId
  try {
    const res = await ProjectApi.receive(orderId)
    if (res.code === 0) {
      showToast('领取成功')
      userStore.fetchAfterLogin()
      loadOrders()
    }
  } finally {
    claimingId.value = null
    showClaimDialog.value = false
    pendingClaimId.value = null
  }
}

async function handleSetDefault(orderId: number) {
  if (settingDefaultId.value === orderId) return
  settingDefaultId.value = orderId
  try {
    const res = await ProjectApi.setDefaultOrder(orderId)
    if (res.code === 0) {
      showToast('设置成功')
      router.back()
    }
  } finally {
    settingDefaultId.value = null
  }
}
</script>

<template>
  <div class="my-agents-screen">
    <header class="agents-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
          <path
            d="M15 18l-6-6 6-6"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
      <div>
        <h1>{{ t('my_agents') }}</h1>
        <p v-if="!loading">
          {{ t('running') }}
          {{ orders.filter((o: any) => o.status === 1 || o.status === 2).length }}/{{
            orders.length
          }}
        </p>
      </div>
    </header>

    <div class="agents-content">
      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!filteredOrders().length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="agent-list">
        <div v-for="o in filteredOrders()" :key="o.id" class="agent-card">
          <div class="card-top">
            <div class="agent-avatar"><img src="/images/robotAvatar1.png" alt="" /></div>
            <div class="agent-info">
              <div class="name-row">
                <strong>{{ o.projectName }}</strong>
                <span
                  class="status-badge"
                  :class="{ green: o.status === 2, cyan: o.status === 1, yellow: o.status === 3 }"
                >
                  {{ projectStore.getStatusText(o) }}
                </span>
              </div>
              <span class="order-no">{{ o.orderNo }}</span>
            </div>
          </div>
          <div class="metrics-row">
            <div class="metric">
              <span>{{ t('principal') }}</span
              ><strong>{{ o.amount }}</strong>
            </div>
            <div class="metric">
              <span>{{ t('earnings') }}</span
              ><strong>{{ o.settleAmount }}</strong>
            </div>
            <div class="metric">
              <span>{{ t('cycle') }}</span
              ><strong>{{
                o.status === 2
                  ? `${t('remaining_label')}${projectStore.getRemainingDays(o)}${t('days_unit')}`
                  : o.status === 3
                    ? `${projectStore.getSettledDays(o)}${t('days_unit')}`
                    : projectStore.getStatusText(o)
              }}</strong>
            </div>
          </div>
          <div class="card-actions" v-if="o.status === 2">
            <button
              v-if="projectStore.getClaimableAmount(o) !== '+0.00 USDT'"
              class="claim-action-btn"
              :class="{ loading: claimingId === o.id }"
              :disabled="claimingId === o.id"
              @click="handleClaim(o.id, projectStore.getClaimableAmount(o))"
            >
              <span class="btn-spinner"></span>
              <span class="btn-text">{{ `领取 ${projectStore.getClaimableAmount(o)}` }}</span>
            </button>
            <button
              class="default-btn"
              :class="{
                full: projectStore.getClaimableAmount(o) === '+0.00 USDT',
                loading: settingDefaultId === o.id,
              }"
              :disabled="settingDefaultId === o.id"
              @click="handleSetDefault(o.id)"
            >
              <span class="btn-spinner"></span>
              <span class="btn-text">{{ t('set_as_default') }}</span>
            </button>
          </div>
          <div class="card-actions" v-else-if="o.status === 1">
            <button
              class="default-btn full"
              :class="{ loading: settingDefaultId === o.id }"
              :disabled="settingDefaultId === o.id"
              @click="handleSetDefault(o.id)"
            >
              <span class="btn-spinner"></span>
              <span class="btn-text">{{ t('set_as_default') }}</span>
            </button>
          </div>
          <div
            class="card-actions"
            v-else-if="o.status === 3 && projectStore.getClaimableAmount(o) !== '+0.00 USDT'"
          >
            <button
              class="claim-action-btn"
              :class="{ loading: claimingId === o.id }"
              :disabled="claimingId === o.id"
              @click="handleClaim(o.id, projectStore.getClaimableAmount(o))"
            >
              <span class="btn-spinner"></span>
              <span class="btn-text">{{ `领取 ${projectStore.getClaimableAmount(o)}` }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="notice-box">
        <p>{{ t('activation_notice_text') }}</p>
      </div>
    </div>

    <ConfirmDialog
      v-model:visible="showClaimDialog"
      :title="t('claim_confirm_title')"
      :message="t('claim_confirm_content')"
      :loading="claimingId !== null"
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

.my-agents-screen {
  min-height: 100vh;
  background: #0a0e14;
}

.agents-header {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95);
  backdrop-filter: blur(10px);
  .back-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
  }
  h1 {
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin: 0;
  }
  p {
    font-size: 12px;
    color: $muted;
    margin: 2px 0 0;
  }
}

.agents-content {
  padding: 12px 16px 24px;
}

.loading-wrap {
  display: flex;
  justify-content: center;
  padding: 40px;
  .spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba($green, 0.2);
    border-top-color: $green;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}
.empty-text {
  text-align: center;
  padding: 40px;
  color: $muted;
  font-size: 14px;
}

.agent-list {
  display: grid;
  gap: 12px;
}

.agent-card {
  padding: 16px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid $border;

  .card-top {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .agent-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    border: 1px solid $border;
    overflow: hidden;
    flex-shrink: 0;
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
  .agent-info {
    flex: 1;
    min-width: 0;
  }
  .name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    strong {
      font-size: 16px;
      color: white;
    }
  }
  .status-badge {
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    &.green {
      color: $green;
      background: rgba($green, 0.15);
      border: 1px solid rgba($green, 0.4);
    }
    &.cyan {
      color: $cyan;
      background: rgba($cyan, 0.15);
      border: 1px solid rgba($cyan, 0.4);
    }
    &.yellow {
      color: #ffb800;
      background: rgba(#ffb800, 0.15);
      border: 1px solid rgba(#ffb800, 0.4);
    }
  }
  .order-no {
    font-size: 11px;
    color: $muted;
  }

  .metrics-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-top: 14px;
    .metric {
      padding: 10px;
      background: $input;
      border-radius: 8px;
      border: 1px solid $border;
      span {
        display: block;
        font-size: 10px;
        color: $muted;
      }
      strong {
        display: block;
        font-size: 13px;
        color: $green;
        font-weight: 700;
        margin-top: 4px;
      }
    }
  }

  .card-actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    .claim-action-btn {
      position: relative;
      flex: 1;
      padding: 14px;
      background: $green;
      border: none;
      border-radius: 10px;
      color: #0a0e14;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: opacity 0.2s;

      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      &.loading {
        pointer-events: none;
      }
      .btn-spinner {
        display: none;
        position: absolute;
        inset: 0;
        margin: auto;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(#0a0e14, 0.3);
        border-top-color: #0a0e14;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      &.loading .btn-spinner {
        display: block;
      }
      .btn-text {
        transition: opacity 0.15s;
      }
      &.loading .btn-text {
        opacity: 0;
      }
    }
    .default-btn {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 14px 12px;
      background: none;
      border: 1px solid $green;
      border-radius: 10px;
      color: $green;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity 0.2s;

      &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      &.full {
        flex: 1;
        text-align: center;
      }

      &.loading {
        pointer-events: none;
      }
      .btn-spinner {
        display: none;
        position: absolute;
        inset: 0;
        margin: auto;
        width: 16px;
        height: 16px;
        border: 2px solid rgba($green, 0.3);
        border-top-color: $green;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      &.loading .btn-spinner {
        display: block;
      }
      .btn-text {
        transition: opacity 0.15s;
      }
      &.loading .btn-text {
        opacity: 0;
      }
    }
  }
}

.notice-box {
  margin-top: 16px;
  padding: 14px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;
  p {
    font-size: 12px;
    color: $muted;
    line-height: 1.5;
    margin: 0;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
