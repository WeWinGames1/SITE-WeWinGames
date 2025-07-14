#!/bin/bash

# Extreme production build script for severe file descriptor limits
# This script uses a step-by-step approach to minimize concurrent file operations

echo "Starting extreme production build with minimal file operations..."

# Try to increase ulimit (may not work without sudo)
ulimit -n 4096 2>/dev/null
echo "Current file descriptor limit: $(ulimit -n)"

# Clear any existing build artifacts
echo "Cleaning previous build..."
rm -rf public/build
rm -rf node_modules/.vite

# Set Node.js options to limit operations
export NODE_OPTIONS="--max-old-space-size=1024"

# Disable all parallelism
export UV_THREADPOOL_SIZE=1

# Clear npm cache to prevent cache file operations
echo "Clearing npm cache..."
npm cache clean --force 2>/dev/null || true

# Create a minimal temporary tsconfig for build
echo "Creating minimal TypeScript config..."
cat > tsconfig.build.json << 'EOF'
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "node",
    "strict": false,
    "jsx": "preserve",
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "allowJs": true,
    "types": ["vite/client"],
    "paths": {
      "@/*": ["./resources/js/*"]
    }
  },
  "include": ["resources/js/**/*"],
  "exclude": ["node_modules", "public", "vendor"]
}
EOF

# Build with standard vite config but limited resources
echo "Building with limited resources..."
TS_NODE_FILES=false npx vite build

# Clean up temporary files
rm -f tsconfig.build.json

echo "Build complete!"
echo "Files created in public/build/"
ls -la public/build/