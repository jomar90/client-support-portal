# Client Support Portal

A full-stack support ticket management application built with **Laravel, Vue 3, TypeScript, and MySQL**.

The application allows users to create and track support tickets, while administrators can manage requests, assign tickets, update statuses, respond to users, and maintain internal notes.

It includes role-based authorization, authentication with Laravel Sanctum, email notifications, and a Vue frontend organized into separate domains.

## Features

### User Features

- Register, log in, and log out
- Reset forgotten passwords
- Create and edit support tickets
- View personal tickets and their statuses
- Categorize support requests
- Read administrator responses
- Receive email notifications

### Administrator Features

- View and manage all support tickets
- Assign tickets to administrators
- Update ticket statuses: Pending, In Progress, and Resolved
- Create and edit ticket responses
- Add, edit, view, and delete internal notes
- Create and delete ticket categories
- View and update user information
- Delete user accounts when permitted

Internal notes are visible only to administrators. Laravel enforces access permissions on the backend.

## Technology Stack

| Backend | Frontend | Database & Tools |
|---|---|---|
| Laravel | Vue 3 | MySQL |
| PHP 8.4+ | TypeScript | Vite |
| Laravel Sanctum | Vue Router | Git |
| Eloquent ORM | Axios | Composer / npm |
| Form Requests | Custom Store Module Factory | PHPUnit |

## Project Structure

The Vue frontend follows a domain-based structure, keeping related pages, types, routes, and stores together.

```text
resources/js/
├── domains/
│   ├── administrators/
│   ├── auth/
│   ├── categories/
│   ├── tickets/
│   └── users/
├── services/
│   ├── http/
│   ├── error/
│   └── store/
├── components/
└── router/
```

The application uses a small reusable Store Module Factory for loading and storing collections.

Other operations, such as creating or updating tickets, are handled through domain-specific actions.

Axios handles communication with the Laravel API, while Laravel Form Requests and authorization rules validate and protect operations.

## Installation

### Requirements

- PHP 8.4 (8.4.1 or newer) or PHP 8.5
- Composer 2
- Node.js 24 and npm
- MySQL
- Required PHP extensions, including PDO MySQL, mbstring, XML, and fileinfo
- PDO SQLite PHP extension (used by the automated tests)

### 1. Clone the repository

Clone this repository and open the project directory.

### 2. Install dependencies

Run:

`composer install`

`npm ci`

### 3. Configure the environment

Create your `.env` file from the example:

`cp .env.example .env`

On Windows PowerShell:

`Copy-Item .env.example .env`

Generate the application key:

`php artisan key:generate`

Create a MySQL database named `client_support_portal` and configure the database connection in `.env`.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=client_support_portal
DB_USERNAME=root
DB_PASSWORD=
```

`.env.example` already sets `APP_URL=http://localhost:8000` and `SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000`. Open the application at the same address as `APP_URL` and use one hostname consistently.

### 4. Run migrations and seeders

`php artisan migrate --seed`

This creates the required tables and inserts development example data.

If your database already contains data you want to keep, back it up first and run `php artisan migrate` instead. It applies only the migrations that have not run yet and leaves existing data in place. Do not use `php artisan migrate:fresh` on that database, because it deletes every table.

### 5. Build and run the application

Build the frontend:

`npm run build`

Start Laravel:

`php artisan serve --host=localhost --port=8000`

Open:

`http://localhost:8000`

For frontend development, run `npm run dev` in a separate terminal and keep opening `http://localhost:8000`, not the Vite address.

## Demo Accounts

The database seeder creates the following development accounts:

| Email | Password | Role |
|---|---|---|
| admin@example.com | password | Administrator |
| admin2@example.com | password | Administrator |
| user@example.com | password | User |
| other@example.com | password | User |

These credentials are for local development and demonstration only.

The sample database includes tickets with different statuses, categories, assignments, responses, and an internal note.

## Authentication and Authorization

Authentication uses Laravel Sanctum with session-based authentication and CSRF protection.

The application supports two roles using a boolean `admin` field.

**Normal users** can create, edit, and view their own tickets and read responses associated with those tickets.

**Administrators** can manage tickets, assignments, statuses, categories, internal notes, and user accounts.

Authorization is enforced through Laravel. Frontend navigation and buttons reflect permissions but do not replace backend security checks.

## Email Notifications

The application supports:

- Registration confirmation emails
- Password reset emails
- Notifications when administrators respond to tickets

For local development, `.env.example` already sets `MAIL_MAILER=log`.

Emails will be written to `storage/logs/laravel.log` rather than delivered to an inbox.

For real email delivery, configure an SMTP provider in `.env` and run `php artisan config:clear`.

## Data Handling

Categories that are still linked to tickets cannot be deleted.

A user cannot be deleted while they own or are assigned unresolved tickets, and administrators cannot delete their own account. Deleting a user also deletes their resolved tickets, including those tickets' responses and notes, and clears their ticket assignments. Responses and internal notes they wrote on other tickets are kept and shown as written by "Deleted user".

## Running Tests

Run the Laravel tests:

`php artisan test`

Check TypeScript:

`npm run typecheck`

Build the frontend:

`npm run build`

Validate the Composer configuration and check that your PHP version meets the requirements:

`composer validate --no-check-publish`

`composer check-platform-reqs`

The automated Laravel tests use an in-memory SQLite database, separate from the application's MySQL database.

## Documentation

Additional project documentation is available:

- [Project Architecture and Explanation](docs/PROJECT_EXPLANATION.md)
- [Database Diagram](docs/erd.svg)
- [Database Schema Notes](docs/erd.md)
- [Ticket Creation Flowchart](docs/TICKET_CREATION_WALKTHROUGH.md)
- [Requirements Checklist](docs/REQUIREMENTS_CHECKLIST.md)
- [Setup and Testing Guide](docs/SETUP_AND_TESTING.md)

## Project Scope

Client Support Portal was built for a final full-stack assignment with 27 defined functional requirements. Its implementation was deliberately scoped to those requirements: delivering the requested support-ticket workflows end to end, with appropriate validation, authorization, database relationships, and automated tests.

The project is a complete implementation of its intended assignment scope, rather than a general-purpose or production-scale helpdesk platform. Features beyond the brief were left outside this version to keep the implementation focused on the specified functionality.

## Potential Future Enhancements

If the application is expanded beyond its original scope, possible extensions include:

- **Pagination and filtering:** Server-side pagination and more flexible search for larger ticket and user datasets.
- **Authentication safeguards:** Login and API rate limiting, alongside optional verified-email requirements.
- **Data access and performance:** More complex reporting queries using Eloquent or Query Builder, supported by query profiling, indexing, and caching where measurements justify them.
- **Monitoring and logging:** Structured application logs, error tracking, and performance monitoring for deployed environments.
- **Localization:** Multiple interface languages and locale-aware content.
- **External integrations:** Connections to third-party support, messaging, or reporting services.
- **Testing and extensibility:** Dedicated unit tests and test-first coverage for future functionality, with custom service-container bindings or providers where additional integrations make them useful.

These are possible extensions for a broader product scope, not missing requirements of the current assignment.
