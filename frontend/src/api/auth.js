import apiClient from './client'

export const login = (email, password) => {
  return apiClient.post('', {
    action: 'login',
    email,
    password
  })
}

export const logout = () => {
  return apiClient.post('', {
    action: 'logout'
  })
}

export const getCurrentUser = () => {
  return apiClient.post('', {
    action: 'get_current_user'
  })
}

export const validateToken = () => {
  return apiClient.post('', {
    action: 'validate_token'
  })
}
