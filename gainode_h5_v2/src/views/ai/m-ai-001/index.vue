<!--
  M-AI-001 · AI 信号（P1）— 高保真原型页
  PROTOTYPE-MOCK-DATA：全部数据为页面内 mock，不接任何 API。
  PROTOTYPE-COPY：新增文案暂用硬编码中文，合同冻结后统一迁 i18n（Known Deviation）。
-->
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { t } from '../../../i18n'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()

interface SignalItem {
  id: string
  pair: string
  direction: 'LONG' | 'SHORT'
  spreadBps: number
  confidence: number // 0-100
  venueA: string
  venueB: string
  status: 'LIVE' | 'FILLED' | 'EXPIRED'
  minutesAgo: number
}

// PROTOTYPE-MOCK-DATA
const SIGNALS: SignalItem[] = [
  { id: 'SIG-2041', pair: 'APT/USDT', direction: 'LONG', spreadBps: 42, confidence: 87, venueA: 'Binance', venueB: 'OKX', status: 'LIVE', minutesAgo: 2 },
  { id: 'SIG-2040', pair: 'BTC/USDT', direction: 'SHORT', spreadBps: 18, confidence: 72, venueA: 'Bybit', venueB: 'Binance', status: 'FILLED', minutesAgo: 9 },
  { id: 'SIG-2039', pair: 'ETH/USDT', direction: 'LONG', spreadBps: 25, confidence: 64, venueA: 'OKX', venueB: 'Gate', status: 'LIVE', minutesAgo: 14 },
  { id: 'SIG-2038', pair: 'SOL/USDT', direction: 'SHORT', spreadBps: 61, confidence: 91, venueA: 'Binance', venueB: 'Bybit', status: 'EXPIRED', minutesAgo: 33 },
  { id: 'SIG-2037', pair: 'APT/USDT', direction: 'SHORT', spreadBps: 12, confidence: 55, venueA: 'Gate', venueB: 'OKX', status: 'EXPIRED', minutesAgo: 51 },
]

const STATS = { todayCount: 128, avgSpreadBps: 31, winRate24h: 68 }

const STATUS_LABEL: Record<SignalItem['status'], string> = {
  LIVE: '进行中',
  FILLED: '已成交',
  EXPIRED: '已过期',
}

function confClass(c: number): string {
  return c >= 80 ? 'hi' : c >= 65 ? 'mid' : 'low'
}
</script>

<template>
  <main class="app-page">
    <header class="page-header">
      <h1>{{ t('page.m_ai_001.title') }}</h1>
      <DataStateBadge page-id="M-AI-001" />
    </header>

    <!-- 统计概览 -->
    <section class="stats-row">
      <div class="stat-card">
        <span class="stat-num">{{ STATS.todayCount }}</span>
        <span class="stat-label">今日信号</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">{{ STATS.avgSpreadBps }}<small>bps</small></span>
        <span class="stat-label">平均价差</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">{{ STATS.winRate24h }}<small>%</small></span>
        <span class="stat-label">24h 胜率</span>
      </div>
    </section>

    <!-- 筛选 chips -->
    <section class="chips">
      <button class="chip active">全部</button>
      <button class="chip">进行中</button>
      <button class="chip">APT/USDT</button>
      <button class="chip">高置信度 ≥80</button>
    </section>

    <!-- 信号卡片列表 -->
    <section class="signal-list">
      <article v-for="s in SIGNALS" :key="s.id" class="signal-card">
        <div class="sig-head">
          <span class="pair">{{ s.pair }}</span>
          <span class="dir" :class="s.direction === 'LONG' ? 'long' : 'short'">
            {{ s.direction === 'LONG' ? '做多' : '做空' }}
          </span>
          <span class="status" :class="s.status.toLowerCase()">{{ STATUS_LABEL[s.status] }}</span>
        </div>
        <div class="sig-body">
          <div class="metric">
            <span class="m-label">价差</span>
            <span class="m-val spread">{{ s.spreadBps }} bps</span>
          </div>
          <div class="metric grow">
            <span class="m-label">置信度</span>
            <div class="conf-bar">
              <div
                class="conf-fill"
                :class="confClass(s.confidence)"
                :style="{ width: s.confidence + '%' }"
              />
            </div>
            <span class="m-val">{{ s.confidence }}%</span>
          </div>
        </div>
        <div class="sig-foot">
          <span>{{ s.venueA }} ⇄ {{ s.venueB }}</span>
          <span>{{ s.minutesAgo }} 分钟前 · {{ s.id }}</span>
        </div>
      </article>
    </section>

    <p class="proto-note">
      原型说明：数据为演示样例；AI 信号属内部套利引擎输出，C 端展示口径待合同冻结。
    </p>

    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/')">
        {{ t('common.cancel') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.app-page { min-height: 100vh; background: var(--gray-50); padding-bottom: var(--space-8); }
.page-header { display: flex; align-items: center; gap: var(--space-2); padding: var(--space-4); background: var(--white); border-bottom: var(--border-default); }
.page-header h1 { font-size: 20px; font-weight: 800; margin: 0; color: var(--gray-950); }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); padding: var(--space-4) var(--space-4) 0; }
.stat-card { background: var(--white); border-radius: var(--radius-md); padding: var(--space-3); display: flex; flex-direction: column; gap: 2px; box-shadow: var(--shadow-card); }
.stat-num { font-size: 20px; font-weight: 700; color: var(--brand-navy-950); }
.stat-num small { font-size: 11px; color: var(--gray-500); margin-left: 2px; }
.stat-label { font-size: 11px; color: var(--gray-500); }
.chips { display: flex; gap: var(--space-2); padding: var(--space-3) var(--space-4); overflow-x: auto; }
.chip { flex: 0 0 auto; border: 1px solid var(--gray-200); background: var(--white); border-radius: 999px; padding: 6px 12px; font-size: 12px; color: var(--gray-600); cursor: pointer; }
.chip.active { background: var(--brand-blue-600); border-color: var(--brand-blue-600); color: var(--white); }
.signal-list { display: flex; flex-direction: column; gap: var(--space-3); padding: 0 var(--space-4); }
.signal-card { background: var(--white); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4); box-shadow: var(--shadow-card); }
.sig-head { display: flex; align-items: center; gap: var(--space-2); }
.pair { font-weight: 700; color: var(--gray-950); }
.dir { font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; }
.dir.long { background: var(--success-100); color: var(--success-600); }
.dir.short { background: var(--danger-100); color: var(--danger-600); }
.status { margin-left: auto; font-size: 11px; color: var(--gray-500); }
.status.live { color: var(--info-600); font-weight: 600; }
.sig-body { display: flex; align-items: center; gap: var(--space-4); margin-top: var(--space-3); }
.metric { display: flex; flex-direction: column; gap: 4px; }
.metric.grow { flex: 1; flex-direction: row; align-items: center; gap: var(--space-2); }
.m-label { font-size: 11px; color: var(--gray-500); }
.m-val { font-size: 13px; font-weight: 600; color: var(--gray-800); }
.m-val.spread { color: var(--warning-600); }
.conf-bar { flex: 1; height: 6px; background: var(--gray-100); border-radius: 999px; overflow: hidden; }
.conf-fill { height: 100%; border-radius: 999px; }
.conf-fill.hi { background: var(--success-600); }
.conf-fill.mid { background: var(--warning-600); }
.conf-fill.low { background: var(--gray-400); }
.sig-foot { display: flex; justify-content: space-between; margin-top: var(--space-3); font-size: 11px; color: var(--gray-500); }
.proto-note { padding: var(--space-3); font-size: 11px; color: var(--warning-600); background: var(--warning-100); border-radius: var(--radius-sm); margin: var(--space-4); line-height: 1.5; }
.actions { padding: var(--space-4); }
.btn-primary { width: 100%; height: 48px; border: none; border-radius: var(--radius-lg); background: var(--brand-blue-600); color: var(--white); font-size: 16px; font-weight: 700; cursor: pointer; }
</style>
