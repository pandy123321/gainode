<!--
  M-GROWTH-001 · 团队/邀请（P1）— 高保真原型页
  PROTOTYPE-MOCK-DATA：全部数据为页面内 mock，不接任何 API。
  PROTOTYPE-COPY：新增文案暂用硬编码中文，Referral/Agent P1 合同冻结后统一迁 i18n（Known Deviation）。
-->
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { t } from '../../../i18n'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()

const INVITE_CODE = ref('GAI-8K2M-QX47')
const copied = ref(false)

function copyCode(): void {
  copied.value = true
  setTimeout(() => (copied.value = false), 1500)
}

// PROTOTYPE-MOCK-DATA
const LEVEL_STATS = [
  { level: 'L1 直邀', members: 12, earnings: '328.50' },
  { level: 'L2 二级', members: 37, earnings: '96.20' },
  { level: 'L3 三级', members: 84, earnings: '21.75' },
]

const TOTAL_EARNINGS = '446.45'

interface Member {
  name: string
  masked: string
  level: string
  joined: string
  contribution: string
}

const MEMBERS: Member[] = [
  { name: '用户 A***3', masked: 'ID 10023***', level: 'L1', joined: '08-24', contribution: '52.10' },
  { name: '用户 T***9', masked: 'ID 10187***', level: 'L1', joined: '08-22', contribution: '38.40' },
  { name: '用户 M***2', masked: 'ID 10244***', level: 'L2', joined: '08-19', contribution: '12.60' },
  { name: '用户 K***7', masked: 'ID 10301***', level: 'L2', joined: '08-15', contribution: '9.80' },
  { name: '用户 L***5', masked: 'ID 10366***', level: 'L3', joined: '08-11', contribution: '4.25' },
]
</script>

<template>
  <main class="app-page">
    <header class="page-header">
      <h1>{{ t('page.m_growth_001.title') }}</h1>
      <DataStateBadge page-id="M-GROWTH-001" />
    </header>

    <!-- 累计收益 -->
    <section class="hero-card">
      <span class="hero-label">团队累计贡献收益（APT）</span>
      <span class="hero-num">{{ TOTAL_EARNINGS }}</span>
      <div class="hero-tags">
        <span class="tag">三级分佣</span>
        <span class="tag ghost">合同冻结后生效</span>
      </div>
    </section>

    <!-- 邀请卡 -->
    <section class="invite-card">
      <span class="invite-label">我的邀请码</span>
      <div class="invite-row">
        <code class="invite-code">{{ INVITE_CODE }}</code>
        <button class="copy-btn" data-testid="copy-invite" @click="copyCode">
          {{ copied ? '已复制 ✓' : '复制' }}
        </button>
      </div>
      <div class="qr-slot" aria-label="邀请二维码占位">
        <div class="qr-grid">
          <span v-for="i in 25" :key="i" class="qr-cell" :class="{ on: [1,3,4,5,7,9,11,13,15,17,19,21,23,25].includes(i) }" />
        </div>
        <span class="qr-hint">二维码占位 · 接入后由服务端生成</span>
      </div>
    </section>

    <!-- 分层统计 -->
    <section class="levels">
      <article v-for="l in LEVEL_STATS" :key="l.level" class="level-card">
        <span class="lv-name">{{ l.level }}</span>
        <span class="lv-members">{{ l.members }} 人</span>
        <span class="lv-earn">+{{ l.earnings }} APT</span>
      </article>
    </section>

    <!-- 成员列表 -->
    <section class="members">
      <h2 class="sec-title">团队成员</h2>
      <table class="member-table">
        <thead>
          <tr><th>成员</th><th>层级</th><th>加入</th><th>贡献</th></tr>
        </thead>
        <tbody>
          <tr v-for="m in MEMBERS" :key="m.masked">
            <td>
              <span class="m-name">{{ m.name }}</span>
              <span class="m-id">{{ m.masked }}</span>
            </td>
            <td><span class="lvl-badge">{{ m.level }}</span></td>
            <td>{{ m.joined }}</td>
            <td class="contrib">{{ m.contribution }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <p class="proto-note">
      原型说明：数据为演示样例；Referral/Agent 属 P1 合同范围（earnings 禁止自动生成），冻结前本页保持 DEFERRED。
    </p>

    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/me')">
        {{ t('common.cancel') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.app-page { min-height: 100vh; background: var(--gray-50); padding-bottom: var(--space-8); }
.page-header { display: flex; align-items: center; gap: var(--space-2); padding: var(--space-4); background: var(--white); border-bottom: var(--border-default); }
.page-header h1 { font-size: 20px; font-weight: 800; margin: 0; color: var(--gray-950); }
.hero-card { margin: var(--space-4); padding: var(--space-5); border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--brand-navy-900), var(--brand-blue-800)); color: var(--white); display: flex; flex-direction: column; gap: var(--space-2); }
.hero-label { font-size: 12px; opacity: .8; }
.hero-num { font-size: 32px; font-weight: 800; letter-spacing: .5px; }
.hero-tags { display: flex; gap: var(--space-2); margin-top: var(--space-2); }
.tag { font-size: 11px; background: rgba(255,255,255,.18); border-radius: 999px; padding: 3px 10px; }
.tag.ghost { background: transparent; border: 1px dashed rgba(255,255,255,.45); }
.invite-card { margin: 0 var(--space-4) var(--space-4); background: var(--white); border-radius: var(--radius-md); padding: var(--space-4); box-shadow: var(--shadow-card); }
.invite-label { font-size: 12px; color: var(--gray-500); }
.invite-row { display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-2); }
.invite-code { font-size: 20px; font-weight: 700; letter-spacing: 2px; color: var(--brand-blue-600); font-family: monospace; }
.copy-btn { border: none; background: var(--brand-blue-600); color: var(--white); border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13px; cursor: pointer; }
.qr-slot { display: flex; align-items: center; gap: var(--space-3); margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--gray-100); }
.qr-grid { display: grid; grid-template-columns: repeat(5, 10px); grid-auto-rows: 10px; gap: 2px; }
.qr-cell { background: var(--gray-100); border-radius: 2px; }
.qr-cell.on { background: var(--gray-950); }
.qr-hint { font-size: 11px; color: var(--gray-400); }
.levels { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); padding: 0 var(--space-4) var(--space-4); }
.level-card { background: var(--white); border-radius: var(--radius-md); padding: var(--space-3); display: flex; flex-direction: column; gap: 2px; box-shadow: var(--shadow-card); }
.lv-name { font-size: 11px; color: var(--gray-500); }
.lv-members { font-size: 16px; font-weight: 700; color: var(--gray-950); }
.lv-earn { font-size: 12px; font-weight: 600; color: var(--success-600); }
.members { margin: 0 var(--space-4); background: var(--white); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4) var(--space-4); box-shadow: var(--shadow-card); }
.sec-title { font-size: 14px; font-weight: 700; color: var(--gray-950); margin: var(--space-2) 0; }
.member-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.member-table th { text-align: left; color: var(--gray-400); font-weight: 500; padding: var(--space-2) 0; border-bottom: 1px solid var(--gray-100); }
.member-table td { padding: var(--space-2) 0; border-bottom: 1px solid var(--gray-50); color: var(--gray-700); }
.m-name { display: block; color: var(--gray-900); font-weight: 600; }
.m-id { display: block; font-size: 10px; color: var(--gray-400); }
.lvl-badge { background: var(--info-100); color: var(--info-600); border-radius: 999px; padding: 1px 8px; font-size: 10px; font-weight: 600; }
.contrib { color: var(--success-600); font-weight: 600; text-align: right; }
.proto-note { padding: var(--space-3); font-size: 11px; color: var(--warning-600); background: var(--warning-100); border-radius: var(--radius-sm); margin: var(--space-4); line-height: 1.5; }
.actions { padding: 0 var(--space-4); }
.btn-primary { width: 100%; height: 48px; border: none; border-radius: var(--radius-lg); background: var(--brand-blue-600); color: var(--white); font-size: 16px; font-weight: 700; cursor: pointer; }
</style>
