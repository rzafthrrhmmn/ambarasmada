import { defineStore } from 'pinia';

export const useUIStore = defineStore('ui', () => {
  const mobileMenuOpen = ref(false);
  const sidebarOpen = ref(false);
  const isLoading = ref(false);
  const offline = ref(!navigator.onLine);

  function setMobileMenuOpen(value: boolean) {
    mobileMenuOpen.value = value;
  }

  function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
  }

  function setSidebarOpen(value: boolean) {
    sidebarOpen.value = value;
  }

  function setLoading(value: boolean) {
    isLoading.value = value;
  }

  function setOffline(value: boolean) {
    offline.value = value;
  }

  return {
    mobileMenuOpen,
    sidebarOpen,
    isLoading,
    offline,
    setMobileMenuOpen,
    toggleMobileMenu,
    setSidebarOpen,
    setLoading,
    setOffline,
  };
});
