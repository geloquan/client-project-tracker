# Development Logs

Development log for the **Project Tracker**: a Laravel REST API backend with a Vue frontend, built with an AI coding agent. Each phase records the goal, the Copilot command used, and the review outcome.

**Agent model used:** GPT-5.6 Luna

---

## Table of Contents

1. [Overview](#overview)
2. [Phase 1: Project Setup](#phase-1-project-setup)
3. [Phase 2: Backend REST API](#phase-2-backend-rest-api)
4. [Phase 3: Vue Dashboard](#phase-3-vue-dashboard)
5. [Phase 4: Authentication](#phase-4-authentication)
6. [Phase 5: Seed Data](#phase-5-seed-data)
7. [Phase 6: Findings and Fixes (Round 1)](#phase-6-findings-and-fixes-round-1)
8. [Phase 7: Project Flow and Timeline](#phase-7-project-flow-and-timeline)
9. [Phase 8: Findings and Fixes (Round 2)](#phase-8-findings-and-fixes-round-2)
10. [Phase 9: Findings and Fixes (Round 3)](#phase-9-findings-and-fixes-round-3)
11. [Final Status Flow Rules](#final-status-flow-rules)
12. [Summary](#summary)

---

## Overview

| Phase | Focus                          | Result              |
|-------|--------------------------------|---------------------|
| 1     | Project setup                  | Done                |
| 2     | Backend REST API               | Reviewed            |
| 3     | Vue dashboard                  | Reviewed            |
| 4     | Authentication                 | Reviewed            |
| 5     | Seed data                      | Done                |
| 6     | Fixes: delete modal, profile   | Reviewed            |
| 7     | Status flow and timeline       | Reviewed            |
| 8     | Fixes: timeline, logout, URLs  | Reviewed            |
| 9     | Fixes: project modal, actions  | Reviewed, all good  |

---

## Phase 1: Project Setup

- Init project
- Import libraries
- Create `Project` model

---

## Phase 2: Backend REST API

**Goal:** a REST API for tracking projects, ready to be consumed by Vue.

**Copilot command**

```text
This is a simple application for tracking projects.
Architecture: Laravel REST API (backend) + Vue (frontend). Vue must access
resources via the REST API.

Complete the API functions: List (paginated), Create, Edit, and Delete.
Refer to the migration for the attributes of the Project model.

Code architecture must-haves:
- Resource
- JsonResponse
- Validations, accordingly:
    - Client Name is required.
    - Project Name is required.
    - Status must be valid.
    - Priority must be valid.
    - Due Date cannot be earlier than Start Date.
    - Invalid requests should return meaningful errors.

List function:
- Search functionality
- Filtering by Status
- Filtering by Priority

Note: Vue is not being implemented yet, but the generated code is
expected to be consumed by Vue.
```

**Outcome:** code review passed.

---

## Phase 3: Vue Dashboard

**Goal:** a single-tab dashboard (Projects) with no login yet.

**Copilot command**

```text
Now produce the Vue app. Refer to the validations.
Don't add a login page; simply a dashboard with 1 tab (Projects).

Table view:
- Page number must be inputable

Functions:
- Search
- Filtering by Status
- Filtering by Priority
- Sorting by column, with sort order (A-Z, Z-A, etc.)
- Include/exclude columns (combobox)

Must have:
- Usable modal
- Usable toast

Design:
- Use Tailwind CSS
- Color theme: Cream, ice-cream store vibe
```

**Outcome:** code review passed.

---

## Phase 4: Authentication

**Goal:** the Vue app operates on the API as an authenticated user.

**Copilot command**

```text
Make the Vue app have a login page so that it can operate on the API
as authenticated.

Expectations:
- Login page
- Profile page
    - Functions: Logout, Change Password, Change Name

Add seeder:
- email: marcoangelo.quanico@gmail.com
- password: quanico
```

**Outcome:** code review passed.

---

## Phase 5: Seed Data

- Added test data (Project records) to the seeder
- Ran the seeders

---

## Phase 6: Findings and Fixes (Round 1)

| # | Finding                                        | Copilot command |
|---|------------------------------------------------|-----------------|
| 1 | Delete action did not use the modal            | `On the delete action of a record, utilize the modal.` |
| 2 | Profile button in the header was not intuitive | `The button to enter the profile is not intuitive; it isn't clear that it goes to the profile.` |

**Outcome:** code review passed after each fix.

---

## Phase 7: Project Flow and Timeline

**Goal:** replace free status selection with a guided project flow, and add a timeline UI when viewing a project.

**Copilot command**

```text
Add a timeline UI on viewing a project. The timeline should show the
history of the project, using only the datetime values in the project record.

Goal: Update the operations to have a project flow instead of freely
picking the project status. Add a timeline UI on viewing a project.

Expected:
- Update the API and the UI form for editing.
- When creating, a project always starts as "In Progress".
- Users are limited in what they can operate:
    - Actions are simply "next status", rather than inputting a
      specific status.
    - Purpose: preserve record integrity and avoid user bias.

Additional:
- Deleting requires the user's password entry for security.
```

**Outcome:** code review done; findings below.

---

## Phase 8: Findings and Fixes (Round 2)

**Findings**

| # | Area         | Finding |
|---|--------------|---------|
| 1 | Timeline UI  | Actions in the timeline view do not work |
| 2 | Timeline UI  | "Created at" is used instead of "Planned at" |
| 3 | Logout       | Logout button does not ask for confirmation |

**Copilot command**

```text
Fix or apply:

Findings on timeline UI:
1. Actions on the timeline view do not work.
2. Created at value is considered rather than Planned at.

Vue must update the URL relative to what is shown in the UI.

The logout button does not ask for confirmation. Ask for confirmation.

Allow rows in the table to be clickable, assuming project view.

Move the timeline into a modal instead of a separate page.
```

**Outcome:** code review done; findings below.

---

## Phase 9: Findings and Fixes (Round 3)

**Findings**

| # | Area              | Finding |
|---|-------------------|---------|
| 1 | Timeline modal    | Applied, but not as expected |
| 2 | Project view      | Incorrect actions shown (on-hold projects only offered "mark as complete", with no un-hold option; a bug) |
| 3 | Actions           | Password confirmation not required on actions |

**Copilot command**

```text
Move the Project view into a modal instead, with the timeline view under it.

On-hold projects do not have an un-hold option, only mark as complete.
This is a bug.

Rules for operation:
- When on hold: the only action is un-hold.
- When planned: actions are in-progress and hold.
- When in-progress: actions are complete and hold.
- All actions must require password input confirmation.
```

**Outcome:** code review passed. **All good.**

---

## Final Status Flow Rules

| Current status | Allowed actions         | Password required |
|----------------|-------------------------|-------------------|
| Planned        | In progress, Hold       | Yes               |
| In progress    | Complete, Hold          | Yes               |
| On hold        | Un-hold                 | Yes               |
| Complete       | None                    | n/a               |

Additional rules:

- New projects always start as **In Progress**.
- Users trigger "next status" actions; they never type a specific status.
- Deleting a project requires password entry.

---

## Summary

- [x] Laravel REST API: list (paginated), create, edit, delete, with validation
- [x] Search, status filter, priority filter
- [x] Vue dashboard (Tailwind, cream theme) with sorting, column toggle, modal, toast
- [x] Login and profile (logout, change password, change name)
- [x] Seeders for the user and test projects
- [x] Delete via confirmation modal
- [x] Clearer profile entry point
- [x] Guided status flow with password-confirmed actions
- [x] Project view as a modal with the timeline underneath
- [x] Clickable table rows, URL kept in sync with the UI
- [x] Logout confirmation
- [x] Password-protected delete
