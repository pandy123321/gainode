<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { TeamApi } from '../../api/services'
import { t } from '../../i18n'
import { showToast } from '../../utils/toast'

const router = useRouter()
const code = ref('')
const loading = ref(true)

const inviteUrl = () => `https://h5.gainode.com/#/register?inviteCode=${code.value}`
const qrUrl = () => `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(inviteUrl())}`

onMounted(async () => {
  const res = await TeamApi.getTeamDetail()
  if (res.code === 0 && res.data) {
    code.value = res.data.invite_code?.toString() || ''
  }
  loading.value = false
})

function copyCode() {
  if (!code.value) return
  navigator.clipboard.writeText(code.value)
  showToast('邀请码已复制')
}

function copyLink() {
  navigator.clipboard.writeText(inviteUrl())
  showToast('链接已复制')
}

function buildShareUrl(platform: string): string {
  const text = `Join Gainode AI Network and earn together! ${inviteUrl()}`
  const encodedText = encodeURIComponent(text)
  const encodedUrl = encodeURIComponent(inviteUrl())

  switch (platform) {
    case 'whatsapp':
      return `https://wa.me/?text=${encodedText}`
    case 'telegram':
      return `https://t.me/share/url?url=${encodedUrl}&text=${encodeURIComponent('Join Gainode AI Network!')}`
    case 'twitter':
      return `https://twitter.com/intent/tweet?text=${encodedText}`
    case 'facebook':
      return `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`
    default:
      return ''
  }
}

function handleShare(platform: string) {
  const shareUrl = buildShareUrl(platform)
  if (!shareUrl) {
    copyLink()
    return
  }
  window.open(shareUrl, '_blank')
}
</script>

<template>
  <div class="invite-screen">
    <header class="invite-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('invite_friends_title') }}</h1>
      <div class="header-spacer"></div>
    </header>

    <div class="invite-content">
      <!-- QR Code -->
      <div class="qr-card">
        <img :src="qrUrl()" alt="QR Code" class="qr-img" />
      </div>

      <!-- Invite Code -->
      <div class="code-card">
        <span class="card-label">{{ t('invite_code') }}</span>
        <div class="code-row">
          <strong>{{ code }}</strong>
          <button class="icon-copy-btn" @click="copyCode">
            <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><rect x="8" y="8" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2" stroke="currentColor" stroke-width="1.8"/></svg>
            {{ t('copy') }}
          </button>
        </div>
      </div>

      <!-- Invite Link -->
      <div class="link-card">
        <span class="card-label">{{ t('invite_link') }}</span>
        <div class="link-row">
          <span class="link-text">{{ inviteUrl() }}</span>
          <button class="icon-copy-btn small" @click="copyLink">
            <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><rect x="8" y="8" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2" stroke="currentColor" stroke-width="1.8"/></svg>
          </button>
        </div>
      </div>

      <!-- Share To -->
      <p class="share-label">{{ t('share_to') }}</p>
      <div class="share-row">
        <button class="share-btn" @click="handleShare('whatsapp')">
          <div class="share-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </div>
          <span>WhatsApp</span>
        </button>
        <button class="share-btn" @click="handleShare('telegram')">
          <div class="share-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.46-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.441-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.476-1.635.099-.002.321.023.465.14.12.098.153.228.169.319.016.091.036.298.02.462z"/></svg>
          </div>
          <span>Telegram</span>
        </button>
        <button class="share-btn" @click="handleShare('twitter')">
          <div class="share-icon dark">
            <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </div>
          <span>Twitter</span>
        </button>
        <button class="share-btn" @click="handleShare('facebook')">
          <div class="share-icon fb-blue">
            <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </div>
          <span>Facebook</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.invite-screen { min-height: 100vh; background: #0A0E14; }

.invite-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);

  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { flex: 1; font-size: 18px; font-weight: 700; color: white; margin: 0; text-align: center; }
  .header-spacer { width: 20px; }
}

.invite-content { padding: 24px; }

// QR Code
.qr-card {
  display: flex; justify-content: center; padding: 20px;
  background: white; border-radius: 16px; margin: 0 auto 24px;
  width: fit-content;

  .qr-img { width: 200px; height: 200px; display: block; }
}

// Invite Code
.code-card {
  padding: 14px; background: $elevated; border-radius: 12px;
  border: 1px solid $border; margin-bottom: 16px;

  .card-label { display: block; font-size: 12px; color: $muted; margin-bottom: 8px; }

  .code-row {
    display: flex; align-items: center; justify-content: space-between;

    strong { font-size: 18px; font-weight: 700; color: white; }
  }
}

.icon-copy-btn {
  display: flex; align-items: center; gap: 4px;
  padding: 8px 16px;
  background: rgba($green, 0.1);
  border: 1px solid rgba($green, 0.3);
  border-radius: 8px;
  color: $green; font-size: 13px; cursor: pointer;

  &.small { padding: 0; width: 36px; height: 36px; justify-content: center; background: $border; border: none; color: $muted; border-radius: 8px; }
}

// Invite Link
.link-card {
  padding: 14px; background: $elevated; border-radius: 12px;
  border: 1px solid $border; margin-bottom: 32px;

  .card-label { display: block; font-size: 12px; color: $muted; margin-bottom: 8px; }

  .link-row {
    display: flex; align-items: center; gap: 8px;

    .link-text {
      flex: 1; font-size: 13px; color: white; overflow: hidden;
      text-overflow: ellipsis; white-space: nowrap;
    }
  }
}

// Share
.share-label {
  text-align: center; font-size: 14px; color: $muted; margin: 0 0 16px;
}

.share-row {
  display: flex; justify-content: space-around;
}

.share-btn {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  background: none; border: none; cursor: pointer;

  .share-icon {
    width: 52px; height: 52px; display: grid; place-items: center;
    background: $border; border-radius: 14px; border: 1px solid #2A3540;

    &.green { color: #25D366; }
    &.blue { color: #26A5E4; }
    &.dark { color: white; }
    &.fb-blue { color: #1877F2; }
  }

  span { font-size: 11px; color: $muted; }
}
</style>
