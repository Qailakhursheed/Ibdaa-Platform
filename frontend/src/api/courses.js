import apiClient from './client'

export const getAll = (params = {}) => {
  return apiClient.post('', {
    action: 'get_courses',
    ...params
  })
}

export const getOne = (id) => {
  return apiClient.post('', {
    action: 'get_course',
    id
  })
}

export const create = (data) => {
  return apiClient.post('', {
    action: 'create_course',
    ...data
  })
}

export const update = (id, data) => {
  return apiClient.post('', {
    action: 'update_course',
    id,
    ...data
  })
}

export const remove = (id) => {
  return apiClient.post('', {
    action: 'delete_course',
    id
  })
}
