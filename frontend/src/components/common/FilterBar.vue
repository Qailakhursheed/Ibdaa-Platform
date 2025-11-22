<template>
  <div class="mb-4 p-4 bg-white rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Search -->
      <div>
        <input
          v-model="localFilters.search"
          type="text"
          placeholder="بحث..."
          class="input-field"
          @input="onSearchChange"
        />
      </div>

      <!-- Status Filter -->
      <div>
        <select v-model="localFilters.status" class="input-field" @change="applyFilters">
          <option value="">جميع الحالات</option>
          <option value="active">نشط</option>
          <option value="inactive">غير نشط</option>
          <option value="suspended">موقوف</option>
        </select>
      </div>

      <!-- Gender Filter -->
      <div>
        <select v-model="localFilters.gender" class="input-field" @change="applyFilters">
          <option value="">الجنس</option>
          <option value="male">ذكر</option>
          <option value="female">أنثى</option>
        </select>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button @click="applyFilters" class="btn-primary flex-1">
          تطبيق
        </button>
        <button @click="resetFilters" class="btn-secondary flex-1">
          إعادة تعيين
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:filters', 'apply'])

const localFilters = ref({
  search: '',
  status: '',
  gender: '',
  ...props.filters
})

let searchTimeout = null

function onSearchChange() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

function applyFilters() {
  emit('update:filters', { ...localFilters.value })
  emit('apply', { ...localFilters.value })
}

function resetFilters() {
  localFilters.value = {
    search: '',
    status: '',
    gender: ''
  }
  applyFilters()
}

watch(() => props.filters, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })
</script>
