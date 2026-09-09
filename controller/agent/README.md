# Open Looking Glass Agent

The remote node agent for the Open Looking Glass platform. Written in Go for single-binary deployment.

## Features

- Single compiled binary
- Low memory footprint
- Ping, traceroute, MTR, and DNS tests
- HMAC-SHA256 signed requests
- Automatic heartbeats with system stats
- Target validation (blocks private/reserved ranges)
- DNS rebinding protection
- systemd integration

## Requirements

- Linux (amd64 or arm64)
- `ping`, `traceroute`, `mtr`, `dig` installed
- Network access to the controller

### Install dependencies

```bash
# Ubuntu/Debian
sudo apt-get install -y iputils-ping traceroute mtr-tiny dnsutils

# RHEL/CentOS/Fedora
sudo dnf install -y iputils traceroute mtr bind-utils
```

## Quick Start

### 1. Register with controller

```bash
sudo lg-agent register \
  --controller=https://lg.example.com \
  --token=YOUR_REGISTRATION_TOKEN
```

This writes credentials to `/etc/lg-agent/config.yaml`.

### 2. Start the agent

```bash
sudo lg-agent serve
```

### 3. Install as systemd service

```bash
sudo cp scripts/lg-agent.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now lg-agent
sudo systemctl status lg-agent
```

## Configuration

Configuration is loaded from `/etc/lg-agent/config.yaml` by default.

Override with CLI flags or environment variables:

| Flag | Env Var | Default | Description |
|------|---------|---------|-------------|
| `--controller` | `LG_CONTROLLER_URL` | | Controller URL |
| `--listen` | `LG_LISTEN_ADDR` | `:8443` | Agent listen address |
| `--node-key-id` | `LG_NODE_KEY_ID` | | Node key ID |
| `--node-secret` | `LG_NODE_SECRET` | | Node secret |

## Building from Source

```bash
# Install Go 1.22+
# https://go.dev/dl/

# Build
make build

# Cross-compile for Linux
make build-linux

# Run tests
make test
```

## Security

- All requests are HMAC-SHA256 signed
- Timestamps must be within 30 seconds (replay protection)
- Private/reserved IP ranges are blocked
- DNS rebinding protection (resolved IPs validated)
- No arbitrary command execution
- systemd security hardening applied
