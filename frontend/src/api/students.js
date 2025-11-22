import apiClient from './client'

export const getAll = (params = {}) => {
  return apiClient.post('', {
    action: 'get_students',
    ...params
  })
}

export const getOne = (id) => {
  return apiClient.post('', {
    action: 'get_student',
    id
  })
}

export const create = (data) => {
  return apiClient.post('', {
    action: 'create_student',
    ...data
  })
}

export const update = (id, data) => {
  return apiClient.post('', {
    action: 'update_student',
    id,
    ...data
  })
}

export const remove = (id) => {
  return apiClient.post('', {
    action: 'delete_student',
    id
  })
}
