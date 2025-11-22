<template>
  <AppLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-semibold text-gray-800">قائمة الطلاب</h2>
          <p class="text-sm text-gray-600 mt-1">إدارة وعرض جميع الطلاب</p>
        </div>
        <button class="btn-primary" @click="openAdd">
          <span class="text-lg mr-2">+</span>
          إضافة طالب
        </button>
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:filters="filters"
        @apply="handleFilterApply"
      />

      <!-- Table -->
      <div class="card">
        <!-- Loading State -->
        <div v-if="store.loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
          <p class="text-gray-600 mt-4">جاري التحميل...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!store.loading && students.length === 0" class="text-center py-12">
          <p class="text-gray-600 text-lg">لا توجد بيانات</p>
          <button @click="openAdd" class="btn-primary mt-4">إضافة طالب جديد</button>
        </div>

        <!-- Data Table -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
              <tr class="text-right text-sm text-gray-600">
                <th class="px-4 py-3 font-semibold">الرقم</th>
                <th class="px-4 py-3 font-semibold">الاسم</th>
                <th class="px-4 py-3 font-semibold">البريد الإلكتروني</th>
                <th class="px-4 py-3 font-semibold">الهاتف</th>
                <th class="px-4 py-3 font-semibold">الحالة</th>
                <th class="px-4 py-3 font-semibold">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="student in students"
                :key="student.id"
                class="border-t hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-700">{{ student.id }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                      <span class="text-primary-600 font-semibold text-sm">
                        {{ (student.full_name || student.name || '?').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <span class="font-medium text-gray-800">{{ student.full_name || student.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ student.email }}</td>
                <td class="px-4 py-3 text-gray-700">{{ student.phone }}</td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      student.status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : student.status === 'inactive'
                        ? 'bg-gray-100 text-gray-700'
                        : 'bg-red-100 text-red-700'
                    ]"
                  >
                    {{ getStatusLabel(student.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <button
                      @click="edit(student)"
                      class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                    >
                      تعديل
                    </button>
                    <button
                      @click="remove(student.id)"
                      class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                    >
                      حذف
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <Pagination
            v-if="store.pagination.total > store.pagination.perPage"
            :current-page="store.pagination.currentPage"
            :total-pages="store.pagination.totalPages"
            :total="store.pagination.total"
            :per-page="store.pagination.perPage"
            @page-change="handlePageChange"
          />
        </div>
      </div>
    </div>

    <!-- Student Modal -->
    <StudentModal
      v-model="showModal"
      :student="selectedStudent"
      @saved="handleSaved"
    />
  </AppLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import Pagination from '@/components/common/Pagination.vue'
import StudentModal from '@/components/common/StudentModal.vue'
import { useStudentsStore } from '@/stores/students'

const store = useStudentsStore()
const students = computed(() => store.students)

const showModal = ref(false)
const selectedStudent = ref(null)
const filters = ref({
  search: '',
  status: '',
  gender: ''
})

async function loadStudents(page = 1) {
  await store.fetchAll({
    page,
    per_page: store.pagination.perPage,
    ...filters.value
  })
}

function openAdd() {
  selectedStudent.value = null
  showModal.value = true
}

function edit(student) {
  selectedStudent.value = { ...student }
  showModal.value = true
}

async function remove(id) {
  if (!confirm('هل أنت متأكد من حذف هذا الطالب؟')) return
  
  const success = await store.remove(id)
  if (success) {
    // إعادة التحميل بعد الحذف
    loadStudents(store.pagination.currentPage)
  }
}

function handleSaved() {
  loadStudents(store.pagination.currentPage)
}

function handleFilterApply() {
  loadStudents(1) // العودة للصفحة الأولى عند تطبيق الفلترة
}

function handlePageChange(page) {
  loadStudents(page)
}

function getStatusLabel(status) {
  const labels = {
    active: 'نشط',
    inactive: 'غير نشط',
    suspended: 'موقوف'
  }
  return labels[status] || status
}

onMounted(() => {
  loadStudents()
})
</script>

<style scoped>
.table-auto th,
.table-auto td {
  text-align: right;
}
</style>
<template>
  <div class="min-h-screen bg-gray-100">
    <AppLayout>
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">إدارة الطلاب</h1>
            <p class="text-gray-600 mt-1">عرض وإدارة جميع الطلاب المسجلين</p>
          </div>
          
          <button
            @click="openAddModal"
            class="btn-primary flex items-center gap-2"
          >
            <span>➕</span>
            <span>إضافة طالب جديد</span>
          </button>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input
              v-model="filters.search"
              type="text"
              placeholder="بحث بالاسم أو البريد..."
              class="input-field"
              @input="handleSearch"
            />
            
            <select v-model="filters.status" class="input-field">
              <option value="">كل الحالات</option>
              <option value="active">نشط</option>
              <option value="inactive">غير نشط</option>
            </select>
            
            <select v-model="filters.gender" class="input-field">
              <option value="">الجنس</option>
              <option value="male">ذكر</option>
              <option value="female">أنثى</option>
            </select>
            
            <button @click="resetFilters" class="btn-secondary">
              مسح الفلاتر
            </button>
          </div>
        </div>

        <!-- Students Table -->
        <div class="card">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            <p class="mt-4 text-gray-600">جاري التحميل...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="text-center py-12">
            <p class="text-red-600">{{ error }}</p>
            <button @click="loadStudents" class="btn-primary mt-4">
              إعادة المحاولة
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="students.length === 0" class="text-center py-12">
            <p class="text-gray-500 text-lg">لا يوجد طلاب</p>
          </div>

          <!-- Table -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الرقم</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">البريد الإلكتروني</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الهاتف</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ student.id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold">
                          {{ student.full_name?.charAt(0) }}
                        </div>
                      </div>
                      <div class="mr-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ student.full_name }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ student.email }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ student.phone || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                      student.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ student.status === 'active' ? 'نشط' : 'غير نشط' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      @click="editStudent(student)"
                      class="text-primary-600 hover:text-primary-900 ml-4"
                    >
                      ✏️ تعديل
                    </button>
                    <button
                      @click="deleteStudent(student)"
                      class="text-red-600 hover:text-red-900"
                    >
                      🗑️ حذف
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="students.length > 0" class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                عرض {{ students.length }} من إجمالي {{ pagination.total }} طالب
              </div>
              <div class="flex gap-2">
                <button
                  @click="prevPage"
                  :disabled="pagination.currentPage === 1"
                  class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  السابق
                </button>
                <button
                  @click="nextPage"
                  :disabled="pagination.currentPage >= pagination.totalPages"
                  class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  التالي
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useStudentsStore } from '@/stores/students'
import AppLayout from '@/components/layout/AppLayout.vue'

const studentsStore = useStudentsStore()

const filters = ref({
  search: '',
  status: '',
  gender: ''
})

const students = computed(() => studentsStore.students)
const loading = computed(() => studentsStore.loading)
const error = computed(() => studentsStore.error)
const pagination = computed(() => studentsStore.pagination)

async function loadStudents() {
  await studentsStore.fetchAll({
    page: pagination.value.currentPage,
    ...filters.value
  })
}

function handleSearch() {
  // Debounce search
  loadStudents()
}

function resetFilters() {
  filters.value = {
    search: '',
    status: '',
    gender: ''
  }
  loadStudents()
}

function openAddModal() {
  // سيتم إضافة Modal لاحقاً
  alert('سيتم إضافة نموذج الإضافة قريباً')
}

function editStudent(student) {
  // سيتم إضافة Modal لاحقاً
  alert(`تعديل: ${student.full_name}`)
}

async function deleteStudent(student) {
  if (confirm(`هل أنت متأكد من حذف الطالب: ${student.full_name}؟`)) {
    const success = await studentsStore.remove(student.id)
    if (success) {
      alert('تم الحذف بنجاح')
    } else {
      alert('فشل الحذف: ' + studentsStore.error)
    }
  }
}

function prevPage() {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    loadStudents()
  }
}

function nextPage() {
  if (pagination.value.currentPage < pagination.value.totalPages) {
    pagination.value.currentPage++
    loadStudents()
  }
}

onMounted(() => {
  loadStudents()
})
</script>
