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

## Stack Tecnologico

- **Framework**: Laravel
- **AI Orchestration**: NeuronAI
- **MCP Implementation**: php-mcp
- **Chat Widget**: DeepChat (frontend su 4hse-service)
- **Database**: (configurato tramite Laravel standard)

## File di Documentazione

- `project-overview.md` - Questo file, panoramica generale del progetto
- `neuronai-architecture.md` - Dettagli su orchestrazione agenti e RAG
- `api-controllers.md` - Documentazione endpoint e flussi API
- `mcp-server.md` - Configurazione e tool del server MCP
- `environment-config.md` - Variabili d'ambiente e configurazione
