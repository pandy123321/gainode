<!--
  M-PREDICT-FREE-001 · 免费预测互动（P1）— 高保真原型页
  PROTOTYPE-MOCK-DATA：全部数据为页面内 mock，不接任何 API；不涉及真实下注/结算。
  PROTOTYPE-COPY：新增文案暂用硬编码中文，合同冻结后统一迁 i18n（Known Deviation）。
-->
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { t } from '../../../i18n'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()

interface FreePoll {
  id: string
  question: string
  options: Array<{ label: string; votes: number }>
  endsIn: string
  myVote: number | null
}

// PROTOTYPE-MOCK-DATA
const POLLS = ref<FreePoll[]>([
  {
    id: 'FP-101',
    question: '本周 APT 能否站上 $12？',
    options: [
      { label: '能 🚀', votes: 1832 },
      { label: '不能', votes: 964 },
    ],
    endsIn: '2 天 14 小时',
    myVote: null,
  },
  {
    id: 'FP-102',
    question: '今日 BTC 收盘涨跌？',
    options: [
      { label: '收涨', votes: 2410 },
      { label: '收跌', votes: 1975 },
    ],
    endsIn: '6 小时 32 分',
    myVote: null,
  },
  {
    id: 'FP-103',
    question: '下周机器人奖励池总量会创新高吗？',
    options: [
      { label: '会', votes: 733 },
      { label: '不会', votes: 1180 },
    ],
    endsIn: '4 天 8 小时',
    myVote: 1,
  },
])

function vote(poll: FreePoll, idx: number): void {
  if (poll.myVote !== null) return // 原型：单次投票
  poll.myVote = idx
}

function pct(poll: FreePoll, idx: number): number {
  const total = poll.options.reduce((s, o) => s + o.votes + (poll.myVote === poll.options.indexOf(o) ? 1 : 0), 0)
  const v = poll.options[idx].votes + (poll.myVote === idx ? 1 : 0)
  return Math.round((v / Math.max(total, 1)) * 100)
}

// PROTOTYPE-MOCK-DATA
const MY_RECORD = { joined: 46, correct: 31, streak: 4 }

const LEADERS = [
  { rank: 1, name: '预测达人 K***', score: 982 },
  { rank: 2, name: '链上猎手 M***', score: 941 },
  { rank: 3, name: 'APT信仰者 Z***', score: 903 },
  { rank: 4, name: '量化小白 L***', score: 867 },
  { rank: 5, name: '夜猫子 W***', score: 850 },
]
</script>

<template>
  <main class="app-page">
    <header class="page-header">
      <h1>{{ t('page.m_predict_free_001.title') }}</h1>
      <DataStateBadge page-id="M-PREDICT-FREE-001" />
    </header>

    <!-- 我的战绩 -->
    <section class="record-row">
      <div class="rec"><span class="rec-num">{{ MY_RECORD.joined }}</span><span class="rec-lbl">已参与</span></div>
      <div class="rec"><span class="rec-num ok">{{ MY_RECORD.correct }}</span><span class="rec-lbl">命中</span></div>
      <div class="rec"><span class="rec-num fire">{{ MY_RECORD.streak }} 连</span><span class="rec-lbl">当前连胜</span></div>
    </section>

    <!-- 免费竞猜列表 -->
    <section class="poll-list">
      <h2 class="sec-title">进行中的免费竞猜</h2>
      <article v-for="p in POLLS" :key="p.id" class="poll-card">
        <div class="poll-head">
          <span class="q">{{ p.question }}</span>
          <span class="ends">⏳ {{ p.endsIn }}</span>
        </div>
        <div class="opts">
          <button
            v-for="(o, i) in p.options"
            :key="o.label"
            class="opt"
            :class="{ mine: p.myVote === i, picked: p.myVote !== null }"
            :data-testid="`opt-${i}`"
            @click="vote(p, i)"
          >
            <span class="bar" :style="{ width: pct(p, i) + '%' }" />
            <span class="opt-row">
              <span>{{ o.label }}</span>
              <span v-if="p.myVote !== null" class="opct">{{ pct(p, i) }}%</span>
            </span>
          </button>
        </div>
        <footer class="poll-foot">
          <span>免费参与 · 无资金消耗</span>
          <span v-if="p.myVote !== null" class="voted">已投票 ✓</span>
          <span v-else>点击选项即可参与</span>
        </footer>
      </article>
    </section>

    <!-- 排行榜 -->
    <section class="board">
      <h2 class="sec-title">周榜 Top 5</h2>
      <ol class="rank-list">
        <li v-for="r in LEADERS" :key="r.rank" :class="{ top3: r.rank <= 3 }">
          <span class="rk">{{ r.rank }}</span>
          <span class="nm">{{ r.name }}</span>
          <span class="sc">{{ r.score }} 分</span>
        </li>
      </ol>
    </section>

    <p class="proto-note">
      原型说明：数据为演示样例；免费预测为 P1 合同范围，纯互动无真实经济效果，正式规则待合同冻结。
    </p>

    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/prediction')">
        {{ t('common.cancel') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.app-page { min-height: 100vh; background: var(--gray-50); padding-bottom: var(--space-8); }
.page-header { display: flex; align-items: center; gap: var(--space-2); padding: var(--space-4); background: var(--white); border-bottom: var(--border-default); }
.page-header h1 { font-size: 20px; font-weight: 800; margin: 0; color: var(--gray-950); }
.record-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); padding: var(--space-4) var(--space-4) 0; }
.rec { background: var(--white); border-radius: var(--radius-md); padding: var(--space-3); display: flex; flex-direction: column; align-items: center; gap: 2px; box-shadow: var(--shadow-card); }
.rec-num { font-size: 18px; font-weight: 800; color: var(--brand-navy-950); }
.rec-num.ok { color: var(--success-600); }
.rec-num.fire { color: var(--warning-600); }
.rec-lbl { font-size: 11px; color: var(--gray-500); }
.poll-list { padding: var(--space-4) var(--space-4) 0; }
.sec-title { font-size: 14px; font-weight: 700; color: var(--gray-950); margin: 0 0 var(--space-3); }
.poll-card { background: var(--white); border-radius: var(--radius-md); padding: var(--space-4); box-shadow: var(--shadow-card); margin-bottom: var(--space-3); }
.poll-head { display: flex; flex-direction: column; gap: var(--space-1); margin-bottom: var(--space-3); }
.q { font-size: 15px; font-weight: 700; color: var(--gray-950); line-height: 1.4; }
.ends { font-size: 11px; color: var(--warning-600); }
.opts { display: flex; flex-direction: column; gap: var(--space-2); }
.opt { position: relative; overflow: hidden; border: 1px solid var(--gray-200); background: var(--white); border-radius: var(--radius-sm); padding: var(--space-3); cursor: pointer; text-align: left; }
.opt.picked { cursor: default; }
.opt .bar { position: absolute; inset: 0 auto 0 0; background: var(--info-100); transition: width .25s ease; }
.opt.mine { border-color: var(--brand-blue-600); border-width: 2px; }
.opt.mine .bar { background: var(--brand-blue-600); opacity: .18; }
.opt-row { position: relative; display: flex; justify-content: space-between; font-size: 14px; color: var(--gray-800); font-weight: 600; }
.opct { color: var(--brand-blue-600); }
.poll-foot { display: flex; justify-content: space-between; margin-top: var(--space-3); font-size: 11px; color: var(--gray-400); }
.voted { color: var(--success-600); font-weight: 600; }
.board { margin: var(--space-2) var(--space-4) 0; background: var(--white); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4) var(--space-4); box-shadow: var(--shadow-card); }
.rank-list { list-style: none; margin: 0; padding: 0; }
.rank-list li { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px solid var(--gray-50); font-size: 13px; color: var(--gray-700); }
.rank-list li:last-child { border-bottom: none; }
.rk { width: 22px; height: 22px; border-radius: 999px; background: var(--gray-100); color: var(--gray-500); font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
li.top3 .rk { background: var(--brand-gold-300); color: var(--brand-navy-900); }
.nm { flex: 1; }
.sc { font-weight: 700; color: var(--gray-950); }
.proto-note { padding: var(--space-3); font-size: 11px; color: var(--warning-600); background: var(--warning-100); border-radius: var(--radius-sm); margin: var(--space-4); line-height: 1.5; }
.actions { padding: 0 var(--space-4); }
.btn-primary { width: 100%; height: 48px; border: none; border-radius: var(--radius-lg); background: var(--brand-blue-600); color: var(--white); font-size: 16px; font-weight: 700; cursor: pointer; }
</style>
