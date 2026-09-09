# frontend/Dockerfile — Multi-stage build for Vue frontend
#
# Stage 1: Build the Vue application
# Stage 2: Serve with NGINX

# ── Build Stage ─────────────────────────────────────────────
FROM node:20-alpine AS build

WORKDIR /app

# Copy package files and install dependencies
COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline

# Copy source and build
COPY . .
RUN npm run build

# ── Production Stage ────────────────────────────────────────
FROM nginx:alpine

# Copy built assets from build stage
COPY --from=build /app/dist /usr/share/nginx/html

# NGINX config is mounted via docker-compose volume
# but include a default fallback
RUN rm /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
