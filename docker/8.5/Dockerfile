FROM sail-8.5/app

USER root

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
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/chromium.deb

# Ensure /.composer directory exists and has correct permissions
RUN mkdir -p /.composer && chmod -R ugo+rw /.composer

# Set chromium path for Browsershot - try multiple locations
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV CHROMIUM_PATH=/usr/bin/chromium

# Keep as root - start-container will handle user switching
# USER sail

