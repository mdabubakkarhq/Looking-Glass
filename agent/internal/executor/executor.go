package executor

import (
	"encoding/json"
	"fmt"
	"net"
	"regexp"
	"strconv"
	"strings"
	"time"
)

// Result represents the final statistics of a test execution.
type Result struct {
	ResolvedIP  string  `json:"resolved_ip,omitempty"`
	PacketLoss  float64 `json:"packet_loss,omitempty"`
	LatencyMin  float64 `json:"latency_min,omitempty"`
	LatencyAvg  float64 `json:"latency_avg,omitempty"`
	LatencyMax  float64 `json:"latency_max,omitempty"`
	LatencyStd  float64 `json:"latency_stddev,omitempty"`
	HopCount    int     `json:"hop_count,omitempty"`
}

// OutputLine represents a single line of test output.
type OutputLine struct {
	Line string `json:"line"`
}

// blockedNetworks are private/reserved ranges that must not be targeted.
var blockedNetworks = []string{
	"0.0.0.0/8", "10.0.0.0/8", "100.64.0.0/10", "127.0.0.0/8",
	"169.254.0.0/16", "172.16.0.0/12", "192.0.0.0/24", "192.168.0.0/16",
	"198.18.0.0/15", "224.0.0.0/4", "240.0.0.0/4",
	"::1/128", "fc00::/7", "fe80::/10", "ff00::/8",
}

// ValidateTarget checks if a target is safe to test against.
func ValidateTarget(target string) error {
	if target == "" {
		return fmt.Errorf("target is required")
	}
	if len(target) > 255 {
		return fmt.Errorf("target too long")
	}

	if ip := net.ParseIP(target); ip != nil {
		if isBlockedIP(ip) {
			return fmt.Errorf("target is in a private/reserved network range")
		}
		return nil
	}

	ips, err := net.LookupIP(target)
	if err != nil {
		return fmt.Errorf("failed to resolve hostname: %w", err)
	}
	for _, ip := range ips {
		if isBlockedIP(ip) {
			return fmt.Errorf("resolved IP %s is in a private/reserved network range", ip)
		}
	}
	return nil
}

func isBlockedIP(ip net.IP) bool {
	for _, cidr := range blockedNetworks {
		_, network, err := net.ParseCIDR(cidr)
		if err != nil {
			continue
		}
		if network.Contains(ip) {
			return true
		}
	}
	return ip.Equal(net.ParseIP("169.254.169.254"))
}

// ParsePingStats extracts statistics from ping output.
func ParsePingStats(output string) Result {
	result := Result{}
	if m := regexp.MustCompile(`(\d+(?:\.\d+)?)% packet loss`).FindStringSubmatch(output); len(m) > 1 {
		result.PacketLoss, _ = strconv.ParseFloat(m[1], 64)
	}
	if m := regexp.MustCompile(`(?:rtt|round-trip) min/avg/max/(?:mdev|stddev) = ([\d.]+)/([\d.]+)/([\d.]+)/([\d.]+)`).FindStringSubmatch(output); len(m) > 4 {
		result.LatencyMin, _ = strconv.ParseFloat(m[1], 64)
		result.LatencyAvg, _ = strconv.ParseFloat(m[2], 64)
		result.LatencyMax, _ = strconv.ParseFloat(m[3], 64)
		result.LatencyStd, _ = strconv.ParseFloat(m[4], 64)
	}
	return result
}

// ParseTracerouteStats extracts hop count from traceroute output.
func ParseTracerouteStats(output string) Result {
	result := Result{}
	hopSet := make(map[string]bool)
	for _, line := range strings.Split(output, "\n") {
		if m := regexp.MustCompile(`^\s*(\d+)\s+`).FindStringSubmatch(strings.TrimSpace(line)); len(m) > 1 {
			hopSet[m[1]] = true
		}
	}
	result.HopCount = len(hopSet)
	return result
}

// ParseMTRStats extracts statistics from MTR output.
func ParseMTRStats(output string) Result {
	result := Result{}
	for _, line := range strings.Split(output, "\n") {
		trimmed := strings.TrimSpace(line)
		if trimmed == "" || strings.HasPrefix(trimmed, "HOST:") || strings.HasPrefix(trimmed, "Start:") {
			continue
		}
		if m := regexp.MustCompile(`^\s*(\d+)\.\|--\s+`).FindStringSubmatch(trimmed); len(m) > 1 {
			if hopNum, err := strconv.Atoi(m[1]); err == nil && hopNum > result.HopCount {
				result.HopCount = hopNum
			}
		}
		if m := regexp.MustCompile(`(\d+(?:\.\d+)?)%\s+\d+\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)`).FindStringSubmatch(trimmed); len(m) > 6 {
			avg, _ := strconv.ParseFloat(m[3], 64)
			if avg > result.LatencyAvg {
				result.PacketLoss, _ = strconv.ParseFloat(m[1], 64)
				result.LatencyMin, _ = strconv.ParseFloat(m[4], 64)
				result.LatencyAvg = avg
				result.LatencyMax, _ = strconv.ParseFloat(m[5], 64)
				result.LatencyStd, _ = strconv.ParseFloat(m[6], 64)
			}
		}
	}
	return result
}

// ParseDNSStats extracts timing from dig output.
func ParseDNSStats(output string) Result {
	result := Result{}
	if m := regexp.MustCompile(`Query time:\s*(\d+)\s*msec`).FindStringSubmatch(output); len(m) > 1 {
		result.LatencyAvg, _ = strconv.ParseFloat(m[1], 64)
	}
	return result
}

// OutputToJSON converts a line of text to JSON for event data.
func OutputToJSON(line string) string {
	data, _ := json.Marshal(OutputLine{Line: line})
	return string(data)
}

// Now returns current time in ISO format.
func Now() string {
	return time.Now().UTC().Format(time.RFC3339)
}
