// ── API Response Types ────────────────────────────────────────

export interface ApiResponse<T> {
  data: T
}

export interface ApiError {
  message: string
  error: string
}

// ── Configuration ────────────────────────────────────────────

export interface WellKnownTarget {
  ip: string
  family: 'ipv4' | 'ipv6'
  port: number
  label: string
}

export interface ConnectionIpInfo {
  address: string | null
  working: boolean
  is_public: boolean
  reverse_dns: string | null
}

export interface ConnectionNetworkInfo {
  isp: string | null
  org: string | null
  asn: string | null
  as_name: string | null
  country: string | null
  country_code: string | null
  region: string | null
  city: string | null
  timezone: string | null
}

export interface ConnectionInfo {
  ipv4: ConnectionIpInfo
  ipv6: ConnectionIpInfo
  ip_version: string | null
  network: ConnectionNetworkInfo | null
  connection_note: string | null
  detected_at: string
}

export interface FooterLink {
  label: string
  url: string
  external: boolean
}

export interface FooterSection {
  title: string
  links: FooterLink[]
}

export interface FooterLinksConfig {
  sections: FooterSection[]
}

export interface MenuItemConfig {
  label: string
  url: string
  open_new_tab: boolean
}

export interface AppConfig {
  site_name: string
  organization: string
  test_types: TestType[]
  ip_families: IpFamily[]
  supported_features: Record<string, boolean>
  download_sizes: string[]
  well_known_targets: WellKnownTarget[]
  site_logo: string
  site_title: string
  meta_description: string
  og_image: string
  favicon: string
  footer_description: string
  footer_links: FooterLinksConfig | null
  copyright_text: string
  menu_items: MenuItemConfig[]
  email: string
  asn: string
  abuse_contact: string
  [key: string]: unknown
}

// ── Nodes ────────────────────────────────────────────────────

export interface Node {
  id: string          // slug
  uuid: string
  name: string
  hostname: string | null
  city: string
  country_code: string
  provider: string
  asn: string
  ipv4: string
  ipv6: string
  ipv4_enabled: boolean
  ipv6_enabled: boolean
  latitude: number
  longitude: number
  uplink_mbps: number
  status: NodeStatus
  location: string
  capabilities: string[]
  latency_enabled: boolean
  iperf3_enabled: boolean
  iperf3_port: number | null
  iperf3_status: string
  // Detail-only fields (from show endpoint)
  maintenance?: boolean
  agent_version?: string
  last_seen_at?: string | null
  last_ipv4_health_check_at?: string | null
  last_ipv6_health_check_at?: string | null
  last_iperf3_health_check_at?: string | null
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
  id: string           // node slug
  name: string
  location: string
  ipv4_latency_ms: number | null
  ipv6_latency_ms: number | null
  packet_loss_percent: number | null
  status: NodeStatus
  last_checked_at: string | null
}

export interface LatencyResponse {
  generated_at: string
  nodes: LatencyEntry[]
}

export interface LatencyProbeNodeResult {
  node_slug: string
  node_name: string
  status: 'pending' | 'measuring' | 'completed' | 'timeout' | 'blocked' | 'error'
  visitor_ip?: string
  ip_family?: string
  started_at?: string
  completed_at?: string
  latency_avg_ms?: number | null
  latency_min_ms?: number | null
  latency_max_ms?: number | null
  jitter_ms?: number | null
  packet_loss_percent?: number | null
  packets_sent?: number | null
  packets_received?: number | null
  error_message?: string | null
}

export interface LatencyProbeSession {
  session_id: string
  visitor_ip: string
  ip_family: string
  started_at: string
  node_count: number
  dispatched_count: number
}

export interface LatencyProbeResults {
  session_id: string
  visitor_ip: string
  ip_family: string
  started_at: string
  complete: boolean
  nodes: LatencyProbeNodeResult[]
}

// ── iPerf3 ────────────────────────────────────────────────────

export interface Iperf3Session {
  session_id: string
  node_slug: string
  node_name: string
  hostname: string
  port: number
  token: string
  expires_at: string
  iperf3_status: string
  ipv4_enabled: boolean
  ipv6_enabled: boolean
  commands: Iperf3Commands
}

export interface Iperf3Commands {
  upload: string
  download: string
  ipv6_upload: string | null
  ipv6_download: string | null
}

export interface Iperf3SessionStatus {
  session_id: string
  node_slug: string
  status: 'active' | 'expired' | 'error'
  expires_at: string
  remaining_seconds: number
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
  hostname: string | null
  city: string | null
  country_code: string | null
  provider: string | null
  asn: string | null
  ipv4: string | null
  ipv6: string | null
  ipv4_enabled: boolean
  ipv6_enabled: boolean
  latitude: number | null
  longitude: number | null
  uplink_mbps: number | null
  status: NodeStatus
  maintenance: boolean
  public: boolean
  sort_order: number
  download_host: string | null
  latency_enabled: boolean
  iperf3_enabled: boolean
  iperf3_port: number | null
  iperf3_status: string
  agent_version: string | null
  last_seen_at: string | null
  last_ipv4_health_check_at: string | null
  last_ipv6_health_check_at: string | null
  last_iperf3_health_check_at: string | null
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

export interface AdminMenuItem {
  id: number
  label: string
  url: string
  sort_order: number
  open_new_tab: boolean
  active: boolean
}

export interface AdminFooterSection {
  id: number
  title: string
  sort_order: number
  links: AdminFooterLink[]
}

export interface AdminFooterLink {
  id: number
  footer_section_id: number
  label: string
  url: string
  external: boolean
  sort_order: number
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
