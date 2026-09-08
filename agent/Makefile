.PHONY: build clean test install

BINARY_NAME=lg-agent
VERSION=$(shell git describe --tags --always --dirty 2>/dev/null || echo "dev")
BUILD_DIR=build

build:
	@mkdir -p $(BUILD_DIR)
	go build -ldflags "-X main.version=$(VERSION)" -o $(BUILD_DIR)/$(BINARY_NAME) ./cmd/agent

build-linux:
	@mkdir -p $(BUILD_DIR)
	GOOS=linux GOARCH=amd64 go build -ldflags "-X main.version=$(VERSION)" -o $(BUILD_DIR)/$(BINARY_NAME)-linux-amd64 ./cmd/agent
	GOOS=linux GOARCH=arm64 go build -ldflags "-X main.version=$(VERSION)" -o $(BUILD_DIR)/$(BINARY_NAME)-linux-arm64 ./cmd/agent

test:
	go test ./...

clean:
	rm -rf $(BUILD_DIR)

install: build
	install -m 755 $(BUILD_DIR)/$(BINARY_NAME) /usr/local/bin/$(BINARY_NAME)
	@mkdir -p /etc/lg-agent
	@mkdir -p /var/log/lg-agent
	@echo "Installed $(BINARY_NAME) to /usr/local/bin/"
	@echo "Run 'lg-agent register' to register with controller"

lint:
	go vet ./...
	staticcheck ./...
