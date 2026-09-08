package system

import (
	"os"
	"runtime"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/host"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
)

type Stats struct {
	Hostname         string  `json:"hostname"`
	OS               string  `json:"os"`
	CPUUsagePercent  float64 `json:"cpu_usage_percent"`
	MemoryUsagePercent float64 `json:"memory_usage_percent"`
	DiskUsagePercent float64 `json:"disk_usage_percent"`
	UptimeSeconds    uint64  `json:"uptime_seconds"`
	LoadAverage      float64 `json:"load_average"`
}

// GetStats returns current system statistics.
func GetStats() Stats {
	s := Stats{
		Hostname: getHostname(),
		OS:       runtime.GOOS,
	}

	// CPU usage (average over 1 second)
	if percents, err := cpu.Percent(time.Second, false); err == nil && len(percents) > 0 {
		s.CPUUsagePercent = percents[0]
	}

	// Memory usage
	if v, err := mem.VirtualMemory(); err == nil {
		s.MemoryUsagePercent = v.UsedPercent
	}

	// Disk usage (root partition)
	if usage, err := disk.Usage("/"); err == nil {
		s.DiskUsagePercent = usage.UsedPercent
	}

	// Uptime
	if info, err := host.Uptime(); err == nil {
		s.UptimeSeconds = info
	}

	// Load average
	if avg, err := load.Avg(); err == nil {
		s.LoadAverage = avg.Load1
	}

	return s
}

func getHostname() string {
	h, err := os.Hostname()
	if err != nil {
		return "unknown"
	}
	return h
}
