# Work Group Type Mapping Fix

## Problema Identificato

L'agente AI stava inviando valori incorretti per il campo `work_group_type` nell'API 4HSE. Nel log dell'errore:

```json
{
  "name": "Manipolazione sostanze pericolose",
  "office_id": "d4eb65d8-198c-4697-adcc-fd59f5c0e2b5",
  "work_group_type": "Work Phase",
  "description": "Gestione e manipolazione di sostanze che presentano rischi per la salute o la sicurezza, richiedendo precauzioni specifiche."
}
```

Il problema era che `"work_group_type": "Work Phase"` non è un valore enum valido per l'API 4HSE.

## Valori Enum Corretti

L'API 4HSE accetta solo questi tre valori enum per `work_group_type`:

| Enum Value | Significato Italiano | Descrizione |
|------------|---------------------|-------------|
| `JOB` | Mansione | Mansioni/ruoli lavorativi specifici |
| `WORK_PLACE` | Fase di lavoro | Fasi di lavoro o luoghi di lavoro |
| `HGROUP` | Gruppo omogeneo | Gruppo omogeneo (quando non è nessuno degli altri due) |

## Soluzione Implementata

### 1. Creazione della Classe Utility

Creata `WorkGroupTypeMapper` in `app/Ai/Mcp/Tools/Utils/WorkGroupTypeMapper.php` che:

- Mappa termini user-friendly (italiano/inglese) ai valori enum corretti
- Supporta case-insensitive matching
- Fornisce validazione e messaggi di errore

### 2. Mapping Completo

La classe supporta questi termini:

**Italiano:**
- "fase di lavoro", "fasi di lavoro" → `WORK_PLACE`
- "posto di lavoro", "ambiente di lavoro" → `WORK_PLACE` 
- "mansione", "mansioni", "ruolo lavorativo" → `JOB`
- "gruppo omogeneo", "gruppi omogenei" → `HGROUP`

**Inglese:**
- "work phase", "work place", "workplace" → `WORK_PLACE`
- "job", "job role", "position" → `JOB`
- "homogeneous group" → `HGROUP`

**Valori enum diretti:**
- "WORK_PLACE", "JOB", "HGROUP" → se stessi

### 3. Tool Aggiornati

Tutti i tool che gestiscono work group types sono stati aggiornati:

- `WorkGroupCreateTool`
- `WorkGroupUpdateTool`
- `WorkGroupListTool`
- `WorkGroupEntityListTool`
- `WorkGroupPersonListTool`

### 4. Gestione Errori

Se viene fornito un tipo non valido, i tool ora ritornano:

```json
{
  "error": "Invalid work group type",
  "message": "Work group type 'Work Phase' is not valid. Examples of accepted values: gruppo omogeneo, gruppi omogenei, fase di lavoro, fasi di lavoro, posto di lavoro, posti di lavoro (and 28 more variations)"
}
```

## Esempio di Utilizzo

### Prima (Errato)
```json
{
  "work_group_type": "Work Phase"
}
```

### Dopo (Corretto)
L'utente può ora dire:
- "fase di lavoro" → mappato automaticamente a `WORK_PLACE`
- "work phase" → mappato automaticamente a `WORK_PLACE`
- "WORK_PLACE" → usato direttamente

## Test di Verifica

Il mapping è stato testato con 34 variazioni diverse e tutti i test passano:

- ✅ 9 termini italiani
- ✅ 10 termini inglesi  
- ✅ 6 valori enum diretti
- ✅ 4 valori invalidi (correttamente rifiutati)
- ✅ Validazione e messaggi di errore

## Benefici

1. **Robustezza**: Gli agenti AI possono usare terminologia naturale
2. **Localizzazione**: Supporto completo italiano/inglese
3. **Compatibilità**: Mantiene compatibilità con valori enum diretti
4. **Debugging**: Messaggi di errore chiari e informativi
5. **Manutenibilità**: Mapping centralizzato in una classe utility

## File Modificati

```
ai/app/Ai/Mcp/Tools/Utils/WorkGroupTypeMapper.php        (nuovo)
ai/app/Ai/Mcp/Tools/WorkGroupCreateTool.php              (aggiornato)
ai/app/Ai/Mcp/Tools/WorkGroupUpdateTool.php              (aggiornato)
ai/app/Ai/Mcp/Tools/WorkGroupListTool.php                (aggiornato)
ai/app/Ai/Mcp/Tools/WorkGroupEntityListTool.php          (aggiornato)
ai/app/Ai/Mcp/Tools/WorkGroupPersonListTool.php          (aggiornato)
```

Questo fix risolve il problema originale dove l'agente inviava `"Work Phase"` invece del valore enum corretto `"WORK_PLACE"`.