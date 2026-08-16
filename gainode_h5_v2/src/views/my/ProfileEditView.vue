<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { UserApi } from '../../api/services'
import { useUserStore } from '../../stores/user'
import { showToast } from '../../utils/toast'
import { s3Upload, generateFileName } from '../../utils/s3Upload'
import { t } from '../../i18n'

const router = useRouter()
const userStore = useUserStore()

const nickname = ref(userStore.state.userInfo.nickname || '')
const avatar = ref(userStore.state.userInfo.avatar || '')
const saving = ref(false)
const uploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

function triggerUpload() {
  fileInput.value?.click()
}

async function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploading.value = true
  try {
    const fileName = generateFileName(file)
    const url = await s3Upload(file, fileName)
    if (url) {
      avatar.value = url
      showToast(t('avatar_upload_success'))
    } else {
      showToast(t('avatar_upload_failed'))
    }
  } catch {
    showToast(t('avatar_upload_failed'))
  } finally {
    uploading.value = false
    input.value = ''
  }
}

async function handleSave() {
  if (saving.value) return
  saving.value = true
  try {
    const res = await UserApi.updateUserInfo({
      avatar: avatar.value,
      nickname: nickname.value,
    })
    if (res.code === 0) {
      showToast(t('save') + '成功')
      userStore.state.userInfo.nickname = nickname.value
      userStore.state.userInfo.avatar = avatar.value
      localStorage.setItem('user_info', JSON.stringify(userStore.state.userInfo))
      router.replace('/my')
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="profile-edit-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('edit_profile_title') }}</h1>
    </header>

    <div class="edit-content">
      <div class="avatar-section" @click="triggerUpload">
        <div class="avatar-wrap" :class="{ uploading }">
          <img :src="avatar || '/images/robotAvatar1.png'" alt="" />
          <div v-if="uploading" class="upload-mask"><div class="spinner"></div></div>
        </div>
        <span>{{ uploading ? t('uploading') : t('tap_to_change_avatar') }}</span>
        <input ref="fileInput" type="file" accept="image/*" class="file-input" @change="handleFileChange" />
      </div>

      <label>{{ t('nickname_label') }}</label>
      <div class="input-box">
        <input v-model="nickname" type="text" placeholder="输入昵称" />
      </div>

      <button class="save-btn" :class="{ loading: saving }" :disabled="saving" @click="handleSave">
        <span v-if="saving" class="btn-spinner"></span>
        {{ saving ? '' : t('save') }}
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.profile-edit-screen { min-height: 100vh; background: #0A0E14; }

.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.edit-content { padding: 24px 16px; }

.avatar-section {
  display: flex; flex-direction: column; align-items: center; margin-bottom: 32px; cursor: pointer;

  .avatar-wrap {
    width: 80px; height: 80px; border-radius: 50%; border: 2px solid $green;
    overflow: hidden; margin-bottom: 8px; position: relative;
    img { width: 100%; height: 100%; object-fit: cover; }

    .upload-mask {
      position: absolute; inset: 0; background: rgba(0, 0, 0, 0.55);
      display: flex; align-items: center; justify-content: center;

      .spinner {
        width: 24px; height: 24px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
    }
  }

  span { font-size: 12px; color: $muted; }

  .file-input { display: none; }
}

label {
  display: block; font-size: 14px; color: $muted; font-weight: 500; margin-bottom: 10px;
}

.input-box {
  margin-bottom: 32px;
  input {
    width: 100%; padding: 16px; background: $elevated; border: 1px solid $border;
    border-radius: 12px; color: white; font-size: 15px; outline: none;
    &::placeholder { color: #4A5568; }
  }
}

.save-btn {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 16px; background: $green; border: none; border-radius: 12px;
  color: #0A0E14; font-size: 16px; font-weight: 700; cursor: pointer;
  transition: opacity 0.2s;

  &:disabled { opacity: 0.6; cursor: not-allowed; }

  .btn-spinner {
    width: 20px; height: 20px; border: 2px solid rgba(#0A0E14, 0.3);
    border-top-color: #0A0E14; border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
