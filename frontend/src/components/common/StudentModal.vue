<template>
  <Modal v-model="isOpen" :title="modalTitle" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <div class="space-y-4">
        <!-- Full Name -->
        <div>
          <label class="label">الاسم الكامل <span class="text-red-500">*</span></label>
          <input
            v-model="form.full_name"
            type="text"
            class="input-field"
            :class="{ 'border-red-500': errors.full_name }"
            required
          />
          <p v-if="errors.full_name" class="text-red-500 text-sm mt-1">{{ errors.full_name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="label">البريد الإلكتروني <span class="text-red-500">*</span></label>
          <input
            v-model="form.email"
            type="email"
            class="input-field"
            :class="{ 'border-red-500': errors.email }"
            required
          />
          <p v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</p>
        </div>

        <!-- Phone -->
        <div>
          <label class="label">رقم الهاتف <span class="text-red-500">*</span></label>
          <input
            v-model="form.phone"
            type="tel"
            class="input-field"
            :class="{ 'border-red-500': errors.phone }"
            placeholder="967xxxxxxxxx"
            required
          />
          <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
        </div>

        <!-- Date of Birth -->
        <div>
          <label class="label">تاريخ الميلاد</label>
          <input
            v-model="form.date_of_birth"
            type="date"
            class="input-field"
          />
        </div>

        <!-- Gender -->
        <div>
          <label class="label">الجنس</label>
          <select v-model="form.gender" class="input-field">
            <option value="">اختر</option>
            <option value="male">ذكر</option>
            <option value="female">أنثى</option>
          </select>
        </div>

        <!-- Address -->
        <div>
          <label class="label">العنوان</label>
          <textarea
            v-model="form.address"
            class="input-field"
            rows="3"
          ></textarea>
        </div>

        <!-- Status -->
        <div>
          <label class="label">الحالة</label>
          <select v-model="form.status" class="input-field">
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
            <option value="suspended">موقوف</option>
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
import { ref, watch, computed } from 'vue'
import Modal from './Modal.vue'
import { useStudentsStore } from '@/stores/students'

const props = defineProps({
  modelValue: Boolean,
  student: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const store = useStudentsStore()
const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const modalTitle = computed(() => props.student ? 'تعديل طالب' : 'إضافة طالب جديد')

const form = ref({
  full_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  gender: '',
  address: '',
  status: 'active'
})

const errors = ref({})
const loading = ref(false)

watch(() => props.student, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    full_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    address: '',
    status: 'active'
  }
  errors.value = {}
}

function validate() {
  errors.value = {}
  let isValid = true

  if (!form.value.full_name || form.value.full_name.trim().length < 3) {
    errors.value.full_name = 'الاسم يجب أن يكون 3 أحرف على الأقل'
    isValid = false
  }

  if (!form.value.email || !form.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    errors.value.email = 'البريد الإلكتروني غير صالح'
    isValid = false
  }

  if (!form.value.phone || !form.value.phone.match(/^967\d{9}$/)) {
    errors.value.phone = 'رقم الهاتف يجب أن يبدأ بـ 967 ويتكون من 12 رقم'
    isValid = false
  }

  return isValid
}

async function handleSubmit() {
  if (!validate()) return

  loading.value = true
  try {
    let success
    if (props.student?.id) {
      success = await store.update(props.student.id, form.value)
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
</script>
