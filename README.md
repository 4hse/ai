# 4HSE AI Project - Overview

## Descrizione Generale

Questo progetto Laravel rappresenta il componente **AI** del prodotto SaaS **4HSE**. Si integra con il backend principale (`4hse-service`) per fornire funzionalità di intelligenza artificiale attraverso agenti conversazionali, RAG (Retrieval-Augmented Generation), e tool AI accessibili tramite MCP (Model Context Protocol).

## Relazione con Altri Servizi

- **4hse-service**: Backend principale del SaaS 4HSE che espone REST API e consuma le funzionalità AI di questo progetto
- **auth.4hse.com** (`.local` in sviluppo): Servizio di autenticazione centralizzato (implementazione futura)

## Architettura a 3 Componenti

### 1. NeuronAI - Orchestrazione Agenti
Sistema di orchestrazione e gestione degli agenti AI:
- Creazione e configurazione di agenti personalizzati
- Orchestrazione delle conversazioni multi-agente
- RAG della documentazione tecnica di 4HSE
- RAG del sito commerciale di 4HSE

### 2. API Controllers per Widget DeepChat
Espone endpoint REST consumati da `4hse-service` per il widget chat integrato nell'applicazione web:
- **ChatController**: Gestisce le interazioni con gli agenti tramite il widget DeepChat
- **ChatHistoryController**: Fornisce accesso alla cronologia delle conversazioni
- **Database locale**: Memorizza sessioni di chat (popolate automaticamente dagli agenti NeuronAI)
- **Funzionalità future**: Gestione crediti utente, rate limiting, analytics

### 3. MCP Server (php-mcp)
Server Model Context Protocol che espone tool 4HSE a sistemi AI esterni (Claude, ChatGPT, etc.):
- Tool per RAG documentazione 4HSE
- Tool per RAG sito commerciale 4HSE
- **Condivisione risorse**: I tool MCP utilizzano le stesse implementazioni RAG di NeuronAI
- **Autenticazione**: OAuth2 con Keycloak per accesso sicuro ai tool MCP

## Stack Tecnologico

- **Framework**: Laravel
- **AI Orchestration**: NeuronAI
- **MCP Implementation**: php-mcp
- **Chat Widget**: DeepChat (frontend su 4hse-service)
- **Database**: (configurato tramite Laravel standard)

## Testing MCP Server con Claude Code

### Generazione Token OAuth2

Per testare il server MCP con Claude Code, è necessario ottenere un access token da Keycloak:

```bash
# Ottieni il token (sostituisci USERNAME e PASSWORD con le tue credenziali)
curl -X POST 'https://auth.4hse.local/realms/4hse/protocol/openid-connect/token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'client_id=mcp-server-4hse' \
  -d 'client_secret=R1CrmmBQcGvY52rCxqgGyRcfLBel7DpX' \
  -d 'grant_type=password' \
  -d 'username=YOUR_USERNAME' \
  -d 'password=YOUR_PASSWORD' \
  -d 'scope=openid profile email' \
  --insecure | jq -r '.access_token'
```

### Configurazione Claude Code

1. **Esporta il token** nel terminale dove avvierai Claude Code:
   ```bash
   export MCP_TOKEN="your_access_token_here"
   ```

2. **Avvia Claude Code** nello stesso terminale:
   ```bash
   claude-code
   ```

3. **Verifica la connessione** con il comando:
   ```
   /mcp
   ```

Il server MCP HTTP (`4hse-mcp-local-http`) dovrebbe apparire come connesso.

**Nota:** I token hanno una durata di ~24 ore. Quando scadono, genera un nuovo token con il comando curl sopra.

## File di Documentazione

- `project-overview.md` - Questo file, panoramica generale del progetto
- `neuronai-architecture.md` - Dettagli su orchestrazione agenti e RAG
- `api-controllers.md` - Documentazione endpoint e flussi API
- `mcp-server.md` - Configurazione e tool del server MCP
- `environment-config.md` - Variabili d'ambiente e configurazione
