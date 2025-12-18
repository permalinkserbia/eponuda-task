FROM laravelsail/php84-composer:latest

USER root

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Install Chromium via direct download (snap doesn't work in Docker)
# Download Chromium binary directly
RUN apt-get update && \
    apt-get install -y \
        wget \
        ca-certificates \
        libnss3 \
        libatk-bridge2.0-0 \
        libdrm2 \
        libxkbcommon0 \
        libxcomposite1 \
        libxdamage1 \
        libxfixes3 \
        libxrandr2 \
        libgbm1 \
        libasound2t64 \
        libpango-1.0-0 \
        libcairo2 \
        libatspi2.0-0 \
    && wget -q -O /tmp/chromium.deb https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb || \
    (apt-get install -y chromium && ln -sf /usr/bin/chromium /usr/bin/chromium-browser) || \
    (apt-get install -y chromium-browser || true) \
    && docker-php-ext-install pdo_mysql \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/chromium.deb

# Ensure /.composer directory exists and has correct permissions
RUN mkdir -p /.composer && chmod -R ugo+rw /.composer

# Create sail user with dynamic UID/GID support for Sail commands
ARG WWWUSER=1000
ARG WWWGROUP=1000

# Create sail user and group (needed for Sail's -u sail:sail flag)
RUN if ! getent group sail > /dev/null 2>&1; then \
        groupadd -g ${WWWGROUP} sail; \
    else \
        groupmod -g ${WWWGROUP} sail; \
    fi && \
    if ! getent passwd sail > /dev/null 2>&1; then \
        useradd -u ${WWWUSER} -g sail -m -s /bin/bash sail; \
    else \
        usermod -u ${WWWUSER} -g sail sail; \
    fi

# Ensure storage and bootstrap/cache directories are writable by sail user
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/bootstrap/cache && \
    chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Set chromium path for Browsershot - try multiple locations
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV CHROMIUM_PATH=/usr/bin/chromium

# Keep as root - needed to bind to port 80, but sail user exists for Sail commands
USER root

