package executor

import (
	"bufio"
	"context"
	"fmt"
	"io"
	"os/exec"
	"strconv"
	"strings"
	"sync"
)

// RunFunc is a callback invoked for each line of output.
type RunFunc func(line string)

// RunTest executes a network test and streams output via the callback.
func RunTest(ctx context.Context, testType, target, ipFamily string, timeout int, onOutput RunFunc) (Result, error) {
	switch testType {
	case "ping":
		return runPing(ctx, target, ipFamily, timeout, onOutput)
	case "traceroute":
		return runTraceroute(ctx, target, ipFamily, timeout, onOutput)
	case "mtr":
		return runMTR(ctx, target, ipFamily, timeout, onOutput)
	case "dns":
		return runDNS(ctx, target, ipFamily, timeout, onOutput)
	default:
		return Result{}, fmt.Errorf("unsupported test type: %s", testType)
	}
}

func familyFlag(ipFamily string, v4, v6 string) string {
	switch ipFamily {
	case "ipv4":
		return v4
	case "ipv6":
		return v6
	default:
		return ""
	}
}

func runPing(ctx context.Context, target, ipFamily string, timeout int, onOutput RunFunc) (Result, error) {
	args := []string{"-c", "4", "-W", strconv.Itoa(timeout)}
	if ff := familyFlag(ipFamily, "-4", "-6"); ff != "" {
		args = append([]string{ff}, args...)
	}
	args = append(args, target)
	cmd := exec.CommandContext(ctx, "ping", args...)
	return runAndParse(cmd, onOutput, ParsePingStats)
}

func runTraceroute(ctx context.Context, target, ipFamily string, timeout int, onOutput RunFunc) (Result, error) {
	args := []string{"-m", "30", "-w", "3"}
	if ff := familyFlag(ipFamily, "-4", "-6"); ff != "" {
		args = append([]string{ff}, args...)
	}
	args = append(args, target)
	cmd := exec.CommandContext(ctx, "traceroute", args...)
	return runAndParse(cmd, onOutput, ParseTracerouteStats)
}

func runMTR(ctx context.Context, target, ipFamily string, timeout int, onOutput RunFunc) (Result, error) {
	args := []string{"-r", "-c", "10", "-w"}
	if ff := familyFlag(ipFamily, "-4", "-6"); ff != "" {
		args = append([]string{ff}, args...)
	}
	args = append(args, target)
	cmd := exec.CommandContext(ctx, "mtr", args...)
	return runAndParse(cmd, onOutput, ParseMTRStats)
}

func runDNS(ctx context.Context, target, ipFamily string, timeout int, onOutput RunFunc) (Result, error) {
	qtype := "A"
	switch ipFamily {
	case "ipv6":
		qtype = "AAAA"
	case "auto":
		qtype = "ANY"
	}
	cmd := exec.CommandContext(ctx, "dig", "+short", target, qtype)
	return runAndParse(cmd, onOutput, ParseDNSStats)
}

type parseFunc func(output string) Result

func runAndParse(cmd *exec.Cmd, onOutput RunFunc, parse parseFunc) (Result, error) {
	stdout, err := cmd.StdoutPipe()
	if err != nil {
		return Result{}, fmt.Errorf("stdout pipe: %w", err)
	}
	stderr, err := cmd.StderrPipe()
	if err != nil {
		return Result{}, fmt.Errorf("stderr pipe: %w", err)
	}

	if err := cmd.Start(); err != nil {
		return Result{}, fmt.Errorf("start command: %w", err)
	}

	var fullOutput strings.Builder
	var wg sync.WaitGroup
	wg.Add(2)
	go func() { defer wg.Done(); streamReader(stdout, onOutput, &fullOutput) }()
	go func() { defer wg.Done(); streamReader(stderr, onOutput, &fullOutput) }()
	wg.Wait()

	waitErr := cmd.Wait()
	output := fullOutput.String()
	result := parse(output)

	if waitErr != nil {
		if _, ok := waitErr.(*exec.ExitError); ok {
			return result, nil
		}
		return result, waitErr
	}
	return result, nil
}

func streamReader(r io.ReadCloser, onOutput RunFunc, full *strings.Builder) {
	scanner := bufio.NewScanner(r)
	for scanner.Scan() {
		line := scanner.Text()
		full.WriteString(line + "\n")
		if onOutput != nil {
			onOutput(line)
		}
	}
}
