# 4HSE Projects API Integration - MCP Tool

## Overview

Il tool MCP `list_4hse_projects` permette ai client AI (Claude Code, ChatGPT, etc.) di recuperare la lista dei progetti 4HSE attraverso autenticazione OAuth2.

## Architettura

```
Claude Code (MCP Client)
    ↓ OAuth2 Authentication
Keycloak (auth.4hse.local)
    ↓ Access Token
MCP Server (ValidateMcpToken Middleware)
    ↓ Validated Token
ProjectsListTool
    ↓ Token Passthrough
FourHseApiClient
    ↓ Bearer Token
4HSE Service API (service.4hse.local)
```

## Componenti Implementati

### 1. Configurazione (`config/keycloak.php`, `config/fourhse.php`)

**Keycloak OAuth2:**
- Base URL, Realm, Client ID/Secret
- Endpoints: authorization, token, introspection, userinfo
- Scopes: `openid profile email`

**4HSE API:**
- Base URL, timeout, retry configuration
- SSL verification (disabilitabile per sviluppo locale)

### 2. OAuth2 Protected Resource Metadata

**Endpoint:** `GET /.well-known/oauth-protected-resource`

Response:
```json
{
  "resource": "https://ai.4hse.local",
  "authorization_servers": ["https://auth.4hse.local/realms/4hse"],
  "bearer_methods_supported": ["header"]
}
```

Questo endpoint permette ai client MCP di auto-scoprire il server di autorizzazione.

### 3. Token Validation Service (`app/Services/KeycloakTokenValidator.php`)

**Funzionalità:**
- Valida access token tramite Keycloak introspection endpoint
- Verifica che il token sia `active`
- Valida audience (`client_id` match)
- Cache dei risultati di validazione (TTL basato su scadenza token)
- Estrazione informazioni utente da token

**Metodi Principali:**
```php
validate(string $token): array          // Valida e ritorna token data
getUserInfo(array $tokenData): array    // Estrae user info da token
extractBearerToken(string $header): ?string  // Parse Authorization header
```

### 4. Authentication Middleware (`app/Http/Middleware/ValidateMcpToken.php`)

**Applicato a:** Tutte le richieste MCP in modalità HTTP (prod)

**Flusso:**
1. Estrae Bearer token da `Authorization` header
2. Valida con Keycloak via `KeycloakTokenValidator`
3. Store token data e user info in request attributes
4. Ritorna 401 con `WWW-Authenticate` header se invalid

**Request Attributes Iniettati:**
- `token_data`: Dati completi del token introspection
- `user_info`: Info utente estratte (user_id, username, email)
- `bearer_token`: Access token originale

### 5. 4HSE API Client (`app/Services/FourHseApiClient.php`)

**Costruttore:** `new FourHseApiClient(string $bearerToken)`

HTTP client configurato per chiamare le API 4HSE:
- Invia Bearer token in `Authorization` header
- Retry automatico (configurabile)
- Timeout gestito
- SSL verification opzionale

**Metodo Principale:**
```php
getProjects(array $params): array
// Returns: ['projects' => [...], 'pagination' => [...]]
```

**Parametri supportati:**
- `filter`: Oggetto con filtri (name, status, project_type)
- `per-page`: Risultati per pagina (default: 100)
- `page`: Numero pagina (default: 1)
- `sort`: Campo per ordinamento (es: "name", "-created_at")
- `history`: Include elementi storicizzati

**Estrazione Pagination:**
Headers `X-Pagination-*` vengono parsati automaticamente.

### 6. MCP Tool: ProjectsListTool (`app/Ai/Mcp/Tools/ProjectsListTool.php`)

**Tool Name:** `list_4hse_projects`

**Descrizione:** Recupera lista progetti 4HSE con filtri opzionali. Richiede OAuth2 authentication.

#### Parametri

| Nome | Tipo | Required | Descrizione |
|------|------|----------|-------------|
| `filterName` | string | No | Filtra per nome progetto (partial match) |
| `filterStatus` | enum | No | active, suspended, deleted |
| `filterProjectType` | enum | No | safety, template |
| `perPage` | integer | No | Risultati per pagina (1-100, default: 20) |
| `page` | integer | No | Numero pagina (default: 1) |
| `sort` | string | No | Campo sort (es: "name", "-created_at") |

#### Response

**Success:**
```json
{
  "success": true,
  "projects": [
    {
      "project_id": "uuid",
      "name": "Project Name",
      "description": "...",
      "status": "active",
      "project_type": "safety",
      "customer_id": "uuid",
      "partner_id": "uuid",
      "created_at": "2024-01-01",
      "updated_at": "2024-01-02"
    }
  ],
  "pagination": {
    "current_page": 1,
    "page_count": 5,
    "per_page": 20,
    "total_count": 95
  },
  "filters_applied": {
    "name": "filter value",
    "status": null,
    "project_type": null
  }
}
```

**Error:**
```json
{
  "error": "Failed to retrieve projects",
  "message": "Token is not active or has expired",
  "code": 0
}
```

## Flusso OAuth2 Completo

### 1. Client Discovery (Automatico)

Claude Code/MCP client richiede:
```
GET https://ai.4hse.local/.well-known/oauth-protected-resource
```

Riceve authorization server: `https://auth.4hse.local/realms/4hse`

### 2. Authorization Flow (Browser-based)

Client apre browser per autenticazione:
```
https://auth.4hse.local/realms/4hse/protocol/openid-connect/auth
  ?client_id=mcp-server-4hse
  &redirect_uri=http://localhost:PORT/callback
  &response_type=code
  &scope=openid%20profile%20email
  &code_challenge=PKCE_CHALLENGE
  &code_challenge_method=S256
```

Utente effettua login → Keycloak ritorna authorization code.

### 3. Token Exchange

Client scambia code per access token:
```bash
POST https://auth.4hse.local/realms/4hse/protocol/openid-connect/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTH_CODE
&redirect_uri=http://localhost:PORT/callback
&client_id=mcp-server-4hse
&code_verifier=PKCE_VERIFIER
```

Response:
```json
{
  "access_token": "eyJhbG...",
  "token_type": "Bearer",
  "expires_in": 300,
  "refresh_token": "eyJhbG...",
  "scope": "openid profile email"
}
```

### 4. Tool Call con Token

Client MCP chiama tool con Bearer token:
```
Authorization: Bearer eyJhbG...
```

MCP Server valida token → Chiama 4HSE API → Ritorna progetti.

## Testing

### 1. Test OAuth2 Metadata Endpoint

```bash
curl https://ai.4hse.local/.well-known/oauth-protected-resource | jq
```

### 2. Test Token Validation (Manuale)

```bash
# Ottieni token da Keycloak (manual flow)
TOKEN="eyJhbG..."

# Test MCP tool call
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "list_4hse_projects",
      "arguments": {
        "perPage": 10,
        "page": 1,
        "filterStatus": "active"
      }
    }
  }'
```

### 3. Test con Claude Code

1. Avvia MCP server: `docker exec -i 4hse-ai php artisan mcp:serve`
2. Claude Code legge `.mcp.json` e connette al server
3. Al primo tool call, Claude Code richiede autenticazione
4. Si apre browser per login Keycloak
5. Token salvato automaticamente
6. Tool disponibile per uso

## Environment Variables

```bash
# Keycloak OAuth2
KEYCLOAK_BASE_URL=https://auth.4hse.local
KEYCLOAK_REALM=4hse
KEYCLOAK_CLIENT_ID=mcp-server-4hse
KEYCLOAK_CLIENT_SECRET=your-secret-from-keycloak
KEYCLOAK_SCOPES="openid profile email"
KEYCLOAK_VERIFY_SSL=false  # true in produzione

# 4HSE Service API
FOURHSE_API_URL=https://service.4hse.local
FOURHSE_API_TIMEOUT=30
FOURHSE_API_VERIFY_SSL=false  # true in produzione

# MCP Server
MCP_MODE=prod  # use 'dev' for STDIO (no auth)
MCP_HTTP_HOST=0.0.0.0
MCP_HTTP_PORT=8080
```

## Security Notes

### Development (Local)
- `KEYCLOAK_VERIFY_SSL=false`: OK per certificati self-signed
- `FOURHSE_API_VERIFY_SSL=false`: OK per API locali

### Production
- Abilitare sempre SSL verification
- Usare certificati validi (Let's Encrypt)
- Configurare CORS appropriatamente
- Rate limiting su MCP endpoints
- Monitoring su Keycloak token introspection rate

## Troubleshooting

### Token Validation Fails
```bash
# Test token manualmente
curl -X POST https://auth.4hse.local/realms/4hse/protocol/openid-connect/token/introspect \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=$TOKEN" \
  -d "client_id=mcp-server-4hse" \
  -d "client_secret=$CLIENT_SECRET"
```

### 4HSE API Returns 401
- Verificare che il token Keycloak sia valido per `service.4hse.local`
- Controllare configurazione OAuth2 su `service.4hse.local`
- Il token potrebbe richiedere audience specifica

### MCP Server Non Risponde
```bash
# Check logs
docker logs 4hse-ai

# Test endpoint base
curl http://127.0.0.1:8080/health
```

## Future Enhancements

1. **Additional Project Operations:**
   - `create_4hse_project`
   - `update_4hse_project`
   - `delete_4hse_project`
   - `view_4hse_project`

2. **Scope-based Permissions:**
   - `projects:read` scope per list/view
   - `projects:write` scope per create/update
   - `projects:delete` scope per delete

3. **Caching:**
   - Cache risultati progetti (invalidazione su update)
   - Reduce API calls a 4HSE service

4. **Webhook Integration:**
   - Real-time updates quando progetti cambiano
   - Notifiche via MCP events
