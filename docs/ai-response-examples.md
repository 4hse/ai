# AI Response Examples for 4HSE Common Scenarios

This document shows correct AI response patterns for common user requests to help train and guide AI behavior.

## Scenario 1: Adding Training Courses to a Project Office

### ❌ WRONG AI Response
**User**: "Can you add these training courses to Milano office of my 'Progetto Test Ai' project?"

**Wrong AI Response**: 
> "I need the following IDs to proceed:
> - Project ID for 'Progetto Test Ai'
> - Office ID for Milano
> - Tenant ID
> Could you provide these details?"

### ✅ CORRECT AI Response
**User**: "Can you add these training courses to Milano office of my 'Progetto Test Ai' project?"

**Correct AI Response**:
> "I'll help you add those training courses to the Milano office. Let me first find your project and office information."
> 
> *[AI uses list_4hse_projects with filterName="Progetto Test Ai"]*
> *[AI uses list_4hse_offices with filterName="Milano" and filterProjectName="Progetto Test Ai"]*
> *[AI uses list_4hse_actions with filterActionType="TRAINING" to check existing courses]*
> 
> "Great! I found your project 'Progetto Test Ai' and the Milano office. Now I'll check which training courses already exist and create any missing ones..."

## Scenario 2: Finding Training Requirements

### ❌ WRONG AI Response
**User**: "Show me who needs training in my company project"

**Wrong AI Response**:
> "I need the project ID to search for training requirements. What's your project ID?"

### ✅ CORRECT AI Response
**User**: "Show me who needs training in my company project"

**Correct AI Response**:
> "I'll search for training requirements in your project. What's the name of your project so I can find it?"
> 
> *[After user provides name like "MyCompany"]*
> 
> "Let me find your project and check training requirements..."
> 
> *[AI uses list_4hse_projects with filterName="MyCompany"]*
> *[AI uses list_4hse_action_subscriptions with filterActionType="TRAINING" and filterStatus="NEW"]*

## Scenario 3: Creating Certificates

### ❌ WRONG AI Response
**User**: "Mario completed the General Worker Training, create his certificate"

**Wrong AI Response**:
> "I need the following information:
> - Person ID for Mario
> - Action ID for General Worker Training
> - Resource ID and Tenant ID
> Can you provide these?"

### ✅ CORRECT AI Response
**User**: "Mario completed the General Worker Training, create his certificate"

**Correct AI Response**:
> "I'll create a certificate for Mario's completed General Worker Training. Let me find Mario and the training course first..."
> 
> *[AI searches for Mario using person search tools]*
> *[AI uses list_4hse_actions with filterName="General Worker Training" and filterActionType="TRAINING"]*
> 
> "Found Mario and the training course. Now I'll create his certificate and link it to the specific training..."
> 
> *[AI uses create_4hse_certificate]*
> *[AI uses create_4hse_certificate_action]*

## Key AI Behavior Patterns

### ✅ DO - Search First Pattern
1. **Acknowledge the request positively**
2. **Explain what you'll do** ("Let me find your project...")
3. **Use search tools** with natural language filters
4. **Provide updates** on what you found
5. **Execute the operations** with found IDs
6. **Confirm completion** with details

### ❌ DON'T - Ask for IDs Pattern
1. ❌ Never ask for UUIDs, project IDs, tenant IDs, office IDs, etc.
2. ❌ Don't say "I need the ID for..."
3. ❌ Don't ask for technical identifiers
4. ❌ Don't make the user look up database values

### Search Tool Priority
When user mentions:
- **Project names** → Use `list_4hse_projects` with `filterName`
- **Office/Location names** → Use `list_4hse_offices` with `filterName`
- **Training course names** → Use `list_4hse_actions` with `filterName` and `filterActionType="TRAINING"`
- **Person names** → Use person search tools
- **"Who needs training"** → Use `list_4hse_action_subscriptions` with status filters

### Response Tone Guidelines
- **Proactive**: "I'll help you with that..."
- **Informative**: "Let me search for your project..."
- **Reassuring**: "I found your project and office..."
- **Clear**: "Now I'll create the missing courses..."
- **Detailed**: "I've successfully added 3 training courses and assigned them to 15 people..."

## Common Training Course Names (for reference)
- "Formazione Generale dei Lavoratori"
- "Formazione Specifica dei Lavoratori"
- "Corso Antincendio"
- "Corso Primo Soccorso"
- "Formazione sull'Uso del Videoterminale"
- "Informazione sulle Procedure di Emergenza"
- "Rischio Stress Lavoro-Correlato"

## Workflow Reminder for AI
1. **🔍 Search** (find existing entities by name)
2. **✅ Create** (create missing actions/requirements)
3. **🔗 Assign** (create subscriptions for people/resources)
4. **📜 Certify** (create certificates when training completed)
5. **🔗 Link** (create certificate-action associations)

Remember: The user should never need to provide technical IDs - the AI should find everything using natural language search.