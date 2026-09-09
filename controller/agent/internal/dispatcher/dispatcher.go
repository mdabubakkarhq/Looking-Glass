package dispatcher

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"strconv"
	"sync"
	"time"

	"github.com/openlookingglass/lg-agent/internal/auth"
	"github.com/openlookingglass/lg-agent/internal/config"
	"github.com/openlookingglass/lg-agent/internal/executor"
)

// TestRequest is received from the controller.
type TestRequest struct {
	TestID    string `json:"test_id"`
	TestType  string `json:"test_type"`
	Target    string `json:"target"`
	IPFamily  string `json:"ip_family"`
	Timeout   int    `json:"timeout"`
	Timestamp string `json:"timestamp"`
}

// Dispatcher manages running tests and reporting results back.
type Dispatcher struct {
	cfg        *config.Config
	httpClient *http.Client
	active     sync.WaitGroup
}

func New(cfg *config.Config) *Dispatcher {
	return &Dispatcher{
		cfg: cfg,
		httpClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

// Dispatch starts a test asynchronously.
func (d *Dispatcher) Dispatch(req TestRequest) {
	d.active.Add(1)
	go func() {
		defer d.active.Done()
		d.runTest(req)
	}()
}

// WaitActive blocks until all active tests complete.
func (d *Dispatcher) WaitActive() {
	d.active.Wait()
}

func (d *Dispatcher) runTest(req TestRequest) {
	// Validate target
	if err := executor.ValidateTarget(req.Target); err != nil {
		d.reportError(req.TestID, "invalid_target", err.Error())
		return
	}

	// Set defaults
	if req.Timeout <= 0 {
		req.Timeout = 60
	}
	if req.IPFamily == "" {
		req.IPFamily = "auto"
	}

	// Create context with timeout
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(req.Timeout)*time.Second)
	defer cancel()

	// Run test and stream output
	onOutput := func(line string) {
		d.sendEvent(req.TestID, "output", map[string]interface{}{
			"line":        line,
			"occurred_at": executor.Now(),
		})
	}

	result, err := executor.RunTest(ctx, req.TestType, req.Target, req.IPFamily, req.Timeout, onOutput)
	if err != nil {
		d.reportError(req.TestID, "execution_error", err.Error())
		return
	}

	// Resolve IP for ping
	resolvedIP := result.ResolvedIP
	if resolvedIP == "" && req.TestType == "ping" {
		if ips, err := net.LookupIP(req.Target); err == nil && len(ips) > 0 {
			resolvedIP = ips[0].String()
		}
	}

	// Report completion
	d.reportComplete(req.TestID, resolvedIP, result)
}

func (d *Dispatcher) sendEvent(testID, eventType string, data interface{}) {
	dataBytes, _ := json.Marshal(data)
	payload := map[string]interface{}{
		"event_type":  eventType,
		"data":        string(dataBytes),
		"occurred_at": executor.Now(),
	}

	url := fmt.Sprintf("%s/api/v1/agent/tests/%s/events", d.cfg.ControllerURL, testID)
	d.postWithAuth(url, payload)
}

func (d *Dispatcher) reportComplete(testID, resolvedIP string, result executor.Result) {
	payload := map[string]interface{}{
		"packet_loss":  result.PacketLoss,
		"latency_min":  result.LatencyMin,
		"latency_avg":  result.LatencyAvg,
		"latency_max":  result.LatencyMax,
		"latency_stddev": result.LatencyStd,
		"hop_count":    result.HopCount,
		"resolved_ip":  resolvedIP,
	}

	url := fmt.Sprintf("%s/api/v1/agent/tests/%s/complete", d.cfg.ControllerURL, testID)
	d.postWithAuth(url, payload)
}

func (d *Dispatcher) reportError(testID, errorCode, message string) {
	payload := map[string]interface{}{
		"error_code":    errorCode,
		"error_message": message,
	}

	url := fmt.Sprintf("%s/api/v1/agent/tests/%s/complete", d.cfg.ControllerURL, testID)
	d.postWithAuth(url, payload)
}

func (d *Dispatcher) postWithAuth(url string, payload map[string]interface{}) {
	jsonBody, err := json.Marshal(payload)
	if err != nil {
		fmt.Printf("Error marshaling payload: %v\n", err)
		return
	}

	// Build sign payload from the actual payload fields (matching Laravel's AuthenticateAgent)
	signPayload := make(map[string]string)
	for k, v := range payload {
		switch val := v.(type) {
		case string:
			signPayload[k] = val
		case float64:
			signPayload[k] = strconv.FormatFloat(val, 'f', -1, 64)
		case int:
			signPayload[k] = strconv.Itoa(val)
		default:
			b, _ := json.Marshal(val)
			signPayload[k] = string(b)
		}
	}

	headers, err := auth.MakeHeaders(d.cfg.NodeKeyID, d.cfg.NodeSecret, signPayload)
	if err != nil {
		fmt.Printf("Error creating auth headers: %v\n", err)
		return
	}

	req, err := http.NewRequest("POST", url, bytes.NewReader(jsonBody))
	if err != nil {
		fmt.Printf("Error creating request: %v\n", err)
		return
	}

	req.Header.Set("Content-Type", "application/json")
	for k, v := range headers {
		req.Header.Set(k, v)
	}

	resp, err := d.httpClient.Do(req)
	if err != nil {
		fmt.Printf("Error posting to %s: %v\n", url, err)
		return
	}
	defer resp.Body.Close()
	io.ReadAll(resp.Body)

	if resp.StatusCode >= 400 {
		fmt.Printf("Warning: %s returned HTTP %d\n", url, resp.StatusCode)
	}
}
