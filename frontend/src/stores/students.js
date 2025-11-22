import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as studentsApi from '@/api/students'

export const useStudentsStore = defineStore('students', () => {
  // State
  const students = ref([])
  const currentStudent = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 10,
    total: 0,
    totalPages: 1
  })

  // Actions
  async function fetchAll(params = {}) {
    try {
      loading.value = true
      error.value = null
      
      const response = await studentsApi.getAll(params)
      
      if (response.data.success) {
        students.value = response.data.students || []
        if (response.data.pagination) {
          pagination.value = response.data.pagination
        }
      } else {
        error.value = response.data.message || 'فشل تحميل البيانات'
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ في الاتصال'
      console.error('Fetch students error:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    try {
      loading.value = true
      error.value = null
      
      const response = await studentsApi.getOne(id)
      
      if (response.data.success) {
        currentStudent.value = response.data.student
      } else {
        error.value = response.data.message || 'فشل تحميل البيانات'
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ في الاتصال'
      console.error('Fetch student error:', err)
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      loading.value = true
      error.value = null
      
      const response = await studentsApi.create(data)
      
      if (response.data.success) {
        // Reload list
        await fetchAll({ page: pagination.value.currentPage })
        return { success: true, message: response.data.message }
      } else {
        error.value = response.data.message || 'فشل إضافة الطالب'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ في الاتصال'
      console.error('Create student error:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function update(id, data) {
    try {
      loading.value = true
      error.value = null
      
      const response = await studentsApi.update(id, data)
      
      if (response.data.success) {
        // Reload list
        await fetchAll({ page: pagination.value.currentPage })
        return { success: true, message: response.data.message }
      } else {
        error.value = response.data.message || 'فشل تحديث الطالب'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ في الاتصال'
      console.error('Update student error:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function remove(id) {
    try {
      loading.value = true
      error.value = null
      
      const response = await studentsApi.remove(id)
      
      if (response.data.success) {
        // Reload list
        await fetchAll({ page: pagination.value.currentPage })
        return { success: true, message: response.data.message }
      } else {
        error.value = response.data.message || 'فشل حذف الطالب'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'حدث خطأ في الاتصال'
      console.error('Delete student error:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    students,
    currentStudent,
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
