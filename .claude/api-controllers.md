# API Controllers - Widget DeepChat

## Overview

Questo progetto espone API REST consumate dal backend principale `4hse-service` per integrare il widget **DeepChat** nell'applicazione web 4HSE. Gli utenti finali interagiscono con gli agenti AI attraverso questo widget.

## Architettura Integrazione

```
4hse-service (Frontend)
    ↓ (HTTP Request)
Widget DeepChat
    ↓ (POST)
ChatController → RouterWorkflow → NeuronAI Agents
    ↓ (SSE Stream)
Response → Widget DeepChat
```

## Controllers

### ChatController (`app/Http/Controllers/ChatController.php`)

**Endpoint**: `POST /chat/stream`

Gestisce le interazioni in tempo reale con gli agenti AI tramite Server-Sent Events (SSE).

#### Request Parameters
```php
{
  "thread_id": "unique-thread-identifier",  // ID sessione conversazione
  "messages": [],                            // Array messaggi (attualmente non utilizzato)
  // Altri parametri vengono estratti dal request
}
```

#### Response Format (SSE)
```javascript
data: {
  "text": "contenuto della risposta",
  "time": "14:23:45",
  "thread_id": "unique-thread-identifier"
}
```

#### Flusso di Esecuzione

1. **Validazione**: Verifica presenza `thread_id` e messaggio
2. **Workflow Init**: Crea `RouterWorkflow` con:
   - `message`: Query utente
   - `thread_id`: ID sessione
   - `user_id`: Identificativo utente (email)
3. **Streaming**:
   - Avvia workflow agenti NeuronAI
   - Stream eventi `GenerationProgressEvent`
   - Invia chunks SSE al client
4. **Auto-save**: La chat history viene salvata automaticamente da NeuronAI

#### Headers SSE
```
Content-Type: text/event-stream
Cache-Control: no-cache
Connection: keep-alive
```

#### Note Implementative
- Attualmente usa valori hardcoded per testing:
  - `message = "cosa è 4hse?"`
  - `user_id = "adriano.foschi@4hse.com"`
- Gestione errori commentata (da implementare)
- La persistenza è gestita automaticamente da `LaravelChatHistory`

---

### ChatHistoryController (`app/Http/Controllers/ChatHistoryController.php`)

**Endpoint**: `GET /chat-history/{thread_id}`

Fornisce accesso alla cronologia delle conversazioni per un thread specifico.

#### Request
```
GET /chat-history/{thread_id}
```

#### Response Success (200)
```json
{
  "id": 1,
  "thread_id": "unique-thread-identifier",
  "user_id": 123,
  "messages": [
    {
      "role": "user",
      "content": "Come funziona 4HSE?"
    },
    {
      "role": "assistant",
      "content": "4HSE è una piattaforma..."
    }
  ],
  "created_at": "2025-10-09T10:30:00.000000Z",
  "updated_at": "2025-10-09T10:31:45.000000Z"
}
```

#### Response Error (404)
```json
{
  "error": "Thread not found"
}
```

#### Use Case
- Widget DeepChat carica cronologia all'apertura
- Ripristina contesto conversazione precedente
- Mostra storico interazioni utente

---

## Database - Chat History

### Model: ChatHistory (`app/Models/ChatHistory.php`)

**Table**: `chat_history`

#### Schema
```php
[
  'thread_id' => string,      // Unique identifier per sessione
  'user_id' => integer,        // FK a tabella users
  'messages' => array,         // JSON array con cronologia completa
  'created_at' => timestamp,
  'updated_at' => timestamp
]
```

#### Relationships
- `belongsTo(User::class)`: Relazione con utente proprietario

#### Cast Automatici
- `messages`: Automaticamente serializzato/deserializzato da JSON

---

## Routes (`routes/web.php`)

```php
// Homepage (placeholder)
GET  /

// Recupera cronologia chat
GET  /chat-history/{thread_id}

// Stream conversazione con agenti
POST /chat/stream
```

---

## Integrazione con NeuronAI

### Workflow Execution
Il `ChatController` non gestisce direttamente la logica conversazionale, ma delega tutto a **RouterWorkflow**:

1. User message → `ChatController`
2. `ChatController` → `RouterWorkflow::start()`
3. `RouterWorkflow` → `RouterNode` (seleziona agente)
4. `RouterNode` → `CallNode` (esegue agente)
5. Agente genera risposta + popola `chat_history`
6. Stream eventi → `ChatController` → SSE → Client

### Auto-Population Chat History

La tabella `chat_history` viene popolata **automaticamente** da:
- **LaravelChatHistory** (`app/Ai/History/LaravelChatHistory.php`)
- Integrato nel workflow NeuronAI
- Salva messaggi user e assistant in tempo reale
- Context window: 50,000 token

---

## Funzionalità Future

### Gestione Crediti Utente
Implementazione prevista:
- Tracking token/richieste per utente
- Rate limiting basato su piano subscription
- Billing e reporting usage

### Autenticazione
Integrazione con `auth.4hse.com`:
- Validazione JWT token
- Middleware di autenticazione
- Authorization per accesso thread

### Error Handling Avanzato
- Gestione timeout workflow
- Retry logic per failure transitori
- Error reporting strutturato

### Metrics & Monitoring
- Latency tracking
- Agent performance metrics
- User satisfaction scoring
