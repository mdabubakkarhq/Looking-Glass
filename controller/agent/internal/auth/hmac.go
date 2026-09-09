package auth

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"sort"
	"time"
)

// SignPayload creates an HMAC-SHA256 signature matching the Laravel implementation.
// The payload keys are sorted (ksort), JSON-encoded, then signed.
func SignPayload(payload map[string]string, secret string) (string, error) {
	// ksort equivalent: sort keys alphabetically
	sortedPayload := sortedJSON(payload)

	h := hmac.New(sha256.New, []byte(secret))
	if _, err := h.Write([]byte(sortedPayload)); err != nil {
		return "", fmt.Errorf("failed to sign payload: %w", err)
	}

	return hex.EncodeToString(h.Sum(nil)), nil
}

// sortedJSON sorts map keys and returns deterministic JSON.
func sortedJSON(m map[string]string) string {
	keys := make([]string, 0, len(m))
	for k := range m {
		keys = append(keys, k)
	}
	sort.Strings(keys)

	result := "{"
	for i, k := range keys {
		if i > 0 {
			result += ","
		}
		// Use json.Marshal for proper escaping
		keyBytes, _ := json.Marshal(k)
		valBytes, _ := json.Marshal(m[k])
		result += string(keyBytes) + ":" + string(valBytes)
	}
	result += "}"
	return result
}

// MakeHeaders creates the HMAC authentication headers for a request to the controller.
func MakeHeaders(nodeKeyID, nodeSecret string, payload map[string]string) (map[string]string, error) {
	timestamp := time.Now().UTC().Format(time.RFC3339)
	payload["timestamp"] = timestamp

	signature, err := SignPayload(payload, nodeSecret)
	if err != nil {
		return nil, err
	}

	return map[string]string{
		"X-Node-Key": nodeKeyID,
		"X-Signature": signature,
		"X-Timestamp": timestamp,
	}, nil
}
