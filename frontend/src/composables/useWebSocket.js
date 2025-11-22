import { ref, onMounted, onUnmounted } from 'vue'

export function useWebSocket(url) {
  const ws = ref(null)
  const isConnected = ref(false)
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 10
  const reconnectDelay = 3000
  const messageHandlers = ref([])
  let reconnectTimeout = null
  let pingInterval = null

  function connect(userId) {
    if (ws.value) {
      ws.value.close()
    }

    const wsUrl = url || import.meta.env.VITE_WS_URL || 'ws://localhost:8080'
    
    try {
      ws.value = new WebSocket(wsUrl)

      ws.value.onopen = () => {
        console.log('WebSocket connected')
        isConnected.value = true
        reconnectAttempts.value = 0

        // إرسال معرف المستخدم
        if (userId) {
          send({
            type: 'auth',
            user_id: userId
          })
        }

        // بدء ping للحفاظ على الاتصال
        startPing()
      }

      ws.value.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data)
          console.log('WebSocket message received:', data)

          // استدعاء جميع المعالجات المسجلة
          messageHandlers.value.forEach(handler => {
            handler(data)
          })
        } catch (error) {
          console.error('Error parsing WebSocket message:', error)
        }
      }

      ws.value.onerror = (error) => {
        console.error('WebSocket error:', error)
      }

      ws.value.onclose = () => {
        console.log('WebSocket disconnected')
        isConnected.value = false
        stopPing()

        // محاولة إعادة الاتصال
        if (reconnectAttempts.value < maxReconnectAttempts) {
          reconnectAttempts.value++
          console.log(`Reconnecting... (attempt ${reconnectAttempts.value})`)
          reconnectTimeout = setTimeout(() => {
            connect(userId)
          }, reconnectDelay)
        }
      }
    } catch (error) {
      console.error('Failed to create WebSocket connection:', error)
    }
  }

  function disconnect() {
    if (ws.value) {
      ws.value.close()
      ws.value = null
    }
    stopPing()
    if (reconnectTimeout) {
      clearTimeout(reconnectTimeout)
    }
  }

  function send(data) {
    if (ws.value && isConnected.value) {
      ws.value.send(JSON.stringify(data))
    } else {
      console.warn('WebSocket is not connected')
    }
  }

  function onMessage(handler) {
    messageHandlers.value.push(handler)
    
    // إرجاع دالة لإلغاء التسجيل
    return () => {
      const index = messageHandlers.value.indexOf(handler)
      if (index > -1) {
        messageHandlers.value.splice(index, 1)
      }
    }
  }

  function startPing() {
    pingInterval = setInterval(() => {
      send({ type: 'ping' })
    }, 30000) // كل 30 ثانية
  }

  function stopPing() {
    if (pingInterval) {
      clearInterval(pingInterval)
      pingInterval = null
    }
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    ws,
    isConnected,
    connect,
    disconnect,
    send,
    onMessage
  }
}
