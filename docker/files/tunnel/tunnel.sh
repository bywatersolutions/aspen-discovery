#!/bin/bash

set -e

# =============================================================================
# SSH Tunnel Script with Environment Variable Validation
# =============================================================================

echo "=== SSH Tunnel Startup ==="
echo "User: $(whoami) (UID: $(id -u), GID: $(id -g))"
echo "Timestamp: $(date)"

# =============================================================================
# Environment Variable Validation
# =============================================================================

REQUIRED_VARS=(
    "TUNNEL_USER"
    "TUNNEL_LOCAL_PORT"
    "TUNNEL_REMOTE_HOST"
    "TUNNEL_REMOTE_PORT"
    "TUNNEL_JUMP_USER"
    "TUNNEL_JUMP_SERVER"
)

echo "=== Validating Environment Variables ==="
MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
    if [[ -z "${!var}" ]]; then
        MISSING_VARS+=("$var")
        echo "❌ $var: NOT SET"
    else
        echo "✅ $var: ${!var}"
    fi
done

if [[ ${#MISSING_VARS[@]} -gt 0 ]]; then
    echo ""
    echo "❌ ERROR: Missing required environment variables:"
    printf '   - %s\n' "${MISSING_VARS[@]}"
    echo ""
    echo "Please set all required variables before starting the tunnel."
    exit 1
fi

# =============================================================================
# SSH Configuration Setup
# =============================================================================

echo "=== Setting up SSH Configuration ==="

# This section runs as root
if [[ $(id -u) -eq 0 ]]; then
    echo "Running initial setup as root..."

    # Change UID/GID to match LOCAL_USER_ID if specified
    if [[ -n "${LOCAL_USER_ID}" ]]; then
        CURRENT_UID=$(id -u ${TUNNEL_USER})
        LOCAL_GID=${LOCAL_GROUP_ID:-${LOCAL_USER_ID}}  # Use LOCAL_GROUP_ID or default to LOCAL_USER_ID
        
        if [[ "${CURRENT_UID}" != "${LOCAL_USER_ID}" ]]; then
            echo "Changing ${TUNNEL_USER} UID:GID from ${CURRENT_UID}:$(id -g ${TUNNEL_USER}) to ${LOCAL_USER_ID}:${LOCAL_GID}..."
            
            # Change group first
            groupmod -o -g ${LOCAL_GID} ${TUNNEL_USER}
            # Change user
            usermod -o -u ${LOCAL_USER_ID} ${TUNNEL_USER}
            
            # Fix ownership of home directory
            chown -R ${LOCAL_USER_ID}:${LOCAL_GID} /home/${TUNNEL_USER}
        else
            echo "✅ UID already matches: ${LOCAL_USER_ID}"
        fi
    else
        echo "LOCAL_USER_ID not set, using default UID $(id -u ${TUNNEL_USER})"
    fi
    
    SSH_DIR="/home/${TUNNEL_USER}/.ssh"
    
    # Fix SSH directory ownership and permissions
    if [[ -d "${SSH_DIR}" ]]; then
        echo "🔑 Setting up SSH keys for ${TUNNEL_USER}..."
        chown -R ${TUNNEL_USER}:${TUNNEL_USER} "${SSH_DIR}"
        chmod 700 "${SSH_DIR}"
        
        # Fix individual key permissions
        if [[ -f "${SSH_DIR}/id_rsa" ]]; then
            chmod 600 "${SSH_DIR}/id_rsa"
        fi
        if [[ -f "${SSH_DIR}/id_rsa.pub" ]]; then
            chmod 644 "${SSH_DIR}/id_rsa.pub"
        fi
        if [[ -f "${SSH_DIR}/authorized_keys" ]]; then
            chmod 600 "${SSH_DIR}/authorized_keys"
        fi

        echo "✅ SSH permissions set"
    fi
    
    # Switch to TUNNEL_USER and re-execute this script
    echo "Switching to user ${TUNNEL_USER}..."
    exec su-exec ${TUNNEL_USER} "$0" "$@"
    # If su-exec is not available, use: exec su - ${TUNNEL_USER} -c "$0 $*"
fi

SSH_DIR="/home/${TUNNEL_USER}/.ssh"
SSH_KEY="${SSH_DIR}/id_rsa"

if [[ ! -f "${SSH_KEY}" ]]; then
    echo "❌ ERROR: SSH private key not found at ${SSH_KEY}"
    echo "Please mount your SSH private key to ${SSH_KEY}"
    exit 1
fi

echo "✅ SSH key found: ${SSH_KEY}"



# =============================================================================
# SSH Tunnel Establishment
# =============================================================================

echo "=== Establishing SSH Tunnel ==="
echo "Local: 0.0.0.0:${TUNNEL_LOCAL_PORT}"
echo "Remote: ${TUNNEL_REMOTE_HOST}:${TUNNEL_REMOTE_PORT}"
echo "Jump Server: ${TUNNEL_JUMP_USER}@${TUNNEL_JUMP_SERVER}"

# Start SSH tunnel in background
ssh -o StrictHostKeyChecking=no \
    -o ServerAliveInterval=60 \
    -o ServerAliveCountMax=3 \
    -i "${SSH_KEY}" \
    -N -g \
    -L "0.0.0.0:${TUNNEL_LOCAL_PORT}:${TUNNEL_REMOTE_HOST}:${TUNNEL_REMOTE_PORT}" \
    "${TUNNEL_JUMP_USER}@${TUNNEL_JUMP_SERVER}" &

SSH_PID=$!
echo "✅ SSH tunnel started (PID: ${SSH_PID})"

# =============================================================================
# Health Check and Monitoring
# =============================================================================

echo "=== Tunnel Health Monitoring ==="

# Verify SSH actually started
if [[ -z "${SSH_PID}" ]] || ! kill -0 "${SSH_PID}" 2>/dev/null; then
    echo "❌ ERROR: SSH tunnel failed to start (PID: ${SSH_PID})"
    exit 1
fi

echo "✅ Monitoring SSH tunnel (PID: ${SSH_PID})"

# Function to check if tunnel is still running
check_tunnel() {
    if kill -0 "${SSH_PID}" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Cleanup function
cleanup() {
    echo "🔄 Shutting down tunnel..."
    if [[ -n "${SSH_PID}" ]] && kill -0 "${SSH_PID}" 2>/dev/null; then
        kill "${SSH_PID}"
        wait "${SSH_PID}" 2>/dev/null
        echo "✅ Tunnel stopped"
    fi
    exit 0
}

# Set up signal handlers
trap cleanup TERM INT QUIT

# Keep container running and monitor tunnel
CHECK_COUNT=0
while true; do
    if ! check_tunnel; then
        CHECK_COUNT=$((CHECK_COUNT + 1))
        echo "WARNING: SSH tunnel check failed (attempt ${CHECK_COUNT}/3)"
        
        # Give it some grace - maybe it's restarting or there's a transient issue
        if [ ${CHECK_COUNT} -ge 3 ]; then
            echo "ERROR: SSH tunnel process died (PID: ${SSH_PID})"
            echo "Timestamp: $(date)"
            
            # Get exit status if available
            wait "${SSH_PID}" 2>/dev/null
            EXIT_CODE=$?
            echo "SSH exit code: ${EXIT_CODE}"
            
            # Show what processes are running
            echo "Current SSH processes:"
            pgrep -a ssh || echo "No SSH processes found"
            
            exit 1
        fi
        
        sleep 10  # Wait before next check
    else
        # Reset counter if tunnel is healthy
        if [ ${CHECK_COUNT} -gt 0 ]; then
            echo "✅ Tunnel recovered"
        fi
        CHECK_COUNT=0
        sleep 30
    fi
done