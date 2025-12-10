#!/bin/bash
# Wrapper script for Laravel Sail - handles docker group permissions

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Check if we can access docker
if docker ps > /dev/null 2>&1; then
    # Docker is accessible, run sail normally
    exec ./vendor/bin/sail "$@"
else
    # Docker is not accessible, need to activate docker group
    # Use exec to replace current shell with newgrp
    exec newgrp docker
fi
