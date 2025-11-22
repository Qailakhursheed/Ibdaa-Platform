<template>
  <Modal v-model="isOpen" :title="modalTitle" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <div class="space-y-4">
        <!-- Course Name -->
        <div>
          <label class="label">اسم الدورة <span class="text-red-500">*</span></label>
          <input
            v-model="form.course_name"
            type="text"
            class="input-field"
            :class="{ 'border-red-500': errors.course_name }"
            required
          />
          <p v-if="errors.course_name" class="text-red-500 text-sm mt-1">{{ errors.course_name }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="label">الوصف</label>
          <textarea
            v-model="form.description"
            class="input-field"
            rows="4"
          ></textarea>
        </div>

        <!-- Duration -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">المدة (بالأيام)</label>
            <input
              v-model.number="form.duration_days"
              type="number"
              class="input-field"
              min="1"
            />
          </div>

          <div>
            <label class="label">عدد الساعات</label>
            <input
              v-model.number="form.duration_hours"
              type="number"
              class="input-field"
              min="1"
            />
          </div>
        </div>

        <!-- Price -->
        <div>
          <label class="label">السعر (ريال يمني)</label>
          <input
            v-model.number="form.price"
            type="number"
            class="input-field"
            min="0"
          />
        </div>

        <!-- Trainer -->
        <div>
          <label class="label">المدرب</label>
          <select v-model="form.trainer_id" class="input-field">
            <option value="">اختر المدرب</option>
            <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
              {{ trainer.full_name }}
            </option>
          </select>
        </div>

        <!-- Start Date -->
        <div>
          <label class="label">تاريخ البداية</label>
          <input
            v-model="form.start_date"
            type="date"
            class="input-field"
          />
        </div>

        <!-- End Date -->
        <div>
          <label class="label">تاريخ النهاية</label>
          <input
            v-model="form.end_date"
            type="date"
            class="input-field"
          />
        </div>

        <!-- Max Students -->
        <div>
          <label class="label">الحد الأقصى للطلاب</label>
          <input
            v-model.number="form.max_students"
            type="number"
            class="input-field"
            min="1"
          />
        </div>

        <!-- Status -->
        <div>
          <label class="label">الحالة</label>
          <select v-model="form.status" class="input-field">
            <option value="pending">قيد الانتظار</option>
            <option value="active">نشط</option>
            <option value="completed">مكتمل</option>
            <option value="cancelled">ملغي</option>
          </select>
        </div>
      </div>
    </form>

    <template #footer>
      <button type="button" @click="handleClose" class="btn-secondary">
        إلغاء
      </button>
      <button
        type="button"
        @click="handleSubmit"
        :disabled="loading"
        class="btn-primary disabled:opacity-50"
      >
        {{ loading ? 'جاري الحفظ...' : 'حفظ' }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import Modal from './Modal.vue'
import { useCoursesStore } from '@/stores/courses'

const props = defineProps({
  modelValue: Boolean,
  course: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const store = useCoursesStore()
const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const modalTitle = computed(() => props.course ? 'تعديل دورة' : 'إضافة دورة جديدة')

const form = ref({
  course_name: '',
  description: '',
  duration_days: null,
  duration_hours: null,
  price: null,
  trainer_id: '',
  start_date: '',
  end_date: '',
  max_students: null,
  status: 'pending'
})

const errors = ref({})
const loading = ref(false)
const trainers = ref([])

watch(() => props.course, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    course_name: '',
    description: '',
    duration_days: null,
    duration_hours: null,
    price: null,
    trainer_id: '',
    start_date: '',
    end_date: '',
    max_students: null,
    status: 'pending'
  }
  errors.value = {}
}

function validate() {
  errors.value = {}
  let isValid = true

  if (!form.value.course_name || form.value.course_name.trim().length < 3) {
    errors.value.course_name = 'اسم الدورة يجب أن يكون 3 أحرف على الأقل'
    isValid = false
  }

  return isValid
}

async function handleSubmit() {
  if (!validate()) return

  loading.value = true
  try {
    let success
    if (props.course?.id) {
      success = await store.update(props.course.id, form.value)
    } else {
      success = await store.create(form.value)
    }

    if (success) {
      emit('saved')
      handleClose()
    } else {
      alert(store.error || 'فشلت العملية')
    }
  } catch (error) {
    alert('حدث خطأ غير متوقع')
  } finally {
    loading.value = false
  }
}

function handleClose() {
  resetForm()
  emit('update:modelValue', false)
}

onMounted(() => {
  // تحميل قائمة المدربين
  // trainers.value = await fetchTrainers()
})
</script>
