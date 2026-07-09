import axios from 'axios'

const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

// Attach the bearer token to every request.
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// On 401, drop the token and bounce to login.
http.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401 && !location.pathname.startsWith('/login')) {
      localStorage.removeItem('token')
      location.assign('/login')
    }
    return Promise.reject(error)
  },
)

export default http
