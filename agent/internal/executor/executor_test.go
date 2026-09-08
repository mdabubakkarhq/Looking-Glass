package executor

import (
	"testing"
)

func TestValidateTarget_IP(t *testing.T) {
	tests := []struct {
		name    string
		target  string
		wantErr bool
	}{
		{"valid public IPv4", "8.8.8.8", false},
		{"valid public IPv6", "2001:4860:4860::8888", false},
		{"blocked loopback", "127.0.0.1", true},
		{"blocked private 10", "10.0.0.1", true},
		{"blocked private 172", "172.16.0.1", true},
		{"blocked private 192", "192.168.1.1", true},
		{"blocked link-local", "169.254.1.1", true},
		{"blocked metadata", "169.254.169.254", true},
		{"blocked IPv6 loopback", "::1", true},
		{"blocked IPv6 link-local", "fe80::1", true},
		{"empty target", "", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := ValidateTarget(tt.target)
			if (err != nil) != tt.wantErr {
				t.Errorf("ValidateTarget(%q) error = %v, wantErr %v", tt.target, err, tt.wantErr)
			}
		})
	}
}

func TestParsePingStats(t *testing.T) {
	output := `PING 1.1.1.1 (1.1.1.1) 56(84) bytes of data.
64 bytes from 1.1.1.1: icmp_seq=1 ttl=58 time=1.23 ms
64 bytes from 1.1.1.1: icmp_seq=2 ttl=58 time=1.45 ms
64 bytes from 1.1.1.1: icmp_seq=3 ttl=58 time=1.67 ms
64 bytes from 1.1.1.1: icmp_seq=4 ttl=58 time=1.89 ms

--- 1.1.1.1 ping statistics ---
4 packets transmitted, 4 received, 0% packet loss, time 3004ms
rtt min/avg/max/mdev = 1.230/1.560/1.890/0.240 ms`

	result := ParsePingStats(output)

	if result.PacketLoss != 0 {
		t.Errorf("expected 0%% loss, got %f%%", result.PacketLoss)
	}
	if result.LatencyMin != 1.23 {
		t.Errorf("expected min 1.23, got %f", result.LatencyMin)
	}
	if result.LatencyAvg != 1.56 {
		t.Errorf("expected avg 1.56, got %f", result.LatencyAvg)
	}
	if result.LatencyMax != 1.89 {
		t.Errorf("expected max 1.89, got %f", result.LatencyMax)
	}
}

func TestParsePingStatsWithLoss(t *testing.T) {
	output := `PING 1.1.1.1 (1.1.1.1) 56(84) bytes of data.

--- 1.1.1.1 ping statistics ---
4 packets transmitted, 2 received, 50% packet loss, time 3004ms
rtt min/avg/max/mdev = 1.230/1.560/1.890/0.320 ms`

	result := ParsePingStats(output)

	if result.PacketLoss != 50 {
		t.Errorf("expected 50%% loss, got %f%%", result.PacketLoss)
	}
}

func TestParseTracerouteStats(t *testing.T) {
	output := `traceroute to 1.1.1.1 (1.1.1.1), 30 hops max, 60 byte packets
 1  gateway (192.168.1.1)  1.234 ms  1.123 ms  1.098 ms
 2  10.0.0.1 (10.0.0.1)  5.678 ms  5.456 ms  5.234 ms
 3  1.1.1.1 (1.1.1.1)  10.123 ms  10.456 ms  10.789 ms`

	result := ParseTracerouteStats(output)

	if result.HopCount != 3 {
		t.Errorf("expected 3 hops, got %d", result.HopCount)
	}
}

func TestParseDNSStats(t *testing.T) {
	output := `;; Query time: 23 msec`

	result := ParseDNSStats(output)

	if result.LatencyAvg != 23 {
		t.Errorf("expected 23ms latency, got %f", result.LatencyAvg)
	}
}

func TestOutputToJSON(t *testing.T) {
	json := OutputToJSON("test line")
	if json == "" {
		t.Error("OutputToJSON returned empty string")
	}
	if json != `{"line":"test line"}` {
		t.Errorf("unexpected JSON: %s", json)
	}
}
