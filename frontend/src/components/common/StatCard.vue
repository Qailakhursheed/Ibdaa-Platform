<template>
  <div class="card" :class="`border-l-4 border-${color}-500 hover:shadow-lg transition-shadow`">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm text-gray-600 mb-1">{{ title }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ value }}</p>
        
        <div v-if="trend" class="flex items-center mt-2 text-sm">
          <span :class="trend > 0 ? 'text-green-600' : 'text-red-600'">
            {{ trend > 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
          </span>
          <span class="text-gray-500 mr-1">عن الشهر الماضي</span>
        </div>
      </div>
      
      <div :class="`w-16 h-16 rounded-full bg-${color}-100 flex items-center justify-center`">
        <svg class="w-8 h-8" :class="`text-${color}-600`" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: String,
    default: 'M13 10V3L4 14h7v7l9-11h-7z' // Lightning icon
  },
  color: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'yellow', 'purple', 'red'].includes(value)
  },
  trend: {
    type: Number,
    default: null
  }
})
</script>
