FROM node:24-alpine AS build

ENV HUSKY=0
RUN npm install -g pnpm@11

WORKDIR /repo
COPY . .

ARG PUBLIC_API_URL
ENV PUBLIC_API_URL=${PUBLIC_API_URL}

ARG PUBLIC_API_URL
ARG PUBLIC_REVERB_HOST
ARG PUBLIC_REVERB_PORT=443
ARG PUBLIC_REVERB_SCHEME=https
ARG PUBLIC_REVERB_KEY
ENV PUBLIC_API_URL=$PUBLIC_API_URL \
    PUBLIC_REVERB_HOST=$PUBLIC_REVERB_HOST \
    PUBLIC_REVERB_PORT=$PUBLIC_REVERB_PORT \
    PUBLIC_REVERB_SCHEME=$PUBLIC_REVERB_SCHEME \
    PUBLIC_REVERB_KEY=$PUBLIC_REVERB_KEY

RUN pnpm install --frozen-lockfile

RUN pnpm --filter web build \
    && pnpm --filter panel build

FROM caddy:2-alpine

COPY infra/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=build /repo/apps/web/dist /srv/web
COPY --from=build /repo/apps/panel/dist/spa /srv/panel
