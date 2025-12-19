# =============================================================================
# SSH Tunnel Container
# Provides secure SSH tunneling for database connections
# =============================================================================

FROM alpine:3.19

# =============================================================================
# Build Arguments and Environment Variables
# =============================================================================

ARG TUNNEL_USER=tunnel
ENV TUNNEL_USER=${TUNNEL_USER}


# =============================================================================
# System Dependencies Installation
# =============================================================================

RUN echo "=== Installing system dependencies ===" && \
    apk add --no-cache \
        openssh-client \
        ca-certificates \
        bash \
        sudo \
        curl \
        su-exec \
        shadow \
        netcat-openbsd && \
    echo "✅ Dependencies installed"

# =============================================================================
# User and Group Management
# =============================================================================

RUN echo "=== Setting up user and group ===" && \
    # Create group first
    addgroup -g 1000 ${TUNNEL_USER} && \
    # Create user with home directory
    adduser -D -u 1000 -G ${TUNNEL_USER} -s /bin/bash ${TUNNEL_USER} && \
    # Add user to sudoers for troubleshooting
    echo "${TUNNEL_USER} ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/${TUNNEL_USER} && \
    chmod 0440 /etc/sudoers.d/${TUNNEL_USER} && \
    echo "✅ User ${TUNNEL_USER} created (UID: 1000, GID: 1000)"

# =============================================================================
# SSH Configuration Setup
# =============================================================================

RUN echo "=== Setting up SSH configuration ===" && \
    # Create SSH directory with proper permissions
    mkdir -p /home/${TUNNEL_USER}/.ssh && \
    chmod 700 /home/${TUNNEL_USER}/.ssh && \
    # Set ownership
    chown -R ${TUNNEL_USER}:${TUNNEL_USER} /home/${TUNNEL_USER}/.ssh && \
    echo "✅ SSH directory created"

# =============================================================================
# Application Files
# =============================================================================

# Copy SSH configuration
COPY docker/files/tunnel/config /home/${TUNNEL_USER}/.ssh/config

# Copy and setup tunnel script
COPY docker/files/tunnel/tunnel.sh /tunnel.sh
COPY docker/files/tunnel/healthcheck.sh /healthcheck.sh

RUN echo "=== Setting up application files ===" && \
    # Make scripts executable
    chmod +x /tunnel.sh /healthcheck.sh && \
    # Set proper ownership for SSH config
    chown ${TUNNEL_USER}:${TUNNEL_USER} /home/${TUNNEL_USER}/.ssh/config && \
    chmod 600 /home/${TUNNEL_USER}/.ssh/config && \
    echo "✅ Application files configured"

# =============================================================================
# Runtime Configuration
# =============================================================================

# Set working directory
WORKDIR /home/${TUNNEL_USER}

# Health check to ensure tunnel is working
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD /healthcheck.sh

# Run as root to assing proper permissions in tunnel script
USER root

# Display runtime information
RUN echo "=== Container Build Complete ===" && \
    echo "User: ROOT" && \
    echo "Working Directory: /home/${TUNNEL_USER}" && \
    echo "SSH Config: /home/${TUNNEL_USER}/.ssh/config" && \
    echo "Tunnel Script: /tunnel.sh"

# =============================================================================
# Container Startup
# =============================================================================

CMD ["/tunnel.sh"]
