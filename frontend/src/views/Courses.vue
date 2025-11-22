<template>
  <AppLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-semibold text-gray-800">قائمة الدورات</h2>
          <p class="text-sm text-gray-600 mt-1">إدارة وعرض جميع الدورات التدريبية</p>
        </div>
        <button class="btn-primary" @click="openAdd">
          <span class="text-lg mr-2">+</span>
          إضافة دورة
        </button>
      </div>

      <!-- Cards Grid -->
      <div v-if="store.loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        <p class="text-gray-600 mt-4">جاري التحميل...</p>
      </div>

      <div v-else-if="!store.loading && courses.length === 0" class="card text-center py-12">
        <p class="text-gray-600 text-lg">لا توجد دورات</p>
        <button @click="openAdd" class="btn-primary mt-4">إضافة دورة جديدة</button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="course in courses"
          :key="course.id"
          class="card hover:shadow-lg transition-shadow cursor-pointer"
        >
          <!-- Course Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-800 mb-1">
                {{ course.course_name || course.name }}
              </h3>
              <p class="text-sm text-gray-600 line-clamp-2">
                {{ course.description || 'لا يوجد وصف' }}
              </p>
            </div>
            <span
              :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                course.status === 'active'
                  ? 'bg-green-100 text-green-700'
                  : course.status === 'completed'
                  ? 'bg-blue-100 text-blue-700'
                  : course.status === 'cancelled'
                  ? 'bg-red-100 text-red-700'
                  : 'bg-yellow-100 text-yellow-700'
              ]"
            >
              {{ getCourseStatusLabel(course.status) }}
            </span>
          </div>

          <!-- Course Details -->
          <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
              <span class="ml-2">👨‍🏫</span>
              <span>المدرب: {{ course.trainer_name || 'غير محدد' }}</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <span class="ml-2">⏱️</span>
              <span>المدة: {{ course.duration_days || 0 }} يوم</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <span class="ml-2">👥</span>
              <span>الطلاب: {{ course.enrolled_count || 0 }} / {{ course.max_students || 0 }}</span>
            </div>
            <div class="flex items-center text-sm text-gray-600">
              <span class="ml-2">💰</span>
              <span>السعر: {{ formatPrice(course.price) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 pt-4 border-t">
            <button
              @click="edit(course)"
              class="flex-1 px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
            >
              تعديل
            </button>
            <button
              @click="viewDetails(course)"
              class="flex-1 px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
            >
              عرض
            </button>
            <button
              @click="remove(course.id)"
              class="px-3 py-2 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
            >
              حذف
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="courses.length > 0 && store.pagination.total > store.pagination.perPage"
        :current-page="store.pagination.currentPage"
        :total-pages="store.pagination.totalPages"
        :total="store.pagination.total"
        :per-page="store.pagination.perPage"
        @page-change="handlePageChange"
      />
    </div>

    <!-- Course Modal -->
    <CourseModal
      v-model="showModal"
      :course="selectedCourse"
      @saved="handleSaved"
    />
  </AppLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import Pagination from '@/components/common/Pagination.vue'
import CourseModal from '@/components/common/CourseModal.vue'
import { useCoursesStore } from '@/stores/courses'

const store = useCoursesStore()
const courses = computed(() => store.courses)

const showModal = ref(false)
const selectedCourse = ref(null)

async function loadCourses(page = 1) {
  await store.fetchAll({
    page,
    per_page: store.pagination.perPage
  })
}

function openAdd() {
  selectedCourse.value = null
  showModal.value = true
}

function edit(course) {
  selectedCourse.value = { ...course }
  showModal.value = true
}

function viewDetails(course) {
  // سيتم تنفيذ صفحة تفاصيل الدورة لاحقاً
  alert(`عرض تفاصيل: ${course.course_name || course.name}`)
}

async function remove(id) {
  if (!confirm('هل أنت متأكد من حذف هذه الدورة؟')) return
  
  const success = await store.remove(id)
  if (success) {
    loadCourses(store.pagination.currentPage)
  }
}

function handleSaved() {
  loadCourses(store.pagination.currentPage)
}

function handlePageChange(page) {
  loadCourses(page)
}

function getCourseStatusLabel(status) {
  const labels = {
    pending: 'قيد الانتظار',
    active: 'نشط',
    completed: 'مكتمل',
    cancelled: 'ملغي'
  }
  return labels[status] || status
}

function formatPrice(price) {
  if (!price) return 'مجاني'
  return new Intl.NumberFormat('ar-YE', {
    style: 'currency',
    currency: 'YER'
  }).format(price)
}

onMounted(() => {
  loadCourses()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
