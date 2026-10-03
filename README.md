# Project Tracker

A full stack application for tracking projects, built with a **Laravel REST API** and a **Vue** frontend. Users can create, view, update, and delete projects, and move them through a guided status flow with a timeline of their history.

> Submitted for the Full Stack Developer Technical Assessment.

---
![img.png](img.png)
![img_1.png](img_1.png)
---

## Demo Video (Timelapse)

A timelapse recording of the full build process:

**[Watch the timelapse on Google Drive](https://drive.google.com/drive/folders/14I1RJS8iefi1ltof1Mgs5I5G7CHKsHhN?usp=sharing)**

---

## Table of Contents

1. [AI Tool Disclosure](#ai-tool-disclosure)
2. [Features](#features)
3. [Tech Stack](#tech-stack)
4. [Architecture](#architecture)
5. [Technical Decisions](#technical-decisions)
6. [Validation and Error Handling](#validation-and-error-handling)
7. [Getting Started](#getting-started)
8. [API Reference](#api-reference)
9. [Assumptions and Limitations](#assumptions-and-limitations)
10. [Development Log](#development-log)

---

## AI Tool Disclosure

AI tools were used in this project, as permitted by the assessment.

| Item            | Detail                                           |
|-----------------|--------------------------------------------------|
| Tool            | GitHub Copilot (agent mode)                      |
| Model           | GPT-5.6 Luna                                     |
| Used for        | Scaffolding the API, Vue app, and iterative fixes |
| Human oversight | Every generation was code reviewed; findings were fed back as new prompts |

Every prompt used, each review finding, and the resulting fixes are documented in [LOGS.md](./LOGS.md). The full process is also visible in the [timelapse video](https://drive.google.com/drive/folders/14I1RJS8iefi1ltof1Mgs5I5G7CHKsHhN?usp=sharing).

---

## Features

**Backend (Laravel REST API)**

- List (paginated), create, edit, delete
- Search, filter by status, filter by priority
- Authenticated access
- Password confirmation required for status actions and delete

**Frontend (Vue + Tailwind CSS)**

- Login and profile page (logout with confirmation, change password, change name)
- Projects table with search, filters, column sorting, column visibility toggle, and inputable page number
- Clickable rows that open the project view modal, with the timeline underneath
- Reusable modal and toast components
- URL kept in sync with the UI state
- Cream, ice-cream store themed design

---

## Tech Stack

| Layer    | Technology                       |
|----------|----------------------------------|
| Backend  | Laravel (REST API)               |
| Frontend | Vue, Tailwind CSS                |
| Database | [MySQL / SQLite: fill in]        |
| Auth     | [Sanctum / token: fill in]       |

---

## Architecture

```text
Vue SPA  --->  Laravel REST API  --->  Database
(Tailwind)     (Controllers,           (Migrations,
                Requests,               Seeders)
                Resources,
                JsonResponse)
```

- **Separation of concerns:** Vue only talks to the API; no direct data access.
- **API Resources** shape every response consistently for the frontend.
- **Form request validation** keeps controllers thin.
- **JsonResponse** is used for uniform success and error payloads.

---

## Technical Decisions

| Decision | Reason |
|----------|--------|
| Guided status flow instead of a free status dropdown | Preserves record integrity and prevents user bias in the project history |
| New projects always start as `In Progress` | Removes an arbitrary starting state chosen by the user |
| Timeline built only from the record's datetime values | The history is derived from stored data, not a separate log that could drift |
| Password confirmation on status actions and delete | Protects destructive and state-changing operations |
| Modal for project view, with the timeline beneath | Keeps users in context instead of navigating to another page |
| URL synced with UI state | Supports refresh, back button, and shareable links |
| Confirmation before logout and delete | Prevents accidental actions |

### Status Flow

| Current status | Allowed actions       | Password required |
|----------------|-----------------------|-------------------|
| Planned        | In progress, Hold     | Yes               |
| In progress    | Complete, Hold        | Yes               |
| On hold        | Un-hold               | Yes               |
| Complete       | None                  | n/a               |

---

## Validation and Error Handling

| Field        | Rule                              |
|--------------|-----------------------------------|
| Client Name  | Required                          |
| Project Name | Required                          |
| Status       | Must be valid                     |
| Priority     | Must be valid                     |
| Due Date     | Cannot be earlier than Start Date |

- Invalid requests return meaningful, field-level error messages.
- The Vue app surfaces errors through form messages and toasts.
- Validation rules are applied on the API and reflected in the frontend forms.

---

## Getting Started

> Adjust folder names and commands to match your repository.

### Prerequisites

- PHP [version], Composer
- Node.js [version], npm
- [Database]

### Backend

```bash
cd [backend-folder]
composer install
cp .env.example .env
php artisan key:generate
# configure the database in .env
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd [frontend-folder]
npm install
# set the API base URL in .env
npm run dev
```

### Default Credentials (development only)

| Field    | Value                           |
|----------|---------------------------------|
| Email    | `marcoangelo.quanico@gmail.com` |
| Password | `quanico`                       |

The seeder also creates sample project records.

---

## API Reference

> Verify these routes against your `routes/api.php`.

| Method | Endpoint                 | Description                               |
|--------|--------------------------|-------------------------------------------|
| POST   | `/api/login`             | Authenticate                              |
| GET    | `/api/projects`          | List (paginated), supports search and filters |
| POST   | `/api/projects`          | Create (starts as In Progress)            |
| GET    | `/api/projects/{id}`     | View a project                            |
| PUT    | `/api/projects/{id}`     | Edit a project                            |
| DELETE | `/api/projects/{id}`     | Delete (requires password)                |

**List query parameters:** `search`, `status`, `priority`, `page`

---

## Assumptions and Limitations

- Single-user style access; no roles or permissions.
- `Complete` is a terminal status with no further actions.
- [Add anything you would improve with more time: tests, role-based access, audit log table, etc.]

---

## Development Log

The step-by-step build history, with every Copilot prompt, review finding, and fix, is in [LOGS.md](./LOGS.md).
