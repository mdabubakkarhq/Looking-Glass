package auth

import (
	"testing"
)

func TestSignPayload(t *testing.T) {
	payload := map[string]string{
		"test_id":   "abc-123",
		"timestamp": "2026-09-08T12:00:00Z",
	}
	secret := "test-secret-key"

	sig1, err := SignPayload(payload, secret)
	if err != nil {
		t.Fatalf("SignPayload failed: %v", err)
	}

	if sig1 == "" {
		t.Fatal("signature is empty")
	}

	// Same payload should produce same signature
	sig2, err := SignPayload(payload, secret)
	if err != nil {
		t.Fatalf("SignPayload failed on second call: %v", err)
	}

	if sig1 != sig2 {
		t.Errorf("signatures differ: %s != %s", sig1, sig2)
	}
}

func TestSignPayloadDeterministic(t *testing.T) {
	// Keys should be sorted (ksort equivalent)
	p1 := map[string]string{"b": "2", "a": "1"}
	p2 := map[string]string{"a": "1", "b": "2"}

	secret := "my-secret"

	sig1, _ := SignPayload(p1, secret)
	sig2, _ := SignPayload(p2, secret)

	if sig1 != sig2 {
		t.Errorf("same keys in different order should produce same signature: %s != %s", sig1, sig2)
	}
}

func TestSignPayloadDifferentSecret(t *testing.T) {
	payload := map[string]string{"key": "value"}

	sig1, _ := SignPayload(payload, "secret1")
	sig2, _ := SignPayload(payload, "secret2")

	if sig1 == sig2 {
		t.Error("different secrets should produce different signatures")
	}
}

func TestMakeHeaders(t *testing.T) {
	headers, err := MakeHeaders("lk_test123", "test-secret", map[string]string{"data": "value"})
	if err != nil {
		t.Fatalf("MakeHeaders failed: %v", err)
	}

	if headers["X-Node-Key"] != "lk_test123" {
		t.Errorf("expected X-Node-Key=lk_test123, got %s", headers["X-Node-Key"])
	}

	if headers["X-Signature"] == "" {
		t.Error("X-Signature is empty")
	}

	if headers["X-Timestamp"] == "" {
		t.Error("X-Timestamp is empty")
	}
}
