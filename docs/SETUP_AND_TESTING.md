# Client Support Portal – Setup and Testing

## Overview

This document explains how to set up and test my Client Support Portal locally.

I included the required software, database setup, automated checks, manual testing steps, and email configuration.

The main installation instructions are available in [README.md](../README.md).

## 1. Requirements

I used the following tools for the project:

- PHP 8.4.1 or later, depending on the locked Composer dependencies.
- Composer 2.
- Node.js and npm compatible with `package-lock.json`.
- MySQL for the application database.
- PDO SQLite for automated tests.

The example MySQL database name is `client_support_portal`.

The Laravel URL and Sanctum stateful domain settings should use the same hostname.

For local email testing, I use `MAIL_MAILER=log` in the `.env` file.

## 2. Application Setup

I use the README instructions to install the Composer and npm dependencies, create the `.env` file, generate the Laravel application key, and start the application.

For a new, empty database, I run:

```powershell
php artisan migrate --seed
```

This creates the database tables and inserts demonstration data.

For an existing database, I make a backup before running migrations.

I avoid using `php artisan migrate:fresh` on a database containing important data because it deletes all existing tables.

I also added a migration that makes `responses.user_id` and `notes.user_id` nullable and uses `ON DELETE SET NULL`. This keeps responses and notes on surviving tickets when their author is deleted.

## 3. Automated Testing

I use the following commands from the project directory containing `artisan`:

```powershell
composer validate --no-check-publish
composer check-platform-reqs
php artisan test
npm run typecheck
npm run build
php artisan route:list --except-vendor
```

These commands check Composer configuration and platform requirements, run Laravel tests, check TypeScript, build the frontend, and display registered routes.

The automated tests use an in-memory SQLite database configured in `phpunit.xml`. They do not change the application's MySQL database.

The recorded results are available in [VERIFICATION.md](VERIFICATION.md).

## 4. Manual Testing Checklist

I prepared these steps to check the main application workflows through the browser.

| Test | Expected Result |
|---|---|
| Login with incorrect details | An error appears, and login is rejected |
| Register a new account | A normal user is created, and a confirmation email is generated |
| Reset a password | A valid token works; invalid or reused tokens fail |
| Login as a normal user | Only their own tickets are displayed |
| Create and edit a ticket | The ticket and categories are saved under the correct user |
| Open another user's ticket | Laravel denies access |
| Login as an administrator | All tickets, category management, and user management are available |
| Assign a ticket | An administrator can assign it to themselves or another seeded administrator |
| Change ticket status | Pending, In Progress, and Resolved can be saved |
| Create and edit a response | The response is saved, and a notification is generated when created |
| Create, edit, and delete internal notes | Administrators can manage notes; normal users cannot access them |
| Create and delete categories | Unused categories can be deleted; categories linked to tickets cannot |
| Edit user contact information | Changes are saved without changing the user's role or password |
| Delete a user with unresolved tickets | Laravel rejects the deletion |
| Delete a disposable user who wrote responses or notes on another ticket | Those records remain, showing **Deleted user** as the author |
| Logout | Protected pages require login again |

I use disposable demonstration accounts and tickets when testing deletion because some deletion operations permanently remove records.

I do not use production or personal data for these tests.

## 5. Email Testing

I use Laravel's log mail driver during local development.

In `.env`, I set:

```text
MAIL_MAILER=log
```

After registration, password reset, or an administrator response, I check:

`storage/logs/laravel.log`

This helps me confirm that the application generates the expected email messages.

For actual email delivery, SMTP credentials must be configured. After changing the mail settings, I clear Laravel's configuration cache:

```powershell
php artisan config:clear
```

An email appearing in the Laravel log only confirms that the message was generated. It does not confirm delivery to a real inbox.

## Final Summary

I included automated and manual testing instructions to help check the main features of the Client Support Portal.

The automated tests cover backend functionality, while the manual checklist covers login, ticket handling, administrator operations, registration, and permissions.

The application uses SQLite for automated tests and MySQL for its configured application database.

Real SMTP delivery and MySQL runtime behavior should also be checked before using the application in a live environment.

The latest recorded testing information is available in [VERIFICATION.md](VERIFICATION.md).
