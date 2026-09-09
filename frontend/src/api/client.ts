import type {
  AppConfig,
  ApiResponse,
  ConnectionInfo,
  DownloadFile,
  LatencyResponse,
  NetworkInfo,
  Node,
  NodeDetail,
  SystemStatus,
  TestCreateRequest,
  TestCreateResponse,
  TestResult,
  LatencyProbeSession,
  LatencyProbeResults,
  Iperf3Session,
  Iperf3SessionStatus,
} from '@/types'

const API_BASE = '/api/v1'

// ── Generic Fetch Wrapper ────────────────────────────────────

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const url = `${API_BASE}${path}`

  const headers: HeadersInit = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...options.headers,
  }

  const response = await fetch(url, {
    ...options,
    headers,
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({ message: 'Unknown error' }))
    throw new ApiRequestError(body.message || `HTTP ${response.status}`, response.status, body.error)
  }

  return response.json() as Promise<T>
}

export class ApiRequestError extends Error {
  constructor(
    message: string,
    public status: number,
    public code?: string,
  ) {
    super(message)
    this.name = 'ApiRequestError'
  }
}

// ── Public API ───────────────────────────────────────────────

export const api = {
  // Configuration
  getConfig(): Promise<AppConfig> {
    return request<AppConfig>('/config')
  },

  // Nodes
  getNodes(): Promise<ApiResponse<Node[]>> {
    return request<ApiResponse<Node[]>>('/nodes')
  },

  getNode(slug: string): Promise<ApiResponse<NodeDetail>> {
    return request<ApiResponse<NodeDetail>>(`/nodes/${encodeURIComponent(slug)}`)
  },

  getLatency(): Promise<LatencyResponse> {
    return request<LatencyResponse>('/nodes/latency')
  },

  // Tests
  createTest(data: TestCreateRequest): Promise<ApiResponse<TestCreateResponse>> {
    return request<ApiResponse<TestCreateResponse>>('/tests', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  getTest(uuid: string): Promise<ApiResponse<TestResult>> {
    return request<ApiResponse<TestResult>>(`/tests/${encodeURIComponent(uuid)}`)
  },

  getTestStreamUrl(uuid: string): string {
    return `${API_BASE}/tests/${encodeURIComponent(uuid)}/stream`
  },

  // Downloads
  getDownloads(): Promise<ApiResponse<DownloadFile[]>> {
    return request<ApiResponse<DownloadFile[]>>('/downloads')
  },

  // Network Info
  getNetworkInfo(): Promise<ApiResponse<NetworkInfo>> {
    return request<ApiResponse<NetworkInfo>>('/network')
  },

  // Connection Info
  getConnection(): Promise<ApiResponse<ConnectionInfo>> {
    return request<ApiResponse<ConnectionInfo>>('/connection')
  },

  // System Status
  getStatus(): Promise<ApiResponse<SystemStatus>> {
    return request<ApiResponse<SystemStatus>>('/status')
  },

  // Latency Probes
  startLatencyProbe(): Promise<ApiResponse<LatencyProbeSession>> {
    return request<ApiResponse<LatencyProbeSession>>('/latency/start', {
      method: 'POST',
    })
  },

  getLatencyProbeResults(sessionId: string): Promise<ApiResponse<LatencyProbeResults>> {
    return request<ApiResponse<LatencyProbeResults>>(`/latency/${encodeURIComponent(sessionId)}`)
  },

  // iPerf3
  createIperf3Session(nodeSlug: string): Promise<ApiResponse<Iperf3Session>> {
    return request<ApiResponse<Iperf3Session>>('/iperf3/session', {
      method: 'POST',
      body: JSON.stringify({ node_slug: nodeSlug }),
    })
  },

  getIperf3SessionStatus(sessionId: string): Promise<ApiResponse<Iperf3SessionStatus>> {
    return request<ApiResponse<Iperf3SessionStatus>>(`/iperf3/session/${encodeURIComponent(sessionId)}`)
  },
}
