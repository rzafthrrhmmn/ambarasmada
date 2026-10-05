import { defineStore } from 'pinia';

export interface NotificationItem {
  id: number;
  type: string;
  message: string;
  read: boolean;
  created_at: string;
}

export const useNotificationStore = defineStore('notifications', () => {
  const items = ref<NotificationItem[]>([]);
  const unreadCount = ref(0);
  const isConnected = ref(false);

  function setItems(newItems: NotificationItem[]) {
    items.value = newItems;
    unreadCount.value = newItems.filter((item) => !item.read).length;
  }

  function addNotification(notification: NotificationItem) {
    items.value.unshift(notification);
    if (!notification.read) {
      unreadCount.value++;
    }
  }

  function markAsRead(id: number) {
    const item = items.value.find((i) => i.id === id);
    if (item && !item.read) {
      item.read = true;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  }

  function markAllAsRead() {
    items.value.forEach((item) => (item.read = true));
    unreadCount.value = 0;
  }

  function remove(id: number) {
    const item = items.value.find((i) => i.id === id);
    if (item && !item.read) {
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
    items.value = items.value.filter((i) => i.id !== id);
  }

  return {
    items,
    unreadCount,
    isConnected,
    setItems,
    addNotification,
    markAsRead,
    markAllAsRead,
    remove,
  };
});
