# Client Support Portal – Project Explanation

## Overview

I built the Client Support Portal using Laravel for the backend and Vue 3 with TypeScript for the frontend.

Normal users can register, log in, create tickets, and follow their ticket status. Administrators can manage tickets, categories, responses, internal notes, and user accounts.

## 1. Frontend Structure

I organized the frontend into five domains inside `resources/js/domains`:

- `auth` – Registration, login, logout, and password reset.
- `tickets` – Ticket creation, editing, details, responses, and notes.
- `categories` – Category management.
- `administrators` – Administrator selection for ticket assignment.
- `users` – User management.

I also created shared services for HTTP requests, error handling, and storing collections.

My Store Module Factory uses `getAll()` to load data, `setAll()` to store it, and a computed `all` getter to access the collection.

## 2. Ticket Creation and Editing

When a user creates a ticket, Vue collects the title, description, and selected categories.

`Create.vue` sends the information through the ticket store and Axios to `POST /api/tickets`.

Laravel Sanctum checks authentication, and `StoreTicketRequest` validates the data.

I use `TicketController::store()` to create the ticket for the logged-in user and connect its categories inside a database transaction.

Laravel returns the new ticket ID, and Vue opens its detail page. The ticket overview loads the updated list when the user returns.

I use `PATCH /api/tickets/{ticket}` for editing tickets. Normal users can edit their permitted ticket fields, while administrators can also change ticket status and assignments.

Laravel validates that an assigned user is an administrator. Categories can be selected by normal users, but only administrators can manage them.

The full process is explained in [TICKET_CREATION_WALKTHROUGH.md](TICKET_CREATION_WALKTHROUGH.md).

## 3. Responses and Internal Notes

I added ticket responses so administrators can reply to users. When an administrator creates a response, the application also generates an email notification for the ticket owner.

Administrators can create and edit responses.

I created separate operations for internal notes, allowing administrators to add, view, edit, and delete them.

Internal notes are private. I check administrator permissions in both the ticket controller and `TicketResource` before returning note data.

Notes are displayed in chronological order.

If the author of a saved response or note has been deleted, the application displays **Deleted user** instead of their name.

## 4. User Management and Deletion

I added an administrator-only user overview showing first name, last name, email, phone number, and role.

Administrators can update user contact details, but the edit form does not allow role or password changes.

I also added deletion restrictions to protect active tickets:

- Administrators cannot delete their own accounts.
- Users cannot be deleted while they own unresolved tickets.
- Users cannot be deleted while assigned unresolved tickets.
- Resolved tickets owned by a deleted user are removed with their related records.
- Assignments on surviving tickets are cleared.
- Responses and notes written by that user on other tickets are preserved.

For preserved responses and notes, I use nullable author references with `ON DELETE SET NULL`. The application then displays **Deleted user** as the author.

## 5. Authentication and Emails

I use Laravel Sanctum for session-based authentication and CSRF protection.

Users can register, log in, log out, and reset forgotten passwords.

Registration always creates a normal user with `admin = false` and generates a confirmation email. Password reset uses Laravel's password broker.

The application also generates email notifications when administrators respond to tickets.

During local development, I use `MAIL_MAILER=log` to write emails to `storage/logs/laravel.log`. Real email delivery requires SMTP configuration.

## 6. Database and Testing

I created Eloquent models, database migrations, relationships, factories, and seeders.

The migrations define the database tables and foreign keys. Factories help create test records, while the seeder provides example users, tickets, and related data.

The database relationships are documented in [erd.md](erd.md).

I use the following commands to check the application:

```text
php artisan test
npm run typecheck
npm run build
```

Additional setup and manual testing instructions are available in [SETUP_AND_TESTING.md](SETUP_AND_TESTING.md).

The recorded test results and remaining checks are documented in [VERIFICATION.md](VERIFICATION.md).

## Final Summary

The Client Support Portal includes registration, authentication, ticket management, administrator responses, internal notes, category management, user administration, and email notifications.

I separated frontend functionality into domains and used Laravel validation and authorization to protect user information and ticket operations.

The project also includes database documentation, automated tests, and setup instructions.
