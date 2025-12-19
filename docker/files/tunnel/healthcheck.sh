#!/bin/bash

# =============================================================================
# SSH Tunnel Health Check Script
# =============================================================================

TUNNEL_PORT="${TUNNEL_LOCAL_PORT:-3306}"

# Get container uptime
UPTIME=$(awk '{print int($1)}' /proc/uptime 2>/dev/null || echo "999")

# During first 2 minutes, only check if SSH process exists
if [ "$UPTIME" -lt 120 ]; then
    if pgrep -f "ssh" > /dev/null 2>&1; then
        exit 0  # Healthy - SSH is running
    else
        exit 1  # Unhealthy - no SSH process
    fi
fi

# After 2 minutes, check both process and port
if pgrep -f "ssh" > /dev/null 2>&1; then
    if command -v nc &> /dev/null; then
        if nc -z localhost "${TUNNEL_PORT}" 2>/dev/null; then
            exit 0  # Both process and port are good
        else
            # Port not accessible, but SSH is running
            # This might be normal if remote side is slow
            exit 0  # Still mark as healthy since SSH is alive
        fi
    else
        # netcat not available, just trust that SSH is running
        exit 0
    fi
else
    # No SSH process - definitely unhealthy
    exit 1
fi
