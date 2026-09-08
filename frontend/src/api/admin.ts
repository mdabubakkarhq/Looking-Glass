import type {
  AdminApiKey,
  AdminDashboardData,
  AdminDownloadFile,
  AdminLoginRequest,
  AdminLoginResponse,
  AdminNetworkTest,
  AdminNode,
  AdminSecurityEvent,
  AdminSetting,
  AdminUser,
  ApiResponse,
  PaginatedResponse,
  SystemInfo,
} from '@/types'
import { ApiRequestError } from '@/api/client'

const ADMIN_API = '/api/v1/admin'

function getToken(): string | null {
  return localStorage.getItem('admin_token')
}

async function adminRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const url = `${ADMIN_API}${path}`
  const token = getToken()

  const headers: HeadersInit = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const response = await fetch(url, { ...options, headers })

  if (response.status === 401) {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_user')
    window.location.href = '/admin/login'
    throw new ApiRequestError('Session expired', 401)
  }

  if (!response.ok) {
    const body = await response.json().catch(() => ({ message: 'Unknown error' }))
    throw new ApiRequestError(body.message || `HTTP ${response.status}`, response.status, body.error)
  }

  return response.json() as Promise<T>
}

// ── Auth ──────────────────────────────────────────────────────

export const adminApi = {
  async login(data: AdminLoginRequest): Promise<ApiResponse<AdminLoginResponse>> {
    const result = await adminRequest<ApiResponse<AdminLoginResponse>>('/login', {
      method: 'POST',
      body: JSON.stringify(data),
    })
    localStorage.setItem('admin_token', result.data.token)
    localStorage.setItem('admin_user', JSON.stringify(result.data.user))
    return result
  },

  async me(): Promise<ApiResponse<AdminUser>> {
    return adminRequest<ApiResponse<AdminUser>>('/me')
  },

  async logout(): Promise<void> {
    try {
      await adminRequest('/logout', { method: 'POST' })
    } finally {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
    }
  },

  isAuthenticated(): boolean {
    return !!getToken()
  },

  getStoredUser(): AdminUser | null {
    const raw = localStorage.getItem('admin_user')
    if (!raw) return null
    try { return JSON.parse(raw) as AdminUser } catch { return null }
  },

  // ── Dashboard ─────────────────────────────────────────────

  async getDashboard(): Promise<ApiResponse<AdminDashboardData>> {
    return adminRequest<ApiResponse<AdminDashboardData>>('/dashboard')
  },

  // ── Nodes ─────────────────────────────────────────────────

  async getNodes(page = 1, perPage = 25): Promise<PaginatedResponse<AdminNode>> {
    return adminRequest<PaginatedResponse<AdminNode>>(`/nodes?page=${page}&per_page=${perPage}`)
  },

  async getNode(id: number): Promise<ApiResponse<AdminNode>> {
    return adminRequest<ApiResponse<AdminNode>>(`/nodes/${id}`)
  },

  async createNode(data: Partial<AdminNode>): Promise<ApiResponse<AdminNode>> {
    return adminRequest<ApiResponse<AdminNode>>('/nodes', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async updateNode(id: number, data: Partial<AdminNode>): Promise<ApiResponse<AdminNode>> {
    return adminRequest<ApiResponse<AdminNode>>(`/nodes/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  },

  async deleteNode(id: number): Promise<void> {
    await adminRequest(`/nodes/${id}`, { method: 'DELETE' })
  },

  async generateNodeToken(id: number): Promise<ApiResponse<{ token: string; expires_at: string; node_id: string }>> {
    return adminRequest(`/nodes/${id}/generate-token`, { method: 'POST' })
  },

  async toggleNodeMaintenance(id: number): Promise<ApiResponse<{ maintenance: boolean; status: string }>> {
    return adminRequest(`/nodes/${id}/maintenance`, { method: 'POST' })
  },

  async rotateNodeCredentials(id: number): Promise<ApiResponse<{ node_key_id: string; message: string }>> {
    return adminRequest(`/nodes/${id}/rotate-credentials`, { method: 'POST' })
  },

  // ── Tests ─────────────────────────────────────────────────

  async getTests(params: { page?: number; per_page?: number; test_type?: string; status?: string; node_id?: string; target?: string } = {}): Promise<PaginatedResponse<AdminNetworkTest>> {
    const qs = new URLSearchParams()
    if (params.page) qs.set('page', String(params.page))
    if (params.per_page) qs.set('per_page', String(params.per_page))
    if (params.test_type) qs.set('test_type', params.test_type)
    if (params.status) qs.set('status', params.status)
    if (params.node_id) qs.set('node_id', params.node_id)
    if (params.target) qs.set('target', params.target)
    return adminRequest<PaginatedResponse<AdminNetworkTest>>(`/tests?${qs}`)
  },

  async deleteTest(uuid: string): Promise<void> {
    await adminRequest(`/tests/${uuid}`, { method: 'DELETE' })
  },

  // ── Settings ──────────────────────────────────────────────

  async getSettings(): Promise<ApiResponse<AdminSetting[]>> {
    return adminRequest<ApiResponse<AdminSetting[]>>('/settings')
  },

  async updateSettings(settings: Array<{ key: string; value: unknown; type?: string }>): Promise<void> {
    await adminRequest('/settings', {
      method: 'PUT',
      body: JSON.stringify({ settings }),
    })
  },

  // ── Downloads ─────────────────────────────────────────────

  async getDownloads(page = 1, perPage = 25): Promise<PaginatedResponse<AdminDownloadFile>> {
    return adminRequest<PaginatedResponse<AdminDownloadFile>>(`/downloads?page=${page}&per_page=${perPage}`)
  },

  async createDownload(data: Partial<AdminDownloadFile>): Promise<ApiResponse<AdminDownloadFile>> {
    return adminRequest<ApiResponse<AdminDownloadFile>>('/downloads', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async deleteDownload(id: number): Promise<void> {
    await adminRequest(`/downloads/${id}`, { method: 'DELETE' })
  },

  // ── Users ─────────────────────────────────────────────────

  async getUsers(page = 1, perPage = 25): Promise<PaginatedResponse<AdminUser>> {
    return adminRequest<PaginatedResponse<AdminUser>>(`/users?page=${page}&per_page=${perPage}`)
  },

  async createUser(data: { name: string; email: string; password: string; password_confirmation: string }): Promise<ApiResponse<AdminUser>> {
    return adminRequest<ApiResponse<AdminUser>>('/users', { method: 'POST', body: JSON.stringify(data) })
  },

  async deleteUser(id: number): Promise<void> {
    await adminRequest(`/users/${id}`, { method: 'DELETE' })
  },

  // ── Security Events ───────────────────────────────────────

  async getSecurityEvents(params: { page?: number; severity?: string } = {}): Promise<PaginatedResponse<AdminSecurityEvent>> {
    const qs = new URLSearchParams()
    if (params.page) qs.set('page', String(params.page))
    if (params.severity) qs.set('severity', params.severity)
    return adminRequest<PaginatedResponse<AdminSecurityEvent>>(`/security-events?${qs}`)
  },

  // ── System ────────────────────────────────────────────────

  async getSystemInfo(): Promise<ApiResponse<SystemInfo>> {
    return adminRequest<ApiResponse<SystemInfo>>('/system/info')
  },

  async getSystemHealth(): Promise<{ healthy: boolean; checks: Record<string, string> }> {
    return adminRequest('/system/health')
  },

  async runMigrations(): Promise<{ message: string; output: string }> {
    return adminRequest('/system/update', { method: 'POST' })
  },
}
