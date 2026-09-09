package heartbeat

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"

	"github.com/openlookingglass/lg-agent/internal/auth"
	"github.com/openlookingglass/lg-agent/internal/config"
	"github.com/openlookingglass/lg-agent/internal/system"
)

type Sender struct {
	cfg     *config.Config
	version string
	client  *http.Client
}

func New(cfg *config.Config, version string) *Sender {
	return &Sender{
		cfg:     cfg,
		version: version,
		client:  &http.Client{Timeout: 10 * time.Second},
	}
}

func (s *Sender) Start() {
	interval := time.Duration(s.cfg.HeartbeatInterval) * time.Second
	if interval <= 0 {
		interval = 30 * time.Second
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	// Send initial heartbeat immediately
	s.send()

	for range ticker.C {
		s.send()
	}
}

func (s *Sender) send() {
	stats := system.GetStats()

	payload := map[string]interface{}{
		"agent_version":        s.version,
		"hostname":             stats.Hostname,
		"os":                   stats.OS,
		"cpu_usage_percent":    stats.CPUUsagePercent,
		"memory_usage_percent": stats.MemoryUsagePercent,
		"disk_usage_percent":   stats.DiskUsagePercent,
		"active_tests":         0,
		"uptime_seconds":       stats.UptimeSeconds,
		"load_average":         stats.LoadAverage,
		"sent_at":              time.Now().UTC().Format(time.RFC3339),
	}

	jsonBody, err := json.Marshal(payload)
	if err != nil {
		fmt.Printf("[heartbeat] Error marshaling: %v\n", err)
		return
	}

	url := s.cfg.ControllerURL + "/api/v1/agent/heartbeat"

	req, err := http.NewRequest("POST", url, bytes.NewReader(jsonBody))
	if err != nil {
		fmt.Printf("[heartbeat] Error creating request: %v\n", err)
		return
	}

	// Sign the payload for auth
	signPayload := map[string]string{
		"timestamp": payload["sent_at"].(string),
	}

	headers, err := auth.MakeHeaders(s.cfg.NodeKeyID, s.cfg.NodeSecret, signPayload)
	if err != nil {
		fmt.Printf("[heartbeat] Error signing: %v\n", err)
		return
	}

	req.Header.Set("Content-Type", "application/json")
	for k, v := range headers {
		req.Header.Set(k, v)
	}

	resp, err := s.client.Do(req)
	if err != nil {
		fmt.Printf("[heartbeat] Error sending: %v\n", err)
		return
	}
	defer resp.Body.Close()
	io.ReadAll(resp.Body)

	if resp.StatusCode != http.StatusOK {
		fmt.Printf("[heartbeat] Controller returned HTTP %d\n", resp.StatusCode)
	}
}
