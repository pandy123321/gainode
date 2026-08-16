<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useUserStore } from '../stores/user'
import BottomNav from '../components/BottomNav.vue'

const router = useRouter()
const userStore = useUserStore()

userStore.loadFromStorage()
</script>

<template>
  <div class="main-layout">
    <header class="main-header">
      <img src="/images/log.png" alt="Logo" class="logo" />
      <div class="header-right">
        <button class="icon-btn" aria-label="切换语言" @click="router.push('/lang')">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
            <path d="M3 12h18M12 3c2.5 2.8 4 6 4 9s-1.5 6.2-4 9c-2.5-2.8-4-6-4-9s1.5-6.2 4-9z" stroke="currentColor" stroke-width="1.8" />
          </svg>
        </button>
        <div class="avatar-wrap">
          <img :src="userStore.state.userInfo.avatar || '/images/robotAvatar1.png'" alt="avatar" class="avatar" />
        </div>
      </div>
    </header>

    <div class="main-content">
      <router-view />
    </div>

    <BottomNav />
  </div>
</template>

<style scoped lang="scss">
$green: #3DDC97;

.main-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #0A0E14;
}

.main-header {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background: #0A0E14;
  backdrop-filter: blur(12px);

  .logo {
    height: 28px;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .icon-btn {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
  }

  .avatar-wrap {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1.5px solid $green;
    overflow: hidden;
    

    .avatar {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
}

.main-content {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 70px;
}
</style>
