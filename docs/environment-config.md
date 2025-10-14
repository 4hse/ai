# Environment Configuration

## Overview

Questo documento descrive le variabili d'ambiente specifiche per il progetto AI di 4HSE, in aggiunta alle variabili standard di Laravel.

## Variabili Standard Laravel

Il progetto utilizza tutte le variabili d'ambiente standard di Laravel. Fare riferimento a `.env.example` per la configurazione base:

- **APP_**: Configurazione applicazione Laravel
- **DB_**: Database connection (default: SQLite)
- **AWS_**: Credenziali AWS per Bedrock (Claude 3 Sonnet)
- **MAIL_**, **CACHE_**, **QUEUE_**, etc.: Configurazioni Laravel standard

## Variabili MCP Server

Configurazione specifica per il server Model Context Protocol.

### MCP_SERVER_NAME
- **Descrizione**: Nome identificativo del server MCP
- **Default**: `4hse-mcp-server`
- **Esempio**: `MCP_SERVER_NAME=4hse-mcp-server`
- **Utilizzo**: Identificazione del server nei log e nelle connessioni client

### MCP_MODE
- **Descrizione**: Modalità operativa del server MCP
- **Valori**:
  - `dev`: Modalità development (STDIO transport)
  - `prod`: Modalità production (HTTP transport)
- **Default**: `prod`
- **Esempio**: `MCP_MODE=dev`
- **Utilizzo**: Determina il transport layer utilizzato dal server

### MCP_HTTP_HOST
- **Descrizione**: Host su cui il server HTTP MCP ascolta le connessioni
- **Default**: `0.0.0.0` (tutte le interfacce di rete)
- **Esempio**: `MCP_HTTP_HOST=0.0.0.0`
- **Utilizzo**: Solo in modalità `MCP_MODE=prod`
- **Note**:
  - `0.0.0.0` = ascolta su tutte le interfacce
  - `127.0.0.1` = solo connessioni locali

### MCP_HTTP_PORT
- **Descrizione**: Porta su cui il server HTTP MCP ascolta
- **Default**: `8080`
- **Esempio**: `MCP_HTTP_PORT=8080`
- **Utilizzo**: Solo in modalità `MCP_MODE=prod`

### MCP_SERVER_URL
- **Descrizione**: URL completo del server MCP per connessioni client
- **Default**: `http://127.0.0.1:8080/mcp`
- **Esempio**: `MCP_SERVER_URL=http://127.0.0.1:8080/mcp`
- **Utilizzo**: Client MCP utilizzano questo URL per connettersi
- **Note**: Deve corrispondere a `MCP_HTTP_HOST` e `MCP_HTTP_PORT`

## Configurazione Completa Esempio

### Development (Local con STDIO)
```bash
# Laravel Base
APP_NAME="4HSE AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite

# AWS Bedrock (per AdvisorAgent)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1

# MCP Server - Development Mode
MCP_SERVER_NAME=4hse-mcp-server
MCP_MODE=dev
# HTTP settings ignored in dev mode
```

### Production (Docker/HTTP)
```bash
# Laravel Base
APP_NAME="4HSE AI Production"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ai.4hse.com

# Database (PostgreSQL example)
DB_CONNECTION=pgsql
DB_HOST=db-server
DB_PORT=5432
DB_DATABASE=4hse_ai_prod
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# AWS Bedrock
AWS_ACCESS_KEY_ID=prod-access-key
AWS_SECRET_ACCESS_KEY=prod-secret-key
AWS_DEFAULT_REGION=us-east-1

# MCP Server - Production Mode
MCP_SERVER_NAME=4hse-mcp-server
MCP_MODE=prod
MCP_HTTP_HOST=0.0.0.0
MCP_HTTP_PORT=8080
MCP_SERVER_URL=http://127.0.0.1:8080/mcp
```

## AI Provider Credentials

### AWS Bedrock (Claude 3 Sonnet)
Utilizzato da **AdvisorAgent** per RAG commerciale.

```bash
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1  # Region con Bedrock enabled
```

**Setup**:
1. Creare IAM user con policy `AmazonBedrockFullAccess`
2. Abilitare modello Claude 3 Sonnet in AWS Bedrock console
3. Configurare credenziali in `.env`

### Google Gemini (Flash 2.5 & Embeddings)
Utilizzato da **RouterAgent**, **ConsultantAgent**, e per embeddings.

```bash
GOOGLE_API_KEY=your-gemini-api-key
```

**Setup**:
1. Ottenere API key da [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Abilitare Gemini API nel progetto Google Cloud
3. Configurare API key in `.env`

**Note**: La configurazione dei provider AI avviene nel codice:
- `app/Ai/Providers.php` - LLM providers
- `app/Ai/EmbeddingsProviders.php` - Embeddings providers

## Vector Store Paths

I vector stores per RAG sono salvati in:

```bash
storage/ai/www/           # Website RAG (AdvisorAgent)
storage/ai/documentation/ # Docs RAG (GuideAgent)
```

**Importante**:
- Assicurarsi che `storage/ai` sia writable
- In produzione, considerare backup periodici dei vector stores
- I vector stores contengono gli embeddings pre-calcolati

## Docker Environment

Esempio di configurazione per Docker Compose:

```yaml
services:
  ai-app:
    environment:
      # Laravel
      - APP_NAME=4HSE AI
      - APP_ENV=production
      - APP_DEBUG=false

      # Database
      - DB_CONNECTION=pgsql
      - DB_HOST=postgres
      - DB_DATABASE=4hse_ai

      # AWS
      - AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
      - AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}

      # Google
      - GOOGLE_API_KEY=${GOOGLE_API_KEY}

      # MCP Server
      - MCP_SERVER_NAME=4hse-mcp-server
      - MCP_MODE=prod
      - MCP_HTTP_HOST=0.0.0.0
      - MCP_HTTP_PORT=8080
      - MCP_SERVER_URL=http://127.0.0.1:8080/mcp

    volumes:
      - ./storage/ai:/var/www/html/storage/ai
```

## Security Notes

### Secrets Management
- **MAI** committare `.env` in Git
- Utilizzare `.env.example` come template
- In produzione, usare secret management (AWS Secrets Manager, Vault, etc.)

### API Keys
Proteggere le API keys di:
- AWS Bedrock (costose, accesso a modelli potenti)
- Google Gemini (rate limiting, costi)

### MCP Server Access
In produzione:
- Considerare autenticazione per endpoint HTTP
- Limitare accesso a IP whitelist
- Monitorare usage per prevenire abusi

## Troubleshooting

### MCP Server non si avvia
```bash
# Verificare variabili MCP
php artisan mcp:serve

# Check logs
tail -f storage/logs/laravel.log
```

### RAG non funziona
```bash
# Verificare vector stores
ls -la storage/ai/www/
ls -la storage/ai/documentation/

# Verificare permessi
chmod -R 755 storage/ai/
```

### AWS Bedrock errors
```bash
# Test credenziali AWS
aws bedrock list-foundation-models --region us-east-1

# Verificare IAM permissions
```

### Google Gemini errors
```bash
# Test API key
curl "https://generativelanguage.googleapis.com/v1/models?key=$GOOGLE_API_KEY"
```

## Future Configuration

### Auth Integration
```bash
AUTH_SERVICE_URL=https://auth.4hse.com
AUTH_CLIENT_ID=ai-service-client
AUTH_CLIENT_SECRET=secret
```

### Rate Limiting
```bash
RATE_LIMIT_PER_USER=100  # Requests per day
RATE_LIMIT_PER_MINUTE=10
```

### Monitoring
```bash
SENTRY_DSN=https://...
DATADOG_API_KEY=...
```
