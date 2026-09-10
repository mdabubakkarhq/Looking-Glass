# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in Open Looking Glass, please report it responsibly.

### How to Report

1. **Do NOT** open a public GitHub issue for security vulnerabilities.
2. Email security reports to: **security@openlookingglass.com**
3. Include the following in your report:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if available)

### What to Expect

- **Acknowledgment** within 48 hours
- **Status update** within 7 days
- **Fix timeline** communicated once the issue is confirmed
- **Credit** in the security advisory (unless you prefer anonymity)

## Security Architecture

### Controller (Laravel)

- All admin endpoints require Sanctum token authentication
- Agent communication uses HMAC-SHA256 signed requests with timestamp validation
- Rate limiting is enforced per-IP with configurable thresholds
- Target validation blocks private/reserved IP ranges
- DNS rebinding protection through IP re-validation
- Replay protection via timestamp-based request signing
- Output limits prevent resource exhaustion
- Request body size limits enforced

### Agent (Go Binary)

- Agent-to-controller communication uses HMAC-SHA256 authentication
- One-time registration tokens exchanged for permanent credentials
- Credential rotation supported via admin panel
- No raw shell execution — direct binary execution only
- Private IP targets blocked by default
- Process timeout limits enforced
- Output byte limits enforced

### Frontend (Vue 3 SPA)

- No sensitive data stored in localStorage (admin tokens only)
- CSRF protection via Sanctum cookie middleware
- CSP headers configured via NGINX/Apache
- All API calls use HTTPS in production
- No user-supplied input passed to shell commands

### Infrastructure

- HTTPS enforced in production
- HSTS headers recommended
- CORS restricted to configured origins
- Redis used for rate limiting and caching (no persistent PII)
- PostgreSQL for application data
- Visitor IPs are hashed for privacy by default

## Hardening Recommendations

### Controller

1. Use a dedicated database user with minimal privileges
2. Enable Redis password authentication
3. Configure strict CORS origins in `config/looking-glass.php`
4. Set `APP_DEBUG=false` in production
5. Use environment-specific `.env` files
6. Enable Laravel's signed URLs for sensitive routes
7. Configure appropriate rate limits in `config/looking-glass.php`

### Agent

1. Run the agent as a dedicated non-root user where possible
2. Use the provided systemd service file with sandboxing enabled
3. Only grant `CAP_NET_RAW` capability rather than running as root
4. Restrict agent filesystem access to `/etc/lg-agent` only
5. Enable audit logging for agent operations
6. Rotate agent credentials periodically

### Network

1. Use a Web Application Firewall (WAF) in front of the controller
2. Configure NGINX request body size limits
3. Enable NGINX rate limiting as a first layer of defense
4. Use fail2ban for repeated abuse from the same IP
5. Monitor security events via the admin panel

## Dependency Security

- PHP dependencies managed via Composer with `composer audit`
- Node.js dependencies managed via npm with `npm audit`
- Go dependencies managed via Go modules
- Run dependency audits regularly as part of CI/CD

## Security Event Types

The system logs the following security event types:

| Event Type                | Severity | Description                                    |
| ------------------------- | -------- | ---------------------------------------------- |
| `rate_limit_exceeded`     | warning  | IP or visitor exceeded rate limit              |
| `private_ip_blocked`      | warning  | Target resolved to private/reserved IP         |
| `invalid_agent_signature` | critical | Agent request failed HMAC verification         |
| `replay_attack_detected`  | critical | Request timestamp outside acceptable window    |
| `credential_rotation`     | info     | Agent credentials were rotated                 |
| `registration_token_used` | info     | One-time registration token was exchanged      |
| `dns_rebinding_detected`  | critical | Target IP changed between resolution and check |
| `failed_login_attempt`    | warning  | Failed admin login attempt from IP             |
| `admin_login_banned`      | critical | IP banned after too many failed login attempts |

## Contact

For security inquiries: security@openlookingglass.com
