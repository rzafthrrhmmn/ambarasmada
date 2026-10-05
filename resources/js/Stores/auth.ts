import { defineStore } from 'pinia';
import type { User } from '@/Types/user';

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isAuthenticated = computed(() => user.value !== null);

  function setUser(userData: User | null) {
    user.value = userData;
  }

  function logout() {
    user.value = null;
  }

  function updateUser(partial: Partial<User>) {
    if (user.value) {
      user.value = { ...user.value, ...partial };
    }
  }

  return {
    user,
    isAuthenticated,
    setUser,
    logout,
    updateUser,
  };
});
