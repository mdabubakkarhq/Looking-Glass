package server

import (
	"bytes"
	"encoding/json"
	"io"
	"net/http"
	"time"

	"github.com/openlookingglass/lg-agent/internal/auth"
	"github.com/openlookingglass/lg-agent/internal/config"
	"github.com/openlookingglass/lg-agent/internal/dispatcher"
)

type Server struct {
	cfg  *config.Config
	disp *dispatcher.Dispatcher
}

func New(cfg *config.Config, disp *dispatcher.Dispatcher) *Server {
	return &Server{cfg: cfg, disp: disp}
}

func (s *Server) Start() error {
	mux := http.NewServeMux()
	mux.HandleFunc("/api/tests", s.handleTest)
	mux.HandleFunc("/health", s.handleHealth)

	server := &http.Server{
		Addr:         s.cfg.ListenAddr,
		Handler:      mux,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 10 * time.Second,
	}

	return server.ListenAndServe()
}

func (s *Server) handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
}

func (s *Server) handleTest(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Verify HMAC signature
	if !s.verifyAuth(r) {
		http.Error(w, `{"message":"Unauthorized"}`, http.StatusUnauthorized)
		return
	}

	body, err := io.ReadAll(r.Body)
	if err != nil {
		http.Error(w, `{"message":"Failed to read body"}`, http.StatusBadRequest)
		return
	}

	var req dispatcher.TestRequest
	if err := json.Unmarshal(body, &req); err != nil {
		http.Error(w, `{"message":"Invalid JSON"}`, http.StatusBadRequest)
		return
	}

	// Dispatch test asynchronously
	s.disp.Dispatch(req)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusAccepted)
	json.NewEncoder(w).Encode(map[string]string{"status": "accepted", "test_id": req.TestID})
}

func (s *Server) verifyAuth(r *http.Request) bool {
	nodeKeyID := r.Header.Get("X-Node-Key")
	signature := r.Header.Get("X-Signature")
	timestamp := r.Header.Get("X-Timestamp")

	if nodeKeyID == "" || signature == "" || timestamp == "" {
		return false
	}

	// Verify node key ID matches
	if nodeKeyID != s.cfg.NodeKeyID {
		return false
	}

	// Check timestamp freshness
	t, err := time.Parse(time.RFC3339, timestamp)
	if err != nil {
		return false
	}
	if time.Since(t) > 30*time.Second || time.Since(t) < -30*time.Second {
		return false
	}

	// Read body for signature verification
	body, _ := io.ReadAll(r.Body)
	r.Body = io.NopCloser(bytes.NewReader(body))

	// Parse payload
	var payload map[string]string
	if err := json.Unmarshal(body, &payload); err != nil {
		return false
	}
	payload["timestamp"] = timestamp

	// Verify HMAC
	expectedSig, err := auth.SignPayload(payload, s.cfg.NodeSecret)
	if err != nil {
		return false
	}

	return expectedSig == signature
}
