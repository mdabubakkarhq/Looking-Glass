package main

import (
	"fmt"
	"os"

	"github.com/openlookingglass/lg-agent/internal/config"
	"github.com/openlookingglass/lg-agent/internal/dispatcher"
	"github.com/openlookingglass/lg-agent/internal/heartbeat"
	"github.com/openlookingglass/lg-agent/internal/server"
)

const version = "1.0.0"

func main() {
	if len(os.Args) < 2 {
		runServe(nil)
		return
	}

	switch os.Args[1] {
	case "register":
		runRegister(os.Args[2:])
	case "serve":
		runServe(os.Args[2:])
	case "version":
		fmt.Printf("lg-agent %s\n", version)
	case "help", "--help", "-h":
		printUsage()
	default:
		fmt.Fprintf(os.Stderr, "Unknown command: %s\n", os.Args[1])
		printUsage()
		os.Exit(1)
	}
}

func printUsage() {
	fmt.Println(`Usage: lg-agent [command] [flags]

Commands:
  register    Register this agent with a controller
  serve       Start the agent server (default)
  version     Print version

Run 'lg-agent [command] --help' for more information on a specific command.`)
}

func runRegister(args []string) {
	flags := parseRegisterFlags(args)

	if flags.ControllerURL == "" || flags.Token == "" {
		fmt.Fprintln(os.Stderr, "Error: --controller and --token are required")
		fmt.Fprintln(os.Stderr, "Usage: lg-agent register --controller=https://lg.example.com --token=REG_TOKEN")
		os.Exit(1)
	}

	if err := doRegister(flags); err != nil {
		fmt.Fprintf(os.Stderr, "Registration failed: %v\n", err)
		os.Exit(1)
	}
}

func runServe(args []string) {
	cfg, err := config.Load(args)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Configuration error: %v\n", err)
		os.Exit(1)
	}

	if cfg.NodeKeyID == "" || cfg.NodeSecret == "" {
		fmt.Fprintln(os.Stderr, "Error: agent not registered. Run 'lg-agent register' first.")
		os.Exit(1)
	}

	fmt.Printf("lg-agent %s starting on %s\n", version, cfg.ListenAddr)
	fmt.Printf("Controller: %s\n", cfg.ControllerURL)
	fmt.Printf("Node ID: %s\n", cfg.NodeID)

	// Start heartbeat sender
	hb := heartbeat.New(cfg, version)
	go hb.Start()

	// Start dispatcher (receives tests from controller)
	disp := dispatcher.New(cfg)

	// Start HTTP server
	srv := server.New(cfg, disp)
	if err := srv.Start(); err != nil {
		fmt.Fprintf(os.Stderr, "Server error: %v\n", err)
		os.Exit(1)
	}
}
