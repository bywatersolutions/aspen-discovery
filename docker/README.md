# Aspen Discovery - Docker

## Quick Start

```bash
curl -O https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/26.07.00/docker/docker-compose.yml
curl -O https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/26.07.00/docker/files/env/default.env
cp default.env .env
```

Edit `.env` to set your site name, URL, and admin password, then start:

```bash
docker compose up -d
```

Wait for initialization to complete:
```bash
docker compose logs -f backend
# Look for: "Starting PHP-FPM in foreground mode..."
```

Access Aspen at http://localhost:85 (default credentials: `aspen_admin` / `secretPass123`)

## Environment Variables

### Site Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `SITE_NAME` | `aspen` | Site identifier |
| `URL` | `http://localhost:85` | Public URL |
| `TITLE` | `Aspen Discovery` | Library title |
| `LIBRARY` | `Test Library` | Library name |
| `TZ` | `America/Argentina/Cordoba` | Container and PHP timezone |
| `ASPEN_ADMIN_PASSWORD` | `secretPass123` | Admin password — **change before going live** |
| `ASPEN_HOME` | `./aspen` | Directory where instance files are stored (conf, data, logs) |
| `ASPEN_ADMIN_PASSWORD` | `secretPass123` | Admin password |
| `SUPPORTING_COMPANY` | `ByWater Solutions` | Support company name |

### Database

| Variable | Default | Description |
|----------|---------|-------------|
| `DATABASE_HOST` | `db` | MariaDB hostname |
| `DATABASE_PORT` | `3306` | MariaDB port |
| `DATABASE_NAME` | `aspen` | Database name |
| `DATABASE_USER` | `aspenusr` | Database user |
| `DATABASE_PASSWORD` | `aspenpasswd` | Database password |
| `DATABASE_ROOT_PASSWORD` | `password` | MariaDB root password |
| `DATABASE_SSL_MODE` | `DISABLED` | When `DISABLED`, the mariadb CLI client connects with `--skip-ssl` (needed since mariadb-client 11.x defaults to requiring TLS). Set to `REQUIRED` only when the db service has TLS configured, e.g. via a compose overlay adding certs. |

### Services

| Variable | Default | Description |
|----------|---------|-------------|
| `SOLR_HOST` | `solr` | Solr hostname |
| `SOLR_PORT` | `8983` | Solr port |
| `PHP_FPM_HOST` | `backend` | PHP-FPM hostname |
| `PHP_FPM_PORT` | `9000` | PHP-FPM port |

### Docker Images

| Variable | Default | Description |
|----------|---------|-------------|
| `BACKEND_IMAGE_TAG` | `aspendiscovery/aspen:latest` | Backend image |
| `SOLR_IMAGE_TAG` | `aspendiscovery/solr:latest` | Solr image |
| `MARIADB_IMAGE` | `mariadb:10.5` | MariaDB image |

## Updating Configuration

Environment variables are synced to configuration files on every container start. To apply changes:

1. Edit your `.env` file
2. Recreate containers:
   ```bash
   docker compose up -d --force-recreate
   ```

**Supported variables for runtime updates:**
- `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`
- `TZ`, `URL`, `TITLE`, `LIBRARY`
- `SOLR_HOST`, `SOLR_PORT`

**Note:** Changing database credentials requires the MariaDB user to already exist. MariaDB only creates users on first initialization.

## Services & Ports

| Service | Port | Description |
|---------|------|-------------|
| apache | 85 | Web interface |
| solr | 8983 | Search engine admin |
| backend | - | PHP-FPM (internal) |
| cron | - | Background tasks (internal) |
| db | 3306 | MariaDB (internal) |

## Common Commands

```bash
# View logs
docker compose logs -f backend

# Shell access
docker compose exec backend bash

# Stop services
docker compose down

# Apply configuration changes
docker compose up -d --force-recreate

# Full reset (delete all data)
docker compose down -v
rm -rf data/
```

## Publishing Images from a Fork

The `Publish Docker image to hub.docker.com` GitHub Actions workflow (`.github/workflows/main.yml`) reads these repository variables (Settings → Secrets and variables → Actions → Variables), so a fork can publish images to its own registries without changing the workflow:

| Variable | Default | Description |
|----------|---------|-------------|
| `DOCKER_ORG` | `aspendiscovery` | Docker Hub namespace |
| `GHCR_ORG` | `aspen-discovery` | GitHub Container Registry namespace (must be lowercase) |
| `QUAY_ORG` | `aspen-discovery` | Quay.io namespace |
| `DOCKER_PUSH` | `true` | Set to `false` to skip Docker Hub |
| `GHCR_PUSH` | `true` | Set to `false` to skip GitHub Container Registry |
| `QUAY_PUSH` | `true` | Set to `false` to skip Quay.io |
| `IMAGE_PREFIX` | _(empty)_ | Prepended to each image name (`aspen`, `solr`, `tunnel`) |
| `PUSH_BRANCH_PATTERN` | _(empty)_ | Bash regex; pushes to matching branches publish images tagged with the branch name |

Images are always published for git tags and manual workflow runs. Registry credentials come from the `DOCKER_USERNAME`/`DOCKER_PASSWORD`, `GHCR_USERNAME`/`GHCR_PASSWORD` and `QUAY_USERNAME`/`QUAY_PASSWORD` secrets (a `QUAY_USER` secret is used if `QUAY_USERNAME` isn't set), and only the registries being pushed to need them.

For example, to publish only to Quay.io for every push to a release branch such as `26.10.00`, set `QUAY_ORG=example-org`, `IMAGE_PREFIX=aspen-discovery-`, `DOCKER_PUSH=false`, `GHCR_PUSH=false` and `PUSH_BRANCH_PATTERN=^[0-9]{2}\.[0-9]{2}\.[0-9]{2}`. That publishes `quay.io/example-org/aspen-discovery-aspen:26.10.00`, `quay.io/example-org/aspen-discovery-solr:26.10.00` and `quay.io/example-org/aspen-discovery-tunnel:26.10.00`.
