# NeuronAI - Orchestrazione Agenti e RAG

## Overview

NeuronAI è il cuore del sistema AI di 4HSE. Gestisce l'orchestrazione degli agenti conversazionali, il routing delle richieste utente, e fornisce funzionalità RAG per documentazione e contenuti commerciali.

## Architettura Agenti

### Struttura Base

Tutti gli agenti si trovano in `app/Ai/Agents/` e estendono classi base di NeuronAI:
- **Agent**: Base class per agenti standard
- **RAG**: Base class per agenti con Retrieval-Augmented Generation

### Agenti Implementati

#### 1. RouterAgent (`app/Ai/Agents/RouterAgent.php`)
- **Ruolo**: Routing intelligente delle query utente agli agenti specializzati
- **Provider**: Gemini 2.5 Flash
- **Funzione**: Analizza la richiesta utente e determina quale agente specializzato può gestirla meglio

#### 2. ConsultantAgent (`app/Ai/Agents/ConsultantAgent.php`)
- **Ruolo**: Consulente esperto sulla sicurezza sul lavoro
- **Provider**: Gemini 2.5 Flash
- **Funzione**: Fornisce consigli e risposte su tematiche di sicurezza lavorativa

#### 3. AdvisorAgent (`app/Ai/Agents/AdvisorAgent.php`)
- **Ruolo**: Supporto commerciale per 4HSE
- **Provider**: AWS Bedrock Claude 3 Sonnet
- **Embeddings**: Google Gemini Embedding 001
- **Vector Store**: File-based storage in `storage/ai/www`
- **Funzione**: RAG sui contenuti del sito commerciale 4HSE per rispondere a domande su prodotti, pricing, funzionalità

#### 4. GuideAgent
- **Ruolo**: Guida tecnica per l'uso del prodotto 4HSE
- **Funzione**: Assistenza tecnica e risposta a domande sulla documentazione

#### 5. AssistantAgent
- **Ruolo**: Assistente generale per richieste varie

## Workflow di Orchestrazione

### RouterWorkflow (`app/Ai/Workflows/RouterWorkflow.php`)

Il workflow principale che gestisce il flusso delle conversazioni:

```
Utente → RouterNode → CallNode → Agente Specializzato
```

**Componenti chiave:**
- **RouterNode** (`app/Ai/Nodes/RouterNode.php`): Determina quale agente invocare
- **CallNode** (`app/Ai/Nodes/CallNode.php`): Esegue l'agente selezionato
- **LaravelChatHistory** (`app/Ai/History/LaravelChatHistory.php`): Gestisce la persistenza della cronologia chat

**Parametri di inizializzazione:**
- `query`: La richiesta dell'utente
- `thread_id`: ID della sessione di conversazione
- `user_id`: ID dell'utente
- `bearer`: Token di autenticazione (default: 'fake' per sviluppo)
- `contextWindow`: 50000 token per la cronologia

## Sistema RAG

### Struttura RAG

Gli agenti RAG (come AdvisorAgent) utilizzano:

1. **AI Provider**: Modello di linguaggio per generare risposte
2. **Embeddings Provider**: Modello per creare vettori di embedding dei documenti
3. **Vector Store**: Database vettoriale per similarity search

### Vector Stores Configurati

- **Website RAG** (`storage/ai/www`): Contenuti del sito commerciale 4HSE
- **Documentation RAG**: Documentazione tecnica del prodotto 4HSE

### Provider AI

Configurati in `app/Ai/Providers.php`:
- AWS Bedrock (Claude 3 Sonnet)
- Google Gemini (2.5 Flash)

### Embeddings Providers

Configurati in `app/Ai/EmbeddingsProviders.php`:
- Google Gemini Embedding 001

## Gestione Prompts

I system prompt degli agenti sono definiti in `app/Ai/Prompts.php`:
- `ROUTER_AGENT_INSTRUCTIONS`: Istruzioni per il routing
- `CONSULTANT_AGENT_INSTRUCTIONS`: Istruzioni per consulenza sicurezza
- Altri prompt specifici per ogni agente

## Eventi e Monitoraggio

Eventi custom per tracking:
- **SelectedAgentEvent** (`app/Ai/Events/SelectedAgentEvent.php`): Traccia quale agente è stato selezionato
- **GenerationProgressEvent** (`app/Ai/Events/GenerationProgressEvent.php`): Progress streaming delle risposte
- **ProgressEvent** (`app/Ai/Events/ProgressEvent.php`): Eventi generici di progresso

## Chat History

La cronologia delle conversazioni è gestita da **LaravelChatHistory** che:
- Salva automaticamente messaggi utente e risposte agenti
- Mantiene il contesto conversazionale (50k token)
- Persiste nel database Laravel
- Viene popolata automaticamente dagli agenti durante l'esecuzione

## Condivisione con MCP

I RAG definiti per gli agenti (AdvisorAgent, GuideAgent) vengono **riutilizzati** dai tool MCP:
- Stessa implementazione vector store
- Stessi embeddings provider
- Stessa logica di retrieval

Questo garantisce coerenza tra chat widget interno e tool AI esterni.
