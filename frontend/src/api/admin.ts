import type {
  AdminDashboardData,
  AdminDownloadFile,
  AdminFooterLink,
  AdminFooterSection,
  AdminLoginRequest,
  AdminLoginResponse,
  AdminMenuItem,
  AdminNetworkTest,
  AdminNode,
  AdminRateLimitEvent,
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

  async forgotPassword(email: string): Promise<{ message: string }> {
    return adminRequest<{ message: string }>('/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email }),
    })
  },

  async resetPassword(data: { token: string; email: string; password: string; password_confirmation: string }): Promise<{ message: string }> {
    return adminRequest<{ message: string }>('/reset-password', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async testSmtp(): Promise<{ message: string; success: boolean }> {
    return adminRequest<{ message: string; success: boolean }>('/settings/smtp/test', { method: 'POST' })
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

  async purgeTests(params: { days?: number; status?: string } = {}): Promise<{ message: string; purged: number }> {
    return adminRequest<{ message: string; purged: number }>('/tests/purge', {
      method: 'POST',
      body: JSON.stringify(params),
    })
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

  // ── Menu Items ────────────────────────────────────────────

  async getMenuItems(): Promise<ApiResponse<AdminMenuItem[]>> {
    return adminRequest<ApiResponse<AdminMenuItem[]>>('/menu-items')
  },

  async createMenuItem(data: Partial<AdminMenuItem>): Promise<ApiResponse<AdminMenuItem>> {
    return adminRequest<ApiResponse<AdminMenuItem>>('/menu-items', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async updateMenuItem(id: number, data: Partial<AdminMenuItem>): Promise<ApiResponse<AdminMenuItem>> {
    return adminRequest<ApiResponse<AdminMenuItem>>(`/menu-items/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  },

  async deleteMenuItem(id: number): Promise<void> {
    await adminRequest(`/menu-items/${id}`, { method: 'DELETE' })
  },

  // ── Footer Sections & Links ───────────────────────────────

  async getFooter(): Promise<ApiResponse<AdminFooterSection[]>> {
    return adminRequest<ApiResponse<AdminFooterSection[]>>('/footer')
  },

  async createFooterSection(data: { title: string; sort_order?: number }): Promise<ApiResponse<AdminFooterSection>> {
    return adminRequest<ApiResponse<AdminFooterSection>>('/footer/sections', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async updateFooterSection(id: number, data: Partial<AdminFooterSection>): Promise<ApiResponse<AdminFooterSection>> {
    return adminRequest<ApiResponse<AdminFooterSection>>(`/footer/sections/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  },

  async deleteFooterSection(id: number): Promise<void> {
    await adminRequest(`/footer/sections/${id}`, { method: 'DELETE' })
  },

  async createFooterLink(data: Partial<AdminFooterLink>): Promise<ApiResponse<AdminFooterLink>> {
    return adminRequest<ApiResponse<AdminFooterLink>>('/footer/links', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  async updateFooterLink(id: number, data: Partial<AdminFooterLink>): Promise<ApiResponse<AdminFooterLink>> {
    return adminRequest<ApiResponse<AdminFooterLink>>(`/footer/links/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    })
  },

  async deleteFooterLink(id: number): Promise<void> {
    await adminRequest(`/footer/links/${id}`, { method: 'DELETE' })
  },

  // ── Media Upload ──────────────────────────────────────────

  async uploadMedia(file: File, folder = 'media'): Promise<ApiResponse<{ url: string; path: string; filename: string; size_bytes: number }>> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('folder', folder)

    const url = `${ADMIN_API}/media/upload`
    const token = getToken()
    const headers: HeadersInit = {
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    }

    const response = await fetch(url, { method: 'POST', headers, body: formData })
    if (!response.ok) {
      const body = await response.json().catch(() => ({ message: 'Upload failed' }))
      throw new ApiRequestError(body.message || `HTTP ${response.status}`, response.status)
    }
    return response.json() as Promise<ApiResponse<{ url: string; path: string; filename: string; size_bytes: number }>>
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

  async updateUser(id: number, data: Partial<{ name: string; email: string; password: string; password_confirmation: string }>): Promise<ApiResponse<AdminUser>> {
    return adminRequest<ApiResponse<AdminUser>>(`/users/${id}`, { method: 'PUT', body: JSON.stringify(data) })
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

  // ── Rate Limits ─────────────────────────────────────────

  async getRateLimits(qs: string = ''): Promise<PaginatedResponse<AdminRateLimitEvent>> {
    return adminRequest<PaginatedResponse<AdminRateLimitEvent>>(`/logs/rate-limits?${qs}`)
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

  // ── DNS Resolution ─────────────────────────────────────────

  async resolveDns(hostname: string): Promise<ApiResponse<{ hostname: string; ipv4: string[]; ipv6: string[]; has_ipv4: boolean; has_ipv6: boolean }>> {
    return adminRequest<ApiResponse<{ hostname: string; ipv4: string[]; ipv6: string[]; has_ipv4: boolean; has_ipv6: boolean }>>('/dns/resolve', {
      method: 'POST',
      body: JSON.stringify({ hostname }),
    })
  },
}
