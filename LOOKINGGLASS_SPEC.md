# Open Looking Glass

## Technical Specification

Version: 1.0 Draft  
Status: Ready for implementation planning  
Primary goal: Build a modern, open-source, multi-location Looking Glass platform with a Vue.js frontend, Laravel controller/API, lightweight remote node agent, secure network diagnostics, and one-click installation.

---

## 1. Project Vision

Open Looking Glass is a self-hosted network diagnostics platform that allows network operators, hosting companies, ISPs, datacenters, VPS providers, and infrastructure teams to publish a secure public Looking Glass service from one or many locations.

The platform must be easy enough to deploy through a one-command or one-click installer while keeping all source code publicly available on GitHub.

Primary supported features:

- Multi-location Looking Glass nodes
- Ping
- Traceroute
- MTR
- DNS lookup
- IPv4 and IPv6 support
- Automatic latency checks against all configured nodes when a visitor opens the site
- Multi-location comparison
- File download speed tests
- Live streaming command output
- Public network information
- Node health/status
- Secure remote execution through strict predefined actions
- Centralized configuration
- API support
- One-click deployment
- Public GitHub source code
- Upgrade and rollback support

The platform must never expose arbitrary shell access to visitors.

---

## 2. Recommended Technology Stack

### Frontend

- Vue 3
- TypeScript
- Vite
- Pinia
- Vue Router
- Tailwind CSS or a minimal custom component layer
- xterm.js or equivalent terminal-style output renderer
- Native Fetch API or Axios
- Server-Sent Events for live test output

### Central Controller

- Laravel 12 or latest stable supported Laravel release at project start
- PHP 8.3+
- Redis
- PostgreSQL preferred
- MySQL/MariaDB supported where practical
- Laravel Queue
- Laravel Scheduler
- Laravel Rate Limiter
- Laravel Sanctum for authenticated admin/API access

### Remote Node Agent

Preferred implementation:

- Go

Reasons:

- Single compiled binary
- Low memory footprint
- Easy systemd deployment
- Reliable process spawning
- Good concurrency handling
- Simple cross-distribution packaging
- Strong support for HTTP, SSE, WebSocket, mTLS, and cryptographic signing

### Reverse Proxy

Supported:

- NGINX
- Caddy

NGINX should be the default production deployment path.

### Containerization

Supported:

- Docker
- Docker Compose

Container deployment must be optional. Native installation must also be supported.

---

## 3. High-Level Architecture

```text
                    Internet Visitor
                          |
                          v
                 CDN / WAF / Reverse Proxy
                          |
                          v
                  Vue 3 Frontend SPA
                          |
                          v
                  Laravel Controller API
                          |
              +-----------+-----------+
              |                       |
              v                       v
            Redis                  Database
              |
              v
          Job / Cache Layer
              |
     +--------+--------+--------+
     |                 |        |
     v                 v        v
 Dhaka Agent       Singapore   Frankfurt
     |                 |        |
     v                 v        v
 ping/mtr/trace     system tools on each node
```

The browser must never communicate directly with unrestricted system commands.

The controller sends only predefined test instructions to remote agents.

---

## 4. Deployment Modes

The project must support two main deployment modes.

### 4.1 Single-Node Mode

For small providers or personal use.

All components can run on one server:

```text
Vue Frontend
Laravel Controller
Redis
Database
LG Agent
NGINX
```

This should be the easiest installation mode.

### 4.2 Distributed Multi-Node Mode

For providers operating multiple datacenters or POPs.

```text
Central Controller
      |
      +-- Dhaka Agent
      +-- Singapore Agent
      +-- Frankfurt Agent
      +-- Los Angeles Agent
      +-- Additional Nodes
```

Each remote node runs only the lightweight agent and the required network tools.

---

## 5. One-Click / One-Command Installation

One-click installation is a core project requirement.

The GitHub repository must include an installer that can deploy the system with minimal user interaction.

Recommended primary install method:

```bash
curl -fsSL https://raw.githubusercontent.com/<ORG>/<REPO>/main/install.sh | sudo bash
```

The installer should launch an interactive setup wizard when required.

Example flow:

```text
Open Looking Glass Installer

1. Installation Type
   [1] Full Controller + Local Agent
   [2] Controller Only
   [3] Remote Agent Only
   [4] Docker Compose

2. Domain
   lg.example.com

3. SSL
   [1] Let's Encrypt
   [2] Existing certificate
   [3] No SSL / reverse proxy managed externally

4. Database
   [1] PostgreSQL
   [2] MariaDB

5. Redis
   [1] Install locally
   [2] External Redis

6. Create Admin Account

7. Finish Installation
```

The installer must automate as much of the following as possible:

- OS compatibility validation
- Package installation
- PHP installation
- PHP extensions
- Composer installation
- Node.js installation
- Frontend build
- Database installation/configuration
- Redis installation/configuration
- Laravel environment generation
- APP_KEY generation
- Database migrations
- Queue service
- Scheduler service
- NGINX configuration
- TLS setup
- Agent installation
- systemd services
- Required networking utilities
- Firewall recommendations
- Initial admin account
- Permission setup
- Health check

Supported operating systems for first release:

- Ubuntu 24.04 LTS
- Debian 12

Later:

- Ubuntu 26.04 LTS
- Debian 13
- AlmaLinux
- Rocky Linux

The installer must fail safely if the OS is unsupported.

---

## 6. Installer Files

Repository installation files should include:

```text
/install.sh
/install/
    common.sh
    controller.sh
    agent.sh
    nginx.sh
    database.sh
    redis.sh
    firewall.sh
    ssl.sh
    update.sh
    uninstall.sh
```

Installer requirements:

- Idempotent where possible
- Clear logs
- Non-destructive defaults
- Backup existing config before changes
- Confirmation before destructive actions
- Exit immediately on critical errors
- Verify each installed service
- Generate a post-install report

Example final installer output:

```text
Installation completed successfully.

Frontend:  https://lg.example.com
Admin:     https://lg.example.com/admin
API:       https://lg.example.com/api
Agent ID:  dhaka-01

Services:
- nginx: active
- php-fpm: active
- redis: active
- queue: active
- scheduler: active
- lg-agent: active

Save the following recovery token securely:
XXXXXXXXXXXXXXXX
```

---

## 7. GitHub Repository Structure

Recommended monorepo:

```text
open-looking-glass/
|
|-- frontend/
|   |-- src/
|   |-- public/
|   |-- tests/
|   `-- package.json
|
|-- controller/
|   |-- app/
|   |-- routes/
|   |-- database/
|   |-- tests/
|   `-- composer.json
|
|-- agent/
|   |-- cmd/
|   |-- internal/
|   |-- tests/
|   `-- go.mod
|
|-- install/
|-- docker/
|-- docs/
|-- scripts/
|-- examples/
|-- .github/
|   |-- workflows/
|   |-- ISSUE_TEMPLATE/
|   `-- PULL_REQUEST_TEMPLATE.md
|
|-- install.sh
|-- docker-compose.yml
|-- LICENSE
|-- SECURITY.md
|-- CONTRIBUTING.md
|-- CODE_OF_CONDUCT.md
|-- CHANGELOG.md
`-- README.md
```

---

## 8. Open-Source Distribution

All project source code should be publicly available through GitHub.

Recommended licensing options:

- Apache License 2.0
- MIT License

Apache-2.0 is recommended if explicit patent protection is desired.

Before release, confirm that no code copied from upstream projects introduces incompatible restrictions.

If existing Looking Glass projects are used as inspiration, perform a clean implementation unless their license permits direct code reuse under the selected license.

---

## 9. Frontend Application

The public frontend is a Vue 3 SPA.

Main navigation:

```text
Looking Glass
Locations
Downloads
Network
Peering
Status
API
About
```

### 9.1 Main Looking Glass Screen

Required controls:

- Location selector
- Target hostname/IP field
- IP family selector
  - Auto
  - IPv4
  - IPv6
- Test selector
  - Ping
  - Traceroute
  - MTR
  - DNS
- Run button
- Compare multiple locations button

### 9.2 Live Output

Results should stream into a terminal-style output panel.

Preferred transport:

- Server-Sent Events

Request flow:

```text
POST /api/v1/tests
```

Response:

```json
{
  "id": "test_01KABC...",
  "stream_url": "/api/v1/tests/test_01KABC.../stream"
}
```

Then:

```text
GET /api/v1/tests/{id}/stream
```

Possible events:

```text
event: started
event: output
event: statistics
event: complete
event: error
```

### 9.3 Result Summary

Ping should return structured statistics where possible:

- Packet loss
- Minimum latency
- Average latency
- Maximum latency
- Standard deviation where available

Traceroute:

- Hop count
- Hop addresses
- Hostnames
- Latency per hop

MTR:

- Loss percentage per hop
- Sent packets
- Last
- Average
- Best
- Worst
- Standard deviation

DNS:

- A records
- AAAA records
- MX records
- NS records
- TXT records
- PTR lookup option
- Resolver response time

---

## 10. Automatic Node Latency

Latency data for all nodes is shown inline in the left sidebar node selector, eliminating the need for a separate latency section.

When the homepage loads, the frontend fetches latency data for all nodes once via the `/api/v1/nodes/latency` endpoint (cached client-side for 15 seconds). Each node card in the sidebar displays:

- Node name and status badge
- Latency value (color-coded: green <20ms, emerald <50ms, yellow <100ms, orange <200ms, red ≥200ms)
- Location and provider
- IPv4 availability (green "v4" badge, or gray "v4 N/A" if not configured)
- IPv6 availability (blue "v6" badge, or gray "v6 N/A" if not configured)

Important implementation rule:

Browsers cannot perform true ICMP ping directly.

Therefore the public frontend must request latency values from the controller.

Recommended endpoint:

```text
GET /api/v1/nodes/latency
```

Suggested response:

```json
{
  "generated_at": "2026-09-07T12:00:00Z",
  "nodes": [
    {
      "id": "dhaka-01",
      "name": "Dhaka",
      "ipv4_latency_ms": 4.2,
      "ipv6_latency_ms": 5.1,
      "packet_loss_percent": 0,
      "status": "online",
      "last_checked_at": "2026-09-07T11:59:58Z"
    }
  ]
}
```

The controller should cache results.

Recommended behavior:

- Visitor opens page
- Vue fetches cached node latency data immediately
- Controller refreshes node checks in background when cache is stale
- Frontend may refresh every 30-60 seconds
- A visitor must not directly trigger an uncached ICMP test against every node on every page load

Suggested cache TTL:

- 10 to 30 seconds

This prevents unnecessary load during high visitor traffic.

---

## 11. Multi-Location Comparison

Users should be able to select multiple nodes and run one target from several regions.

Example:

```text
Target: example.com

[x] Dhaka
[x] Singapore
[x] Frankfurt
[ ] Los Angeles

Run Comparison
```

Comparison result:

```text
Location       Ping        Loss      Hops      Status
Dhaka          2.1 ms      0%        8         Excellent
Singapore      41.4 ms     0%        11        Good
Frankfurt      143.8 ms    0%        17        Moderate
```

The controller must enforce stricter limits for multi-node runs.

---

## 12. Download Speed Tests

Download test files are displayed in the main "Run Test" panel after a visitor selects a node. Test IPs (IPv4 and IPv6) with hostnames are also shown in the main panel alongside download links.

When a visitor selects a node, the main panel shows:

- **Node name and location** header
- **Test IPs**: the node's IPv4 and IPv6 addresses with family labels and hostname, each selectable for easy copy
- **Download test files**: one-click download buttons with size labels (e.g. 100MB, 1GB)

Recommended test file sizes:

- 100 MB
- 1 GB
- 5 GB
- 10 GB

Files must be served directly from the selected node.

Do not proxy large file downloads through Laravel.

Preferred architecture:

```text
Browser
   |
   v
Controller generates safe node URL
   |
   v
Node NGINX serves static test file directly
```

Optional future feature:

- Randomly generated streaming data endpoint

Static pre-generated files are simpler for v1.

The admin panel retains a dedicated downloads management section at `/admin/downloads` for uploading and managing test files.

---

## 13. Node Agent

The remote agent is responsible for executing only predefined network tests.

Agent responsibilities:

- Register with controller
- Authenticate controller requests
- Report node health
- Report supported features
- Execute approved tests
- Enforce local resource limits
- Stream output
- Serve status information
- Report agent version

The agent must not expose arbitrary command execution.

### 13.1 Supported Agent Actions

Initial actions:

```text
ping
traceroute
mtr
dns
reverse_dns
health
version
```

Future actions:

```text
tcp_connect
http_latency
iperf3
bgp_lookup
whois
```

### 13.2 Agent Request Example

Controller sends structured input:

```json
{
  "action": "ping",
  "target": "1.1.1.1",
  "family": 4,
  "count": 4,
  "timeout": 5
}
```

The agent constructs the approved process internally.

No visitor-supplied shell command should ever be passed directly to a shell.

---

## 14. Secure Process Execution

Forbidden implementation:

```text
shell_exec(user_input)
```

Forbidden:

```text
sh -c <visitor input>
```

Preferred approach:

- Direct binary execution
- Fixed binary path
- Fixed supported arguments
- Validated target
- Validated integer limits
- Timeout enforced by process context
- Maximum output size enforced

Example conceptual execution:

```text
/usr/bin/ping
-4
-c
4
-W
2
1.1.1.1
```

The process must be started with arguments as separate values, not through shell interpolation.

---

## 15. Target Validation

Targets may be:

- IPv4 address
- IPv6 address
- Hostname

The controller and/or agent must reject dangerous destinations.

Default blocked IPv4 ranges should include at least:

```text
0.0.0.0/8
10.0.0.0/8
100.64.0.0/10
127.0.0.0/8
169.254.0.0/16
172.16.0.0/12
192.0.0.0/24
192.168.0.0/16
198.18.0.0/15
224.0.0.0/4
240.0.0.0/4
```

Default blocked IPv6 ranges should include at least:

```text
::/128
::1/128
fc00::/7
fe80::/10
ff00::/8
```

Metadata and cloud-service addresses must also be blocked where relevant.

Example:

```text
169.254.169.254
```

### DNS Rebinding Protection

Hostname targets must be validated after resolution.

Process:

```text
Hostname submitted
      |
      v
Resolve hostname
      |
      v
Validate every resolved IP
      |
      v
Reject if any resolved target violates policy
```

The agent should preferably resolve and validate again immediately before execution.

---

## 16. Rate Limiting

Rate limiting is mandatory.

Suggested defaults:

```text
Ping            10 requests/minute/IP
Traceroute       5 requests/minute/IP
MTR              3 requests/minute/IP
DNS             20 requests/minute/IP
Multi-location   2 requests/minute/IP
```

Per-node concurrency limits:

```text
Ping             20 concurrent
Traceroute         8 concurrent
MTR                5 concurrent
DNS               20 concurrent
```

All limits must be configurable from administration.

Additional controls:

- Global concurrency limit
- Per-node concurrency limit
- Per-source-IP limit
- Per-API-key limit
- Test runtime limit
- Output byte limit
- Target repetition limit

---

## 17. Node Authentication

Remote node communication must be authenticated.

Preferred options:

### Initial release

- Unique node ID
- Strong node secret
- HMAC-signed requests
- Timestamp
- Replay protection

### Recommended production upgrade

- Mutual TLS

Node registration flow:

```text
Admin creates node
        |
        v
Controller generates one-time registration token
        |
        v
Agent installs using token
        |
        v
Agent exchanges token for permanent credentials
        |
        v
One-time token is invalidated
```

Example:

```bash
sudo lg-agent register \
  --controller=https://lg.example.com \
  --token=REGISTRATION_TOKEN
```

---

## 18. Node Configuration

Each node should support:

- Node ID
- Display name
- Country
- City
- Datacenter/provider
- ASN
- IPv4 address
- IPv6 address
- Latitude
- Longitude
- Uplink speed
- Feature flags
- Maintenance state
- Public/private state
- Sort order
- Download host
- Agent version
- Last heartbeat

Example feature flags:

```json
{
  "ping": true,
  "traceroute": true,
  "mtr": true,
  "dns": true,
  "downloads": true,
  "iperf3": false
}
```

---

## 19. Administration Panel

The admin interface should be part of the Vue application or a separate protected Vue route group.

Required administration sections:

```text
Dashboard
Nodes
Tests
Rate Limits
Security
Downloads
Network Information
Branding
API Keys
Users
Logs
System
Updates
```

### Dashboard

Show:

- Active nodes
- Offline nodes
- Tests today
- Tests this hour
- Rate-limited requests
- Failed tests
- Most tested targets
- Most active visitor regions where privacy policy permits
- Controller status
- Redis status
- Queue status

### Node Management

Admin can:

- Add node
- Generate registration token
- Disable node
- Maintenance mode
- Enable/disable tests
- Configure limits
- Set metadata
- Rotate credentials
- Remove node

---

## 20. Branding and White-Label Configuration

Open-source users should be able to configure branding without editing source code.

Settings:

- Site name
- Logo
- Favicon
- Primary domain
- Organization name
- ASN
- Homepage title
- Description
- Footer text
- GitHub link
- PeeringDB link
- Network status link
- Contact email
- Theme settings

Optional:

- Custom CSS

Avoid custom JavaScript in v1 for security reasons.

---

## 21. Public Network Information

Optional network information page should support:

- ASN
- Network name
- IPv4 prefixes
- IPv6 prefixes
- PeeringDB link
- Looking Glass locations
- Internet exchanges
- Peering policy
- NOC contact
- Abuse contact

The administrator manually configures these values initially.

Future integrations may fetch public data automatically.

---

## 22. Database Model

Initial core tables:

```text
users
nodes
node_credentials
node_heartbeats
node_capabilities
network_tests
network_test_events
rate_limit_events
security_events
api_keys
settings
download_files
registration_tokens
system_updates
```

### nodes

Suggested columns:

```text
id
uuid
slug
name
city
country_code
provider
asn
ipv4
ipv6
latitude
longitude
uplink_mbps
status
maintenance
public
sort_order
last_seen_at
agent_version
created_at
updated_at
```

### network_tests

Suggested columns:

```text
id
uuid
node_id
test_type
target
resolved_ip
ip_family
status
visitor_hash
started_at
completed_at
runtime_ms
packet_loss
latency_min
latency_avg
latency_max
hop_count
error_code
created_at
```

Avoid permanently storing raw visitor IP addresses unless explicitly required by the operator.

A configurable privacy-preserving hash is preferred for abuse protection.

---

## 23. API Design

Version all public APIs.

Base path:

```text
/api/v1
```

### Public endpoints

```text
GET    /api/v1/config
GET    /api/v1/nodes
GET    /api/v1/nodes/latency
GET    /api/v1/nodes/{node}
POST   /api/v1/tests
GET    /api/v1/tests/{test}
GET    /api/v1/tests/{test}/stream
GET    /api/v1/downloads
GET    /api/v1/network
GET    /api/v1/status
```

### Admin endpoints

```text
/api/v1/admin/*
```

All admin endpoints require authenticated authorization.

---

## 24. Test Lifecycle

Example Ping lifecycle:

```text
Visitor submits request
        |
        v
Laravel validates request
        |
        v
Rate limit check
        |
        v
Target validation
        |
        v
Node availability check
        |
        v
Create test record
        |
        v
Dispatch request to node agent
        |
        v
Agent executes predefined binary
        |
        v
Output streams to controller
        |
        v
Controller streams SSE to browser
        |
        v
Agent returns structured summary
        |
        v
Controller marks test complete
```

---

## 25. Agent Health and Heartbeat

Agents should send periodic heartbeats.

Suggested interval:

- 15 to 30 seconds

Heartbeat may include:

```json
{
  "node_id": "dhaka-01",
  "agent_version": "1.0.0",
  "uptime_seconds": 86400,
  "load": 0.31,
  "memory_percent": 22.5,
  "features": ["ping", "traceroute", "mtr", "dns"],
  "timestamp": "2026-09-07T12:00:00Z"
}
```

A node becomes degraded/offline after configurable missed heartbeats.

Suggested defaults:

```text
Degraded: 60 seconds
Offline: 120 seconds
```

---

## 26. System Security

Minimum requirements:

- HTTPS only in production
- HSTS
- CSP
- Strict CORS policy
- CSRF protection for authenticated browser actions
- Secure cookies
- SameSite cookies
- Rate limiting
- Request body limits
- Output limits
- Process timeouts
- No raw shell execution
- Reserved/private IP blocking
- DNS rebinding protection
- Node request signing
- Replay protection
- Secret rotation
- Least-privilege service accounts
- systemd sandboxing for agent
- Restricted filesystem access
- Audit logging
- Dependency scanning
- Automated security tests

### Agent systemd hardening

Target settings where compatible:

```text
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ProtectKernelTunables=true
ProtectKernelModules=true
ProtectControlGroups=true
RestrictSUIDSGID=true
LockPersonality=true
MemoryDenyWriteExecute=true
```

Capabilities should be minimized.

If raw ICMP requires special capability, configure only the required capability rather than running the full agent as root.

---

## 27. Abuse Prevention

Potential abuse cases:

- ICMP flooding
- MTR resource abuse
- Scanning internal networks
- DNS rebinding
- Targeting metadata endpoints
- Distributed multi-node probing
- API automation abuse
- Oversized output generation

Controls:

- Per-IP limits
- Global limits
- Per-target limits
- Multi-node stricter limits
- CAPTCHA optional after suspicious activity
- Temporary source bans
- Configurable blocked target list
- Configurable allowed target list mode
- Audit events

---

## 28. Logging

Logging categories:

```text
application
security
agent
network-tests
rate-limit
installer
update
```

Do not write agent secrets or API tokens into logs.

Admin UI should support security event review.

---

## 29. Privacy

Default behavior should minimize collection of visitor information.

Recommended:

- Do not retain full visitor IP by default
- Use short-lived Redis keys for rate limiting
- Optional keyed hash for abuse correlation
- Configurable log retention
- Document data collection clearly

Admin may optionally enable stronger logging where legally appropriate.

---

## 30. Update System

The one-click installation experience also requires easy upgrades.

Recommended command:

```bash
sudo openlg update
```

or:

```bash
sudo /opt/open-looking-glass/bin/update
```

Upgrade process:

```text
Check release
Backup config
Backup database
Download signed release
Verify checksum/signature
Enable maintenance mode if needed
Update controller
Update frontend
Run migrations
Update local agent
Restart services
Run health checks
Disable maintenance mode
```

Remote agents should support staged upgrades.

Admin dashboard should display:

- Current version
- Latest available version
- Update notes
- Agent version mismatch

Automatic updates should be opt-in.

---

## 31. Rollback

The updater must preserve enough state for rollback.

Recommended retained items:

- Previous application release
- Database backup
- Environment backup
- NGINX config backup
- Agent binary backup

Example:

```bash
sudo openlg rollback
```

---

## 32. Uninstaller

Provide:

```bash
sudo openlg uninstall
```

The uninstaller must ask whether to preserve:

- Database
- Configuration
- Logs
- SSL certificates
- Download files

No destructive deletion without explicit confirmation.

---

## 33. Docker Installation

Repository must include:

```text
docker-compose.yml
```

Services may include:

```text
nginx
controller
queue
scheduler
redis
postgres
```

Agent may run:

- Natively on node
- In a privileged/restricted container where supported

Native agent deployment should remain recommended for network tooling simplicity.

---

## 34. CLI Utility

Provide an administration CLI wrapper.

Suggested command:

```text
openlg
```

Examples:

```bash
openlg status
openlg update
openlg rollback
openlg repair
openlg doctor
openlg backup
openlg restore
openlg agent status
openlg agent register
openlg logs
```

`openlg doctor` should check:

- PHP
- Redis
- Database
- Queue
- Scheduler
- NGINX
- SSL
- Controller API
- Agent
- ping
- traceroute
- mtr
- DNS utility

---

## 35. GitHub Releases

Each stable release should publish:

- Source archive
- Controller release artifact
- Frontend production build where useful
- Linux agent binaries
- Checksums
- Signed checksums if signing infrastructure is available
- Installation script
- Release notes

Agent binaries initially:

```text
linux-amd64
linux-arm64
```

---

## 36. CI/CD

GitHub Actions should run:

### Frontend

- npm install
- Type check
- Lint
- Unit tests
- Production build

### Laravel

- Composer install
- PHP lint
- Static analysis
- Unit tests
- Feature tests
- Security checks

### Go Agent

- gofmt check
- go vet
- Unit tests
- Race tests where practical
- Build amd64
- Build arm64

### Security

- Dependency scanning
- Secret scanning
- CodeQL
- Container image scanning where applicable

---

## 37. Versioning

Use Semantic Versioning.

Example:

```text
1.0.0
1.1.0
1.1.1
2.0.0
```

Controller, frontend, and agent should expose their versions.

Compatibility rules should be documented.

---

## 38. Initial Release Scope

### Version 1.0 Must Include

- Vue 3 public frontend
- Laravel controller
- Go agent
- Single-node deployment
- Multi-node deployment
- Ping
- Traceroute
- MTR
- DNS
- IPv4
- IPv6
- SSE output
- Automatic all-node latency in sidebar
- Location comparison
- Static download speed files
- Node health
- Admin login
- Node management
- Feature toggles
- Rate limiting
- Security controls
- Installer
- Updater
- Docker Compose
- Public API
- Documentation

### Version 1.1 Candidates

- TCP port check
- HTTP latency
- Reverse DNS UI
- API keys
- Shareable result links
- Result export
- Advanced branding
- Geographic node map

### Version 1.2 Candidates

- iperf3
- BGP information
- PeeringDB integration
- Public status page
- Advanced observability

---

## 39. Development Phases

### Phase 1: Repository and Base Architecture

- Create GitHub repository
- Finalize license
- Create monorepo
- Configure CI
- Laravel skeleton
- Vue skeleton
- Go agent skeleton

### Phase 2: Node Protocol

- Registration flow
- Authentication
- Heartbeats
- Capability reporting
- Signed requests

### Phase 3: Network Test Engine

- Ping
- Traceroute
- MTR
- DNS
- Input validation
- Output parsing
- Timeouts

### Phase 4: Controller API

- Nodes
- Tests
- Streams
- Rate limiting
- Redis coordination
- Test persistence

### Phase 5: Public Frontend

Implement approved frontend design:

- Hero/network summary
- Test console
- Location selector
- Test selector
- Terminal output
- Statistics
- Multi-location comparison
- Locations section
- Downloads
- Automatic latency in sidebar
- Responsive layout

### Phase 6: Administration

- Admin authentication
- Dashboard
- Node management
- Limits
- Branding
- System settings
- Logs

### Phase 7: Installer

- Ubuntu 24.04
- Debian 12
- Full installation mode
- Controller mode
- Agent mode
- SSL
- systemd
- Verification

### Phase 8: Release System

- GitHub Releases
- Build artifacts
- Update utility
- Rollback
- Checksums

### Phase 9: Hardening

- Security review
- Abuse testing
- Load testing
- Agent sandboxing
- DNS rebinding tests
- Input fuzzing

### Phase 10: Public v1.0 Release

- Documentation
- Installation guide
- Screenshots
- Demo instance
- Upgrade documentation
- Contribution guide

---

## 40. README Installation Experience

The GitHub README should make installation obvious.

Example:

```markdown
## Install Open Looking Glass

Ubuntu 24.04 / Debian 12

```bash
curl -fsSL https://raw.githubusercontent.com/example/open-looking-glass/main/install.sh | sudo bash
```

Choose:

1. Full installation
2. Controller only
3. Remote node agent

The installer configures the application, services, database, Redis, web server, TLS and systemd automatically.
```

---

## 41. Example Remote Node Installation

From the admin panel:

```text
Nodes > Add Node > Generate Install Command
```

The UI may generate:

```bash
curl -fsSL https://raw.githubusercontent.com/<ORG>/<REPO>/main/install.sh | \
sudo bash -s -- agent \
  --controller=https://lg.example.com \
  --token=ONE_TIME_TOKEN
```

The token should expire quickly and become invalid after successful registration.

---

## 42. API Safety Principle

The API should express intent, not commands.

Correct:

```json
{
  "test": "mtr",
  "target": "example.com",
  "family": 6,
  "node": "fra-01"
}
```

Incorrect:

```json
{
  "command": "mtr -6 example.com"
}
```

This rule applies throughout the project.

---

## 43. Performance Targets

Suggested v1 targets:

- Initial frontend cached load: under 1 second on a normal broadband connection where infrastructure allows
- API response for cached node list: under 100 ms server-side
- Cached latency endpoint: under 100 ms server-side
- Agent heartbeat handling: lightweight enough for hundreds of nodes
- SSE start time: under 500 ms after test acceptance under normal conditions
- Frontend Lighthouse performance target: 90+

---

## 44. Scalability

The controller should remain stateless where practical.

Use Redis for:

- Rate limiting
- Node presence
- Test coordination
- Short-lived stream state
- Latency cache
- Concurrency counters

Multiple controller instances should eventually be possible behind a load balancer.

This does not need to be required for v1 but architecture should not block it.

---

## 45. Future Optional Modules

Potential later additions:

- BGP route server integration
- Looking Glass BGP queries
- PeeringDB auto sync
- RIPEstat integration
- RPKI status
- ROA visibility
- Route leak diagnostics
- TCP connectivity tests
- HTTP/TLS timing
- DNS propagation checks
- Reverse traceroute integrations
- Public API tokens
- Grafana/Prometheus metrics
- Webhook alerts for node outages
- Prometheus exporter
- White-label themes
- Localization

---

## 46. Definition of Done for v1.0

Version 1.0 is ready only when all of the following are true:

- Fresh Ubuntu 24.04 server can install from one command
- Fresh Debian 12 server can install from one command
- Full single-node deployment works
- Controller-only deployment works
- Remote agent deployment works
- Agent registration works securely
- Ping works over IPv4 and IPv6
- Traceroute works over IPv4 and IPv6
- MTR works over IPv4 and IPv6
- DNS test works
- Output streams to browser
- Automatic all-node latency shown in sidebar
- Multi-location comparison works
- Download files work directly from nodes
- Admin can add/disable/configure nodes
- Rate limiting works
- Private/reserved destination protection works
- DNS rebinding protection is tested
- No public path can execute arbitrary shell commands
- Installer health check passes
- Upgrade works
- Rollback works
- GitHub CI passes
- Documentation is complete
- Security policy is published
- Release artifacts are reproducible or documented

---

## 47. Final Recommended Project Direction

Use the following architecture as the baseline:

```text
Frontend
Vue 3 + TypeScript + Vite

Controller
Laravel + PostgreSQL + Redis

Remote Nodes
Go Agent

Live Output
Server-Sent Events

Web Server
NGINX

Deployment
Native one-click installer + Docker Compose

Source Distribution
Public GitHub repository

License
Apache-2.0 preferred, subject to final dependency/license review
```

The project should be treated as a new open-source Looking Glass platform rather than only a frontend redesign of an older PHP Looking Glass.

The key differentiators should be:

- One-command installation
- Secure multi-node architecture
- Modern Vue interface
- Simple remote node onboarding
- Automatic node latency visibility
- Multi-location comparison
- Strong abuse prevention
- Fully open GitHub development
- Clean update and rollback workflow

