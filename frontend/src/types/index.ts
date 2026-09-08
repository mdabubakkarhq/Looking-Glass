// ── API Response Types ────────────────────────────────────────

export interface ApiResponse<T> {
  data: T
}

export interface ApiError {
  message: string
  error: string
}

// ── Configuration ────────────────────────────────────────────

export interface AppConfig {
  site_name: string
  organization: string
  test_types: TestType[]
  ip_families: IpFamily[]
  supported_features: Record<string, boolean>
  download_sizes: string[]
  [key: string]: unknown
}

// ── Nodes ────────────────────────────────────────────────────

export interface Node {
  id: string          // slug
  uuid: string
  name: string
  city: string
  country_code: string
  provider: string
  asn: string
  ipv4: string
  ipv6: string
  latitude: number
  longitude: number
  uplink_mbps: number
  status: NodeStatus
  location: string
  capabilities: string[]
  // Detail-only fields (from show endpoint)
  maintenance?: boolean
  agent_version?: string
  last_seen_at?: string | null
}

export interface NodeDetail extends Omit<Node, 'capabilities'> {
  maintenance: boolean
  agent_version: string
  last_seen_at: string | null
  capabilities: NodeCapability[]
}

export interface NodeCapability {
  feature: string
  max_concurrent: number
  timeout_seconds: number
}

export type NodeStatus = 'online' | 'offline' | 'maintenance'

// ── Latency ──────────────────────────────────────────────────

export interface LatencyEntry {
  node_id: string
  node_name: string
  latency_ms: number | null
  status: NodeStatus
  last_checked: string
}

export interface LatencyResponse {
  data: LatencyEntry[]
  cached: boolean
  generated_at: string
}

// ── Tests ────────────────────────────────────────────────────

export type TestType = 'ping' | 'traceroute' | 'mtr' | 'dns'
export type IpFamily = 'auto' | 'ipv4' | 'ipv6'
export type TestStatus = 'pending' | 'running' | 'completed' | 'failed'

export interface TestCreateRequest {
  node_id: string
  test_type: TestType
  target: string
  ip_family?: IpFamily
}

export interface TestCreateResponse {
  id: string
  node_id: string
  test_type: TestType
  target: string
  ip_family: IpFamily
  status: TestStatus
  stream_url: string
  created_at: string
}

export interface TestResult {
  id: string
  node_id: string
  test_type: TestType
  target: string
  resolved_ip: string | null
  ip_family: IpFamily
  status: TestStatus
  started_at: string | null
  completed_at: string | null
  runtime_ms: number | null
  packet_loss: number | null
  latency_min: number | null
  latency_avg: number | null
  latency_max: number | null
  hop_count: number | null
  error_code: string | null
  error_message: string | null
  created_at: string
}

// ── SSE Events ───────────────────────────────────────────────

export interface SseEvent {
  event: SseEventType
  data: string
}

export type SseEventType =
  | 'test.started'
  | 'test.output'
  | 'test.progress'
  | 'test.completed'
  | 'test.failed'
  | 'error'

export interface SseOutputData {
  line: string
  timestamp: string
}

export interface SseCompletedData {
  runtime_ms: number
  packet_loss: number | null
  latency_min: number | null
  latency_avg: number | null
  latency_max: number | null
  hop_count: number | null
}

export interface SseFailedData {
  error_code: string
  error_message: string
}

// ── Downloads ────────────────────────────────────────────────

export interface DownloadFile {
  id: number
  node_id: string
  name: string
  size_bytes: number
  size_label: string
  url: string
}

// ── Network Info ─────────────────────────────────────────────

export interface NetworkInfo {
  asn: string | null
  network_name: string | null
  ipv4_prefixes: string[]
  ipv6_prefixes: string[]
  peeringdb_url: string | null
  internet_exchanges: InternetExchange[]
  peering_policy: string | null
  noc_contact: string | null
  abuse_contact: string | null
}

export interface InternetExchange {
  name: string
  url?: string
}

// ── System Status ────────────────────────────────────────────

export interface SystemStatus {
  controller: string
  database: string
  redis: string
  nodes: {
    total: number
    online: number
    offline: number
    maintenance: number
  }
  tests_today: number
  version: string
  generated_at: string
}

// ── Admin Types ─────────────────────────────────────────────

export interface AdminUser {
  id: number
  name: string
  email: string
  created_at: string
}

export interface AdminLoginRequest {
  email: string
  password: string
  device_name?: string
}

export interface AdminLoginResponse {
  user: AdminUser
  token: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface AdminDashboardData {
  nodes: {
    active: number
    offline: number
    maintenance: number
    total: number
  }
  tests: {
    today: number
    this_hour: number
    running: number
    failed_today: number
  }
  rate_limits: {
    today: number
  }
  top_targets: Array<{ target: string; test_count: number }>
  system: {
    database: string
    redis: string
    queue: string
  }
}

export interface AdminNode {
  id: number
  uuid: string
  slug: string
  name: string
  city: string | null
  country_code: string | null
  provider: string | null
  asn: string | null
  ipv4: string | null
  ipv6: string | null
  latitude: number | null
  longitude: number | null
  uplink_mbps: number | null
  status: NodeStatus
  maintenance: boolean
  public: boolean
  sort_order: number
  download_host: string | null
  agent_version: string | null
  last_seen_at: string | null
  created_at: string
  updated_at: string
  capabilities?: AdminNodeCapability[]
  credentials?: NodeCredential | null
  latest_heartbeat?: NodeHeartbeat | null
}

export interface AdminNodeCapability {
  id: number
  node_id: number
  feature: string
  enabled: boolean
  max_concurrent: number
  timeout_seconds: number
}

export interface NodeCredential {
  id: number
  node_id: number
  node_key_id: string
  agent_ip: string | null
  active: boolean
}

export interface NodeHeartbeat {
  id: number
  node_id: number
  agent_version: string
  hostname: string
  os: string
  cpu_usage_percent: number
  memory_usage_percent: number
  disk_usage_percent: number
  active_tests: number
  uptime_seconds: number
  load_average: string | null
  sent_at: string
  created_at: string
}

export interface AdminNetworkTest {
  id: number
  uuid: string
  node_id: number
  test_type: TestType
  target: string
  resolved_ip: string | null
  ip_family: IpFamily
  status: TestStatus
  visitor_hash: string
  started_at: string | null
  completed_at: string | null
  runtime_ms: number | null
  packet_loss: number | null
  latency_min: number | null
  latency_avg: number | null
  latency_max: number | null
  hop_count: number | null
  error_code: string | null
  error_message: string | null
  created_at: string
  node?: { id: number; slug: string; name: string }
}

export interface AdminSetting {
  id: number
  group: string
  key: string
  value: string | null
  type: string
  label: string | null
  description: string | null
}

export interface AdminDownloadFile {
  id: number
  node_id: number
  name: string
  filename: string
  size_bytes: number
  size_label: string
  url: string
  enabled: boolean
  sort_order: number
  created_at: string
  node?: { id: number; slug: string; name: string }
}

export interface AdminApiKey {
  id: number
  user_id: number
  name: string
  abilities: string[] | null
  rate_limit_per_minute: number | null
  expires_at: string | null
  last_used_at: string | null
  created_at: string
  user?: { id: number; name: string; email: string }
}

export interface AdminSecurityEvent {
  id: number
  node_id: number | null
  event_type: string
  severity: string
  description: string
  metadata: Record<string, unknown> | null
  created_at: string
  node?: { id: number; slug: string; name: string }
}

export interface AdminRateLimitEvent {
  id: number
  visitor_hash: string
  endpoint: string
  reason: string
  node_id: number | null
  created_at: string
  node?: { id: number; slug: string; name: string }
}

export interface SystemInfo {
  version: string
  php_version: string
  laravel_version: string
  environment: string
  debug_mode: boolean
  timezone: string
  database_driver: string
  queue_connection: string
  cache_driver: string
  nodes_total: number
  tests_total: number
  last_update: unknown
}
