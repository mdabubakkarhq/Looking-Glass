package config

import (
	"flag"
	"fmt"
	"os"

	"gopkg.in/yaml.v3"
)

type Config struct {
	NodeID            string `yaml:"node_id"`
	NodeKeyID         string `yaml:"node_key_id"`
	NodeSecret        string `yaml:"node_secret"`
	ControllerURL     string `yaml:"controller_url"`
	ListenAddr        string `yaml:"listen_addr"`
	HeartbeatInterval int    `yaml:"heartbeat_interval"`
	MaxOutputBytes    int    `yaml:"max_output_bytes"`
	ConfigFile        string `yaml:"-"`
}

func Default() *Config {
	return &Config{
		ListenAddr:        ":8443",
		HeartbeatInterval: 30,
		MaxOutputBytes:    1048576, // 1MB
	}
}

func Load(args []string) (*Config, error) {
	cfg := Default()

	// First pass: find config file
	fs := flag.NewFlagSet("lg-agent", flag.ContinueOnError)
	configFile := fs.String("config", "/etc/lg-agent/config.yaml", "Path to config file")
	fs.Parse(args)

	cfg.ConfigFile = *configFile

	// Load from file
	if data, err := os.ReadFile(cfg.ConfigFile); err == nil {
		if err := yaml.Unmarshal(data, cfg); err != nil {
			return nil, fmt.Errorf("failed to parse config file %s: %w", cfg.ConfigFile, err)
		}
	} else if !os.IsNotExist(err) {
		return nil, fmt.Errorf("failed to read config file %s: %w", cfg.ConfigFile, err)
	}

	// Second pass: CLI flags override config file
	fs2 := flag.NewFlagSet("lg-agent", flag.ContinueOnError)
	fs2.StringVar(&cfg.ListenAddr, "listen", cfg.ListenAddr, "Address to listen on")
	fs2.StringVar(&cfg.ControllerURL, "controller", cfg.ControllerURL, "Controller URL")
	fs2.StringVar(&cfg.NodeKeyID, "node-key-id", cfg.NodeKeyID, "Node key ID")
	fs2.StringVar(&cfg.NodeSecret, "node-secret", cfg.NodeSecret, "Node secret")
	fs2.StringVar(&cfg.NodeID, "node-id", cfg.NodeID, "Node ID")
	fs2.IntVar(&cfg.HeartbeatInterval, "heartbeat-interval", cfg.HeartbeatInterval, "Heartbeat interval in seconds")
	fs2.Parse(args)

	// Environment variable overrides
	if v := os.Getenv("LG_NODE_KEY_ID"); v != "" {
		cfg.NodeKeyID = v
	}
	if v := os.Getenv("LG_NODE_SECRET"); v != "" {
		cfg.NodeSecret = v
	}
	if v := os.Getenv("LG_CONTROLLER_URL"); v != "" {
		cfg.ControllerURL = v
	}
	if v := os.Getenv("LG_LISTEN_ADDR"); v != "" {
		cfg.ListenAddr = v
	}
	if v := os.Getenv("LG_NODE_ID"); v != "" {
		cfg.NodeID = v
	}

	return cfg, nil
}
