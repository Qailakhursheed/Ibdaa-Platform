<template>
  <div class="flex items-start space-x-3 space-x-reverse p-3 hover:bg-gray-50 rounded-lg transition-colors">
    <div :class="`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-${typeColor}-100`">
      <svg class="w-5 h-5" :class="`text-${typeColor}-600`" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeIcon" />
      </svg>
    </div>
    
    <div class="flex-1 min-w-0">
      <p class="text-sm text-gray-800">{{ message }}</p>
      <p class="text-xs text-gray-500 mt-1">{{ formattedTime }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['student', 'course', 'exam', 'payment', 'system'].includes(value)
  },
  message: {
    type: String,
    required: true
  },
  timestamp: {
    type: String,
    required: true
  }
})

const typeConfig = {
  student: {
    color: 'blue',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
  },
  course: {
    color: 'green',
    icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
  },
  exam: {
    color: 'yellow',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
  },
  payment: {
    color: 'purple',
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
  },
  system: {
    color: 'red',
    icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
  }
}

const typeColor = computed(() => typeConfig[props.type]?.color || 'gray')
const typeIcon = computed(() => typeConfig[props.type]?.icon || '')

const formattedTime = computed(() => {
  // Simple time formatting - can be enhanced with a library like date-fns
  return props.timestamp
})
</script>
