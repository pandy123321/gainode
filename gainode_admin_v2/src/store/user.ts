import { defineStore } from 'pinia'
import {menu, permission, logout, getUserInfo} from "../api/module/user";
import router from '../router'

export const useUserStore = defineStore({
  id: 'user',
  state: () => {
    return {
      token: '',
      userInfo: {},
      permissions: [] as any[],
      menus: [] as any[],
    }
  },
  actions: {
    async clearCache() {
      localStorage.removeItem('user');
    },
    async logout() {
      const { data, code } = await logout();
      this.clearCache();
      if(code == 0) {
          router.push('/login');
      }
    },
    async getUserInfo() {
      const { data, code } = await getUserInfo();
      if(code == 0) {
        this.userInfo = data;
      }
    },
    async loadMenus(){
      const { data, code } = await menu();
      if(code == 200) {
        this.menus = data;
      }
    },
    async loadPermissions(){
      const { data, code } = await permission();
      if(code == 200) {
        this.permissions = data;
      }
    }
  },
  persist: {
    storage: localStorage,
    paths: ['token', 'userInfo', 'permissions', 'menus' ],
  }
})
