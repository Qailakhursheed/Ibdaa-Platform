import { defineStore } from 'pinia'
import { ref } from 'vue'
import { coursesApi } from '@/api/courses'

export const useCoursesStore = defineStore('courses', () => {
  // State
  const courses = ref([])
  const currentCourse = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 20,
    total: 0,
    totalPages: 0
  })

  // Actions
  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    
    try {
      const response = await coursesApi.getAll(params)
      
      if (response.success) {
        courses.value = response.courses || []
        
        if (response.pagination) {
          pagination.value = response.pagination
        }
        
        return courses.value
      } else {
        error.value = response.message || 'فشل جلب البيانات'
        return []
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ أثناء جلب البيانات'
      return []
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    error.value = null
    
    try {
      const response = await coursesApi.getOne(id)
      
      if (response.success) {
        currentCourse.value = response.course
        return currentCourse.value
      } else {
        error.value = response.message
        return null
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ'
      return null
    } finally {
      loading.value = false
    }
  }

  async function create(courseData) {
    loading.value = true
    error.value = null
    
    try {
      const response = await coursesApi.create(courseData)
      
      if (response.success) {
        courses.value.unshift(response.course)
        return true
      } else {
        error.value = response.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ أثناء الإضافة'
      return false
    } finally {
      loading.value = false
    }
  }

  async function update(id, courseData) {
    loading.value = true
    error.value = null
    
    try {
      const response = await coursesApi.update(id, courseData)
      
      if (response.success) {
        const index = courses.value.findIndex(c => c.id === id)
        if (index !== -1) {
          courses.value[index] = response.course
        }
        return true
      } else {
        error.value = response.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ أثناء التحديث'
      return false
    } finally {
      loading.value = false
    }
  }

  async function remove(id) {
    loading.value = true
    error.value = null
    
    try {
      const response = await coursesApi.delete(id)
      
      if (response.success) {
        courses.value = courses.value.filter(c => c.id !== id)
        return true
      } else {
        error.value = response.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ أثناء الحذف'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    courses,
    currentCourse,
    loading,
    error,
    pagination,
    // Actions
    fetchAll,
    fetchOne,
    create,
    update,
    remove
  }
})
