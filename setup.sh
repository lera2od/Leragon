#!/bin/bash

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}[LERAGON SETUP] Starting Leragon setup...${NC}"

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[LERAGON SETUP] Please run as root or with sudo${NC}"
    exit 1
fi

if command_exists getenforce; then
    selinux_status=$(getenforce)
    if [ "$selinux_status" = "Enforcing" ]; then
        echo -e "${YELLOW}[LERAGON SETUP] SELinux is currently enforcing. This might cause issues with Docker.${NC}"
        read -p "Would you like to temporarily disable SELinux? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            setenforce 0
            echo -e "${GREEN}[LERAGON SETUP] SELinux temporarily disabled${NC}"
            echo -e "${YELLOW}[LERAGON SETUP] Note: To permanently disable SELinux, edit /etc/selinux/config${NC}"
        fi
    fi
fi

if ! command_exists docker; then
    echo -e "${YELLOW}[LERAGON SETUP] Docker not found. Install Docker first before trying to install a fucking docker manager.${NC}"
fi

if ! command_exists docker-compose; then
    echo -e "${YELLOW}[LERAGON SETUP] Docker Compose not found. Install Docker Compose first.${NC}"
fi

DOCKER_GID=$(getent group docker | cut -d: -f3)

echo -e "${GREEN}[LERAGON SETUP] Building Docker image...${NC}"
docker build --build-arg DOCKER_GID=$DOCKER_GID -t leragon .

mkdir -p app

echo -e "${GREEN}[LERAGON SETUP] Removing old containers...${NC}"
docker-compose --project-name "leragon" down

echo -e "${GREEN}[LERAGON SETUP] Starting containers...${NC}"
docker-compose --project-name "leragon" up -d

if [ "$(docker ps -q -f name=leragon)" ]; then
    echo -e "${GREEN}[LERAGON SETUP] Setup completed successfully!${NC}"
    echo -e "You can access Leragon at ${YELLOW}http://localhost:3001${NC}"
else
    echo -e "${RED}[LERAGON SETUP] Setup failed. Please check the logs using: docker-compose logs${NC}"
fi

if ! groups "$USER" | grep &>/dev/null '\bdocker\b'; then
    usermod -aG docker "$USER"
    echo -e "${YELLOW}[LERAGON SETUP] Added current user to docker group. Please log out and back in for changes to take effect.${NC}"
fi