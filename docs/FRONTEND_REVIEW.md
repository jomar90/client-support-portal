# Frontend Architecture

## Overview

I built the frontend using Vue 3, TypeScript, Vue Router, and Axios. I organized the code into separate domains so related pages, routes, types, and stores are easier to find and maintain.

## 1. Frontend Structure

I divided the frontend into five main domains:

| Domain | Functionality |
|---|---|
| `auth` | Registration, login, logout, and password reset |
| `tickets` | Ticket creation, editing, details, assignment, status, responses, and internal notes |
| `categories` | Viewing, creating, and deleting categories |
| `administrators` | Loading administrators for ticket assignment |
| `users` | Administrator-only user overview and editing |

Each domain contains the files needed for its functionality.

## 2. Shared Services

I created shared services to avoid repeating the same code in different parts of the application.

- **services/http:** Handles Axios requests, Laravel Sanctum sessions, and CSRF protection.
- **services/error:** Handles general request errors and form validation messages.
- **services/store:** Contains my Store Module Factory for managing collections.

The Store Module Factory uses an array, a computed getter, `setAll()`, and `getAll()` to load and store collections.

For other operations, such as creating tickets, updating tickets, and managing notes, I use separate actions inside their domains.

I keep form data in local Vue components.

## 3. Pages and Permissions

I use the same form component for creating and editing tickets.

The ticket detail page shows ticket information, responses, assignment controls, status changes, and internal notes.

Administrator-only options are hidden from normal users.

I also added authorization checks in Laravel because hiding buttons in Vue is not enough to protect backend data.

New users who register are always created as normal users with `admin = false`.

## 4. How the Frontend Connects to Laravel

For ticket creation, the process starts in `domains/tickets/pages/Create.vue`.

The form sends data through `tickets/store.ts` and the shared Axios service. Laravel then receives the request through the API route, validates it using `StoreTicketRequest`, and saves it through `TicketController::store()`.

I explained this process in more detail in [TICKET_CREATION_WALKTHROUGH.md](TICKET_CREATION_WALKTHROUGH.md).

## Final Summary

I kept the frontend structure simple by using Vue's built-in features, including `ref`, `computed`, `onMounted`, props, emits, and async functions.

This allowed me to manage forms, shared data, API requests, and permissions without adding another state-management framework.
