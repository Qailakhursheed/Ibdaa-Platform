import { ref } from 'vue'

export function useNotifications() {
  const notifications = ref([])
  const unreadCount = ref(0)
  const audioEnabled = ref(true)

  function addNotification(notification) {
    notifications.value.unshift({
      id: Date.now(),
      ...notification,
      read: false,
      timestamp: new Date()
    })
    unreadCount.value++

    // تشغيل صوت التنبيه
    if (audioEnabled.value) {
      playNotificationSound()
    }

    // عرض إشعار المتصفح
    if (notification.showBrowser !== false) {
      showBrowserNotification(notification)
    }
  }

  function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification && !notification.read) {
      notification.read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  function markAllAsRead() {
    notifications.value.forEach(n => {
      n.read = true
    })
    unreadCount.value = 0
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      const notification = notifications.value[index]
      if (!notification.read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
      notifications.value.splice(index, 1)
    }
  }

  function clearAll() {
    notifications.value = []
    unreadCount.value = 0
  }

  function playNotificationSound() {
    try {
      const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVqzn77BdGAg+ltryxnMpBSuBzvLZiTYIGGq98OScTgwNUKnn77RgGwU7k9jyyHkqBSd+zPDckUAJFF7B6uylVxQKRp/i8L9vIQU1iNPz04IyBh1tv+7mnEYODlWs6O+yXBkIPJfc8sp1KgUqgs7x2IY1CBtrvO/mnEYODlWs5++0XhsGOpTX88d4KwUmfsrv3ZFBCRVfwuvspFYUCkef4vDAcSEFNYnU8tGAMQYfbb7u5Z1GDg9XrOnvsl4bBj2W2vPIeCsEKYPO8tiHNQgcarzw55xFDA5VrOjvtF4bBjuT1/PHdysEKYLM79uQQQkVXsHr7KVWFApHn+LwwHAhBTSI1PLSgTEGHmq77uWbRQ0OVqvm77JdGgU8ldjyxnYqBCqDzfHZhzYIHGu98OWcRgwNU6vnm7VdGgU7lNjyxnYqBCmBze/ajkIMF1/A6+ylVhMJRaDi8MBwIQU0iNPy0n8xBh9tvO7lm0MNDlat5++yXRoFPJPY88d2KgMogszv2o9CChhfwOvrvFQTCkef4PDAcCIGNYnU79F+MAch b7zu5pxEDA5VrOjusV4aBTuU1/PJdysEKYLN8NqOQQwYX8Ds66dWEwlHoOLvwHAiBTWI0+/RgDEHImq87uidRg0OVKzn7rFeGgc8k9jzxncrBSuCzvDajkELFl/A7OumVhQKRp/h7r9vIgU1iNLv0n8xBSFsuPDnn0oNDlWs5++yXRsGPJXZ88l3KwQpgczv2o9ADBlew+vq5lYSCUWg4e7AbykFNYfP89GAMAYZ bLzv55xFDQ5VrOjusV4aBTyU2fPJdysFKYHM79uPQQ0YX8Ds66VWFAlGn+Huv3AiBjaJ0+/SfzEGImq87uacRA0PVazn7rJdGgY8k9jzyXYrBCmCzPDajkENGF/B7OulVhMKRp/h7r9wIgY1idLv0n8xBiFsuu/mnEQNDlSs5+6yXhoHPJTY88d2KgQqgszvXo5ATEVOT0FBACEgACKKkpqikqCYoJihoZiYmJSUkpSSjpKOjoqMiImEhISAfn54eHZ0dHByam5qamRiYl5aXl5aVlRUUVBQTE5KSkhGQkRCQD4+Oz45NzU1MzEwLi0sKyopKCcmJSQjIyIhICAfHh0dHB0bGhkYGBYWFRQUExIREREQDw4ODQwLCwoKCQgHBgYGBQQDAwICAQEBAAABAQICAwMEBAUFBgYICAkJCgsLDA0NDg4PEBARERITExQVFRYWGBgZGhscHR0eHx8gICEiIyMkJSUpKCkqKystLi8wMTI0NTU3OTo7Pj5AQUJEREZISUpMTE5PUFJUVFZXWVteXF5fYmNlZ2lpam1uc3Bydnl+enx+gISEhYqOiYyOj5SWk5aXm5yamZ6eoKGjoKSkqKirqq2tsbCxs7a3tri6vL6/wcDCxMfGyMrLzc7P0dLT1dbX2Nna3N3e3+Di4+Tl5ufp6urr7O3u7/Dx8vPz9fb3+Pn5+/z9/v8AAQ==')
      audio.volume = 0.3
      audio.play()
    } catch (error) {
      console.error('Error playing notification sound:', error)
    }
  }

  async function showBrowserNotification(notification) {
    // طلب الإذن إذا لم يتم منحه
    if ('Notification' in window && Notification.permission === 'default') {
      await Notification.requestPermission()
    }

    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title || 'إشعار جديد', {
        body: notification.message || notification.body,
        icon: notification.icon || '/logo.png',
        tag: notification.id || Date.now().toString(),
        requireInteraction: false
      })
    }
  }

  function toggleAudio() {
    audioEnabled.value = !audioEnabled.value
  }

  return {
    notifications,
    unreadCount,
    audioEnabled,
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAll,
    toggleAudio
  }
}
