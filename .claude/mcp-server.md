# MCP Server - Model Context Protocol

## Overview

Il server MCP (Model Context Protocol) espone tool di 4HSE a sistemi AI esterni come **Claude**, **ChatGPT**, e altri client compatibili con MCP. Permette a questi sistemi di accedere alle funzionalità RAG di 4HSE per arricchire le loro risposte con informazioni specifiche del prodotto.

## Architettura

```
Claude/ChatGPT/AI Client
    ↓ (MCP Protocol)
MCP Server (php-mcp)
    ↓
Tool Classes (app/Ai/Mcp/Tools)
    ↓ (Riusa implementazione)
NeuronAI RAG (GuideAgent, AdvisorAgent)
    ↓
Vector Stores (storage/ai)
```

## Comando Server

### McpServeCommand (`app/Console/Commands/McpServeCommand.php`)

**Artisan Command**: `php artisan mcp:serve`

Avvia il server MCP con configurazione dinamica basata su environment variables.

#### Modalità Operative

**1. Development Mode (STDIO)**
```bash
MCP_MODE=dev php artisan mcp:serve
```
- Transport: STDIO (Standard Input/Output)
- Use case: Integrazione locale con Claude Desktop o altre app MCP
- Output: `[INFO] Starting in DEV mode (STDIO transport)`

**2. Production Mode (HTTP)**
```bash
MCP_MODE=prod php artisan mcp:serve
```
- Transport: HTTP Streamable
- Host: Configurabile via `MCP_HTTP_HOST` (default: 127.0.0.1)
- Port: Configurabile via `MCP_HTTP_PORT` (default: 8080)
- Output: `[INFO] Starting in PROD mode (HTTP transport on 127.0.0.1:8080)`

#### Server Configuration
```php
Server::make()
    ->withServerInfo('4HSE MCP Server', '1.0.0')
    ->build()
```

#### Auto-Discovery
Il server effettua **auto-discovery** di tutti i tool nella cartella:
```
app/Ai/Mcp/Tools/
```
Tutti i file PHP con attributo `#[McpTool]` vengono registrati automaticamente.

---

## Tool Disponibili

### 1. DocumentationSearchTool (`app/Ai/Mcp/Tools/DocumentationSearchTool.php`)

**Tool Name**: `search_4hse_documentation`

Ricerca semantica nella documentazione tecnica di 4HSE.

#### Implementazione
```php
#[McpTool(
    name: 'search_4hse_documentation',
    description: 'Searches the 4HSE documentation using natural language queries'
)]
public function searchDocumentation(string $query, int $limit = 5): array
```

#### Parametri
- **query** (string, required): Query in linguaggio naturale
  - Example: "Come configurare le notifiche email?"
- **limit** (integer, optional): Numero massimo risultati (default: 5, max: 20)

#### Risposta
```json
{
  "query": "Come configurare le notifiche email?",
  "results_count": 3,
  "results": [
    {
      "id": "doc_123",
      "source_type": "documentation",
      "source_name": "email-configuration.md",
      "content": "Per configurare le notifiche email...",
      "similarity_score": 0.92
    }
  ]
}
```

#### RAG Source
Utilizza **GuideAgent** → Vector Store della documentazione tecnica

---

### 2. WebsiteSearchTool (`app/Ai/Mcp/Tools/WebsiteSearchTool.php`)

**Tool Name**: `search_4hse_website`

Ricerca semantica nel sito commerciale di 4HSE (pricing, features, marketing content).

#### Implementazione
```php
#[McpTool(
    name: 'search_4hse_website',
    description: 'Searches the 4HSE website using natural language queries'
)]
public function searchWebsite(string $query, int $limit = 5): array
```

#### Parametri
- **query** (string, required): Query in linguaggio naturale
  - Example: "Quali sono i piani di pricing disponibili?"
- **limit** (integer, optional): Numero massimo risultati (default: 5, max: 20)

#### Risposta
```json
{
  "query": "Quali sono i piani di pricing disponibili?",
  "results_count": 2,
  "results": [
    {
      "id": "www_456",
      "source_type": "website",
      "source_name": "pricing-page",
      "content": "I piani disponibili sono: Basic, Professional, Enterprise...",
      "similarity_score": 0.88
    }
  ]
}
```

#### RAG Source
Utilizza **AdvisorAgent** → Vector Store del sito commerciale (`storage/ai/www`)

---

## Condivisione Risorse con NeuronAI

### Architettura Condivisa

I tool MCP **non duplicano** la logica RAG, ma **riutilizzano** le implementazioni esistenti:

```php
// WebsiteSearchTool
$this->rag = AdvisorAgent::make();

// DocumentationSearchTool
$this->rag = GuideAgent::make();
```

### Vantaggi
1. **Single Source of Truth**: Una sola implementazione RAG
2. **Consistency**: Stessi risultati tra widget chat e tool MCP
3. **Maintainability**: Modifiche al RAG si propagano automaticamente
4. **Resource Efficiency**: Condivisione vector stores e embeddings

### Retrieval-Only Mode
I tool MCP usano **solo la fase di retrieval** senza generazione LLM:
```php
$documents = $this->rag->retrieveDocuments($message);
```
Questo permette:
- Performance superiori (no LLM call)
- Controllo completo sul formato output
- Costi ridotti

---

## Integrazione Client

### Claude Code (Local Testing)

Il progetto include un file **`.mcp.json`** nella root per testare il server MCP localmente con Claude Code:

```json
{
  "mcpServers": {
    "4hse-mcp-local": {
      "command": "docker",
      "args": [
        "exec",
        "-i",
        "4hse-ai",
        "php",
        "artisan",
        "mcp:serve"
      ]
    }
  }
}
```

**Utilizzo**:
1. Avviare il container Docker: `docker-compose up -d`
2. Claude Code legge automaticamente `.mcp.json`
3. I tool MCP diventano disponibili nella sessione corrente
4. Il server viene eseguito in modalità STDIO tramite `docker exec`

**Vantaggi**:
- Testing locale senza configurare Claude Desktop
- Esecuzione nel container Docker (environment isolato)
- Configurazione committabile nel repository

### Claude Desktop (Development)

Configurazione `claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "4hse": {
      "command": "php",
      "args": ["artisan", "mcp:serve"],
      "cwd": "/path/to/project",
      "env": {
        "MCP_MODE": "dev"
      }
    }
  }
}
```

### HTTP Client (Production)

Endpoint: `http://MCP_SERVER_URL/mcp`

Headers:
```
Content-Type: application/json
Accept: text/event-stream
```

Example request body (MCP protocol):
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "search_4hse_documentation",
    "arguments": {
      "query": "How to configure notifications?",
      "limit": 5
    }
  }
}
```

---

## Error Handling

### Tool-Level Errors
```json
{
  "error": "Failed to retrieve documents",
  "message": "Vector store connection timeout"
}
```

### MCP Protocol Errors
Gestiti automaticamente da `php-mcp/server`:
- Invalid method
- Tool not found
- Schema validation errors

---

## Dependencies

### PHP Packages
- **php-mcp/server**: Implementazione server MCP
- **neuron-core/neuron-ai**: RAG e agent orchestration

### Laravel Integration
- Auto-discovery via Artisan command
- Laravel service container per dependency injection
- Environment-based configuration

---

## Monitoring & Debugging

### Logging
Il server scrive log su STDERR:
```
[INFO] Starting in DEV mode (STDIO transport)
[INFO] Discovering tools in: /app/Ai/Mcp/Tools
[INFO] Registered tool: search_4hse_documentation
[INFO] Registered tool: search_4hse_website
```

### Testing Tools

Via HTTP (in production mode):
```bash
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list"
  }'
```

Via Claude Desktop: I tool appariranno automaticamente nella UI

---

## Future Enhancements

### Tool Aggiuntivi
- **search_4hse_changelog**: Ricerca nelle release notes
- **get_4hse_api_docs**: Accesso API documentation
- **calculate_pricing**: Calcolo pricing personalizzato

### Authentication
- Token-based authentication per tool call
- Rate limiting per client
- Usage tracking e billing

### Advanced RAG
- Hybrid search (semantic + keyword)
- Re-ranking dei risultati
- Citation tracking (source attribution)
