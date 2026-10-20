# Client Support Portal

Client Support Portal is a full-stack application for managing customer support requests. Customers can create and track tickets, while administrators can manage assignments, statuses, categories, and replies.

Built with **Laravel, Vue 3, and TypeScript**, the application separates authentication, authorization, validation, API responses, and frontend state management.

**Status:** Active development. The core ticket workflow is implemented; additional account management and administrative features are planned.

## Features

**Customers**

- Log in and log out.
- Create tickets with a title, description, and optional categories.
- View and edit their own tickets.
- Follow ticket statuses and read administrator replies.
- Receive an email notification when an administrator replies.

**Administrators**

- View and edit tickets from all customers.
- Assign tickets to administrators.
- Change ticket status (`pending`, `in_progress`, `resolved`).
- Add and edit ticket replies.
- Create and delete categories; categories linked to tickets cannot be deleted.

Access restrictions are enforced by the Laravel backend, not just by hiding actions in the Vue interface.

## Tech stack

| Area | Technologies |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Eloquent ORM |
| Database | MySQL; SQLite in memory for tests |
| Authentication | Laravel Sanctum with session-based SPA authentication |
| Frontend | Vue 3, TypeScript, Vue Router, Vite |
| HTTP and state | Axios HTTP service, custom Store Module Factory, Vue reactivity |
| Testing | PHPUnit, Laravel Feature Tests |

## Architecture

The Vue frontend calls Laravel endpoints through a shared Axios service. A custom **Store Module Factory** organizes reactive state and actions for frontend features such as authentication, tickets, and categories.

On the backend, **Form Requests** validate incoming data, **Policies** enforce ticket permissions, **controllers** coordinate application actions, and **API Resources** format ticket responses. Eloquent models define the database relationships.

```text
app/Http/Controllers/     API and authentication controllers
app/Http/Requests/        Request validation and authorization
app/Http/Resources/       Ticket API response formatting
app/Policies/            Ticket authorization rules
app/Models/              Eloquent models and relationships
app/Mail/                Ticket reply notification
resources/js/views/      Vue pages
resources/js/stores/     Reactive stores and Store Module Factory
resources/js/services/   Shared Axios HTTP service
resources/js/router/     Vue Router and navigation guards
database/                Migrations, factories, and seeders
tests/Feature/           Feature tests
docs/erd.md              Entity relationship diagram
```

The database entities and relationships are documented in the [ERD](docs/erd.md).

## Getting started

### Requirements

- PHP 8.3 or newer and Composer
- MySQL
- Node.js 20.19+ (20.x) or 22.12+ and npm
- PHP's SQLite driver (`pdo_sqlite`) to run the automated tests

Clone the repository and open its root directory. Then install the backend and frontend dependencies:

```bash
composer install
npm ci
```

Create your local environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead.

Create an empty MySQL database named `client_support_portal`, or choose another name, and update the `DB_*` values in `.env` to match your local configuration.

```bash
php artisan key:generate
php artisan migrate --seed
```

Start Laravel and Vite in separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Open <http://localhost:8000>.

### Demo accounts and sample data

Running `php artisan migrate --seed` creates three local accounts. All use the password **`password`**.

| Role | Email |
| --- | --- |
| Administrator | `admin@example.com` |
| Customer | `user@example.com` |
| Customer | `other@example.com` |

The seeder also creates **four categories, four tickets, and two administrator replies**, with tickets in different statuses. The two customer accounts have separate tickets, making it possible to verify that customers only see their own requests.

**These are development credentials only. Do not use them in a publicly deployed environment.**

### Email notifications

The example environment sets `MAIL_MAILER=log`, so notification emails are written to `storage/logs/laravel.log` rather than sent to a real inbox. Add an administrator reply to a ticket to inspect the notification locally.

## Tests

Run the complete test suite:

```bash
php artisan test
```

To run the ticket-related tests only:

```bash
php artisan test --filter=Ticket
```

**Last verified locally: 19 tests passed (55 assertions), including 17 ticket-focused Feature Tests.**

The Feature Tests cover ticket ownership, administrator permissions, status and assignment validation, category relationships and deletion rules, and reply notifications. Tests use an in-memory SQLite database rather than the local MySQL development database.

## API overview

The ticket and category endpoints use the `/api` prefix and are protected by Laravel Sanctum.

| Method | Endpoint | Access |
| --- | --- | --- |
| GET | `/api/user` | Authenticated user |
| GET, POST | `/api/tickets` | Customer (own tickets), administrator (all tickets) |
| GET, PATCH | `/api/tickets/{ticket}` | Ticket owner or administrator |
| PATCH | `/api/tickets/{ticket}/assignment` | Administrator |
| PATCH | `/api/tickets/{ticket}/status` | Administrator |
| POST | `/api/tickets/{ticket}/responses` | Administrator |
| PATCH | `/api/tickets/{ticket}/responses/{response}` | Administrator |
| GET | `/api/ticket-categories` | Authenticated user |
| GET, POST | `/api/categories` | Administrator |
| DELETE | `/api/categories/{category}` | Administrator |
| GET | `/api/administrators` | Administrator |

Authentication uses web routes (`/login`, `/logout`), and the password reset link can be requested through `/forgot-password`.

## Roadmap

- [ ] Complete the password reset flow (requesting a reset link is implemented).
- [ ] Add user registration and confirmation emails.
- [ ] Build administrator-only internal notes (the database structure already exists).
- [ ] Add administrator user management: list, edit, and delete users.
- [ ] Improve interface styling and responsiveness.
- [ ] Expand automated coverage for the remaining workflows.

## Current limitations

- Login rate limiting has not been added.
- The ticket list is not paginated.
- Reply notification emails are sent during the request rather than through a queue.
- The application is configured for local development and has not been deployed.
