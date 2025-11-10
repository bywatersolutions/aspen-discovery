# Solr Basic Authentication Setup for Aspen Discovery

This document describes how to configure Aspen Discovery to authenticate with a SolrCloud cluster using HTTP Basic Authentication.

## Overview

Aspen Discovery now supports HTTP Basic Authentication for all Solr connections in both Java indexers and PHP web application. This implementation follows the [Apache Solr Basic Authentication Plugin](https://solr.apache.org/guide/solr/latest/deployment-guide/basic-authentication-plugin.html) specification.

## Prerequisites

1. SolrCloud cluster configured with Basic Authentication
2. Valid Solr user credentials with appropriate permissions
3. Aspen Discovery installation (both Java indexers and PHP web application)

## SolrCloud Configuration

### 1. Configure Basic Authentication in SolrCloud

Create a `security.json` file with your authentication configuration:

```json
{
  "authentication": {
    "blockUnknown": true,
    "class": "solr.BasicAuthPlugin",
    "credentials": {
      "solr": "IV0EHq1OnNrj6gvRCwvFwTrZ1+z1oBbnQdiVC3otuq0= Ndd7LKvVBAaZIF0QAVi1ekCfAJXr1GGfLtRUXhgrF8c="
    },
    "realm": "Aspen Discovery Solr",
    "forwardCredentials": false
  }
}
```

### 2. Upload security.json to ZooKeeper

```bash
bin/solr zk cp ./security.json zk:security.json -z localhost:2181
```

**Note:** The security.json file must be at the chroot of the ZooKeeper structure.

### 3. Create Solr Users

Use the Authentication API to create users with appropriate permissions:

```bash
curl --user admin:adminpassword http://localhost:8080/api/cluster/security/authentication \
  -H 'Content-type:application/json' \
  -d '{"set-user": {"aspen":"AspenPassword123"}}'
```

## Aspen Discovery Configuration

### Configuration File Setup

Add the following parameters to your site's configuration file at `sites/[sitename]/conf/config.ini`:

```ini
[Index]
solrHost        = solr-server.example.com
solrPort        = 8080
url             = http://solr-server.example.com:8983/solr
; Basic Authentication credentials for SolrCloud (optional)
; Leave empty if Solr is not using authentication
solrUsername    = aspen
solrPassword    = AspenPassword123
```

**Security Note:** The `config.ini` file should have restricted permissions (e.g., `chmod 600`) since it contains sensitive credentials.

### Configuration Parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `solrHost` | Yes | Hostname or IP address of the Solr server |
| `solrPort` | Yes | Port number for Solr (typically 8983) |
| `url` | Yes | Full base URL to Solr |
| `solrUsername` | No | Username for Solr authentication (leave empty if no auth) |
| `solrPassword` | No | Password for Solr authentication (leave empty if no auth) |

## Implementation Details

#### Java Authentication Implementation

Each indexer:
1. Loads credentials from the `[Index]` section of `config.ini`
2. Creates an `Http2SolrClient.Builder` with credentials using `.withBasicAuthCredentials(username, password)`
3. Applies credentials to all Solr client instances (update servers and query servers)
4. Logs authentication status for troubleshooting

### PHP Authentication Implementation

The PHP implementation:
1. Reads credentials from the global `$configArray['Index']` configuration
2. Calls `setBasicAuth()` on the CurlWrapper instance if credentials are provided
3. Uses standard HTTP Basic Authentication headers in all Solr requests
