# 4HSE System Workflow Guide

## Overview

The 4HSE system manages health, safety, security, and environmental (HSE) requirements through a structured workflow involving actions, subscriptions, certificates, and their relationships.

## Core Entities and Concepts

### 1. Actions
**Actions** are the foundation of the 4HSE system. They represent requirements that can be assigned to people or resources:

- **Training Courses** (`TRAINING`) - Educational requirements like "General Worker Training" or "VDT Usage Training"
- **Maintenance Plans** (`MAINTENANCE`) - Scheduled maintenance activities for equipment
- **Health Surveillance Plans** (`HEALTH`) - Medical monitoring and health checks
- **Procedures** (`CHECK`) - Safety procedures and emergency protocols
- **Individual Protection Plans** (`PER`) - Personal protective equipment requirements

**Tool**: Use `create_4hse_action` to add new training courses, maintenance plans, procedures, etc.

### 2. Action Subscriptions (The "Need")
**Action Subscriptions** represent the assignment of requirements to specific people or resources. They create the "need" that must be satisfied:

- Links a person or resource to an action requirement
- Establishes that someone needs training, equipment needs maintenance, etc.
- Creates the requirement that must later be resolved by a certificate

**Tool**: Use `create_4hse_action_subscription` to assign requirements to people or resources.

### 3. Certificates (The "Resolution")
**Certificates** prove that requirements have been satisfied:

- Evidence that training was completed, maintenance was performed, etc.
- Specify coverage periods (from issue date to expiration date)
- Target specific people or resources
- "Resolve" the needs created by action subscriptions

**Tool**: Use `create_4hse_certificate` to document completed training, maintenance, etc.

### 4. Certificate-Action Associations (The "Link")
**Certificate-Action** relationships specify exactly which action requirements a certificate satisfies:

- Links certificates to specific actions
- Completes the requirement resolution workflow
- May have different expiration dates than the certificate itself

**Tool**: Use `create_4hse_certificate_action` to link certificates to specific actions.

### 5. Demands (Alternative Requirements)
**Demands** represent a different type of requirement relationship:

- Similar to action subscriptions but for specific requests or demands
- Used for particular scenarios where standard subscriptions don't apply

**Tool**: Use `create_4hse_demand` for specific requirement requests.

## Typical Workflows

### Adding Training Courses to a Project

**Scenario**: You want to add training courses like "General Worker Training" and "VDT Usage Training" to a project location.

**Correct Workflow**:
1. **Create Actions** (the training courses) using `create_4hse_action` with `actionType: "TRAINING"`
2. **Create Action Subscriptions** to assign these courses to specific people using `create_4hse_action_subscription`
3. **Create Certificates** when training is completed using `create_4hse_certificate`
4. **Link Certificates to Actions** using `create_4hse_certificate_action`

**❌ Common Mistake**: Using `create_4hse_certificate` directly to "add courses" - certificates don't create courses, they certify completion of existing courses.

### Equipment Maintenance Workflow

**Scenario**: Setting up maintenance requirements for equipment.

**Correct Workflow**:
1. **Create Action** (maintenance plan) using `create_4hse_action` with `actionType: "MAINTENANCE"`
2. **Create Action Subscription** to assign maintenance to specific equipment using `create_4hse_action_subscription`
3. **Create Certificate** when maintenance is performed using `create_4hse_certificate`
4. **Link Certificate to Action** using `create_4hse_certificate_action`

## Entity Relationships

```
Action (Training Course, Maintenance Plan, etc.)
    ↓
Action-Subscription (Assigns requirement to person/resource)
    ↓ (creates need)
Certificate (Proves completion/satisfaction)
    ↓
Certificate-Action (Links certificate to specific action)
```

## Key Principles

1. **Actions First**: Always create the action (course, plan, procedure) before assigning it
2. **Subscriptions Create Needs**: Action subscriptions establish what's required
3. **Certificates Resolve Needs**: Certificates prove requirements have been met
4. **Linking Completes Workflow**: Certificate-action associations specify exactly what was satisfied

## Tool Selection Guide

| Task | Correct Tool | Wrong Tool |
|------|-------------|------------|
| Add training courses to project | `create_4hse_action` | `create_4hse_certificate` |
| Assign training to person | `create_4hse_action_subscription` | `create_4hse_certificate` |
| Record training completion | `create_4hse_certificate` | `create_4hse_action` |
| Link certificate to course | `create_4hse_certificate_action` | N/A |

## Action Types Reference

- **TRAINING**: Educational courses, certifications, skill development
- **MAINTENANCE**: Equipment servicing, inspections, repairs
- **HEALTH**: Medical surveillance, health monitoring, occupational health
- **CHECK**: Procedures, protocols, safety checks, audits
- **PER**: Personal protective equipment, individual safety measures

Remember: The workflow always starts with creating the action (the requirement), then assigning it (creating the need), then satisfying it (creating certificates), and finally linking them (completing the resolution).

## Common User Request Patterns & AI Response Guide

### 🔍 "Add courses to my project/office"
**User says**: *"Can you add these training courses to Milano office of my 'Progetto Test Ai' project?"*

**AI should do**:
1. 🔍 **Search first, don't ask for IDs**: Use `list_4hse_projects` with `filterName="Progetto Test Ai"`
2. 🔍 **Find the office**: Use `list_4hse_offices` with `filterName="Milano"` and `filterProjectName="Progetto Test Ai"`
3. 🔍 **Check existing courses**: Use `list_4hse_actions` with `filterActionType="TRAINING"` to see if courses already exist
4. ✅ **Create missing courses**: Use `create_4hse_action` with `actionType="TRAINING"` for any courses that don't exist
5. ✅ **Assign to people**: Use `create_4hse_action_subscription` to assign courses to people in that office

**❌ AI should NOT**: Ask for project IDs, office IDs, tenant IDs, or any technical identifiers

### 🔍 "Who needs training in my project?"
**User says**: *"Show me who needs training in MyCompany project"*

**AI should do**:
1. 🔍 **Find project**: Use `list_4hse_projects` with `filterName="MyCompany"`
2. 🔍 **Find action subscriptions**: Use `list_4hse_action_subscriptions` with `filterActionType="TRAINING"` and `filterStatus="NEW"` or `filterStatus="EXPIRED"`

### 🔍 "Create certificate for completed training"
**User says**: *"Mario completed the General Worker Training course, create his certificate"*

**AI should do**:
1. 🔍 **Find the person**: Use appropriate person search tool
2. 🔍 **Find the training course**: Use `list_4hse_actions` with `filterName="General Worker Training"`
3. ✅ **Create certificate**: Use `create_4hse_certificate`
4. ✅ **Link to course**: Use `create_4hse_certificate_action`

### Key Principles for AI Responses:
- **🔍 Search First**: Always use `list_*` tools before asking for IDs
- **🏢 Use Natural Language**: Search by names like "Milano", "Progetto Test Ai", "Formazione Generale"
- **📋 Check Existing**: Before creating, check if courses/assignments already exist
- **🔄 Follow Workflow**: Action → Subscription → Certificate → Certificate-Action
- **❌ Never Ask for IDs**: Use search tools to find technical identifiers automatically

## Complete Tool Reference

### Action Tools (Training Courses, Maintenance Plans, Procedures, etc.)
- **Create**: `create_4hse_action` - Add new training courses, maintenance plans, procedures, individual protection plans, or health surveillance plans
- **List**: `list_4hse_actions` - Find existing actions by type, name, office, project
- **View**: `view_4hse_action` - Get detailed information about a specific action
- **Update**: `update_4hse_action` - Modify existing action details
- **Delete**: `delete_4hse_action` - Remove actions (use with caution)

### Action Subscription Tools (The "Needs" - Assignments)
- **Create**: `create_4hse_action_subscription` - Assign actions to people or resources
- **List**: `list_4hse_action_subscriptions` - Find who needs what training/maintenance
- **View**: `view_4hse_action_subscription` - Get details about specific assignments
- **Update**: `update_4hse_action_subscription` - Modify existing assignments
- **Delete**: `delete_4hse_action_subscription` - Remove assignments

### Certificate Tools (The "Resolutions" - Proof of Completion)
- **Create**: `create_4hse_certificate` - Document completed training, maintenance, etc.
- **List**: `list_4hse_certificates` - Find certificates by person, action type, dates
- **View**: `view_4hse_certificate` - Get detailed certificate information
- **Update**: `update_4hse_certificate` - Modify certificate details or dates
- **Delete**: `delete_4hse_certificate` - Remove certificates (use with caution)

### Certificate-Action Tools (The "Links" - Specific Coverage)
- **Create**: `create_4hse_certificate_action` - Link certificates to specific actions
- **List**: `list_4hse_certificate_actions` - Find which actions certificates cover
- **View**: `view_4hse_certificate_action` - Get details about certificate-action links
- **Update**: `update_4hse_certificate_action` - Modify links or expiration dates
- **Delete**: `delete_4hse_certificate_action` - Remove certificate-action links

### Demand Tools (Alternative Requirements)
- **Create**: `create_4hse_demand` - Create specific demands for actions on resources
- **List**: `list_4hse_demands` - Find demands by action type, resource, office
- **View**: `view_4hse_demand` - Get detailed demand information
- **Update**: `update_4hse_demand` - Modify existing demands
- **Delete**: `delete_4hse_demand` - Remove demands