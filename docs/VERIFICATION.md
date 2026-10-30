# My Client Support Portal Assignment — Final Verification Report

**Date:** October 30, 2026

## What I Built

I built the Client Support Portal by working through all 27 user stories. The finished application provides a complete support-ticket workflow for normal users and administrators.

Here is a breakdown of the functionality included in the project:

- **Project Foundation (Stories 1–3):** I created the database structure, models, relationships, migrations, factories, seeders, and the Laravel and Vue application setup.

- **Authentication (Stories 4–6):** Users can log in, log out, and reset a forgotten password. The application distinguishes normal users from administrators and protects restricted routes and API operations.

- **Ticket Management (Stories 7–12):** Users can view, create, edit, and open their own tickets. Administrators can view all tickets, assign them to administrators, and change their status between Pending, In Progress, and Resolved.

- **Category Management (Stories 13–15):** Administrators can view, create, and delete ticket categories. Categories that are currently linked to tickets cannot be deleted.

- **Ticket Responses (Stories 16–18):** Administrators can respond to tickets and edit their responses. Ticket owners can read the responses on their tickets, and the application generates an email notification when a new response is created.

- **Internal Notes (Stories 19–22):** Administrators can create, view, edit, and delete private notes attached to tickets. Notes appear in chronological order and are never included in the ticket data returned to normal users.

- **User Management (Stories 23–25):** Administrators can view registered users in a table and update their contact information. Protected deletion rules prevent administrators from deleting their own account or deleting users who own or are assigned unresolved tickets.

- **Public Registration (Story 26):** New users can register through the frontend. The backend always creates them as normal users, ignores attempts to submit an administrator role, and hashes passwords using Laravel’s hashed model cast. A confirmation email is generated after successful registration.

- **Documentation and Workflow (Story 27):** I created a detailed ticket-creation flowchart, documented the database relationships, prepared setup and testing instructions, and completed the 27-item requirements checklist.

The frontend uses Vue 3 and TypeScript with domain-based folders for authentication, tickets, categories, administrators, and users. Laravel handles validation, authentication, database operations, email generation, and authorization.

## Testing and How I Checked It

I developed and tested the project locally using PHP 8.4 and Node.js 24.

### Automated Checks

I ran the following checks:

```text
composer validate --no-check-publish
npm run typecheck
npm run build
php artisan route:list --except-vendor
php artisan test
```

All 52 automated tests passed successfully.

The tests cover:

- Login, logout, and protected API access
- Password reset links and token validation
- Ticket creation, editing, ownership, and authorization
- Administrator assignment and ticket status changes
- Category creation and protected deletion
- Ticket responses and email notifications
- Internal-note privacy and administrator-only operations
- User information updates and protected account deletion
- Public registration, password hashing, role protection, and confirmation emails
- Attempts to access notes or records belonging to another ticket
- Validation errors and unauthorized requests

The automated tests use an isolated in-memory SQLite database. They do not modify the application’s MySQL database.

I also ran the migrations and database seeder against an isolated SQLite database. All ten migrations completed successfully, and the seeder created demonstration users, tickets, categories, responses, assignments, statuses, and an internal note.

The TypeScript check completed without errors, and Vite successfully created the production frontend build.

## Manual Browser Checks I Performed

I also checked the main workflows through the browser using the production frontend build, an isolated SQLite database, and Laravel’s log mail driver.

1. I logged in as an administrator and confirmed that the administrator could view all tickets and access the Users and Categories sections.

2. I opened the user-management table and confirmed that it displayed the required user information.

3. I edited a user’s contact information and confirmed that the changes were saved and displayed after returning to the overview.

4. I opened a ticket as an administrator and confirmed that internal notes were displayed.

5. I created and edited an internal note and confirmed that the ticket details refreshed with the updated content.

6. I logged out and registered a new normal-user account through the public registration page.

7. I confirmed that registration displayed a success message and generated a confirmation email in the local Laravel mail log.

8. I logged in with a normal-user account and confirmed that the user could see their own tickets but could not see internal notes or administrator controls.

9. I visited the user-management route as a normal user and confirmed that the frontend redirected back to the ticket overview. The Laravel backend also independently rejects unauthorized user-management requests.

10. I created a ticket as a normal user and confirmed that it was saved with the correct owner, a Pending status, and no administrator assignment.

The note- and user-deletion confirmation prompts were inspected in the frontend source. The actual deletion permissions, restrictions, and database effects are covered by the automated backend tests.

## User-Deletion Rules

User deletion is deliberately restricted to protect active support records.

- Administrators cannot delete their own account.
- A user cannot be deleted while they own an unresolved ticket.
- A user cannot be deleted while they are assigned an unresolved ticket.
- Resolved tickets owned by a deleted user are removed with the responses and notes attached to those tickets.
- Assignments on surviving tickets are cleared when the assigned administrator is deleted.
- Responses and notes written by the deleted user on other users’ tickets are preserved and displayed with the author shown as “Deleted user.”
- Sessions and password-reset records belonging to the deleted user are removed.

These checks are enforced by Laravel and do not depend only on the frontend interface.

## Main Implementation Areas

### Backend

- Authentication, registration, and password-reset controllers
- Ticket, category, response, note, administrator, and user controllers
- Laravel Form Requests for validation
- Ticket authorization policy
- Ticket API resource
- Registration and ticket-response emails
- Eloquent models and relationships
- Database migrations, factories, and seeders

### Frontend

- Authentication and registration pages
- Ticket overview, creation, editing, and detail pages
- Ticket assignment and status controls
- Response and internal-note components
- Category-management pages
- Administrator user-management pages
- Shared HTTP, error-handling, routing, and collection-store services

### Tests and Documentation

- Authentication tests
- Ticket workflow and authorization tests
- Internal-note tests
- User-management tests
- Registration tests
- Requirements coverage tests
- Database relationship documentation
- Ticket-creation flowchart
- Setup, testing, and requirements documentation

## Limitations and Things to Keep in Mind

1. **MySQL Verification:** The application is configured for MySQL, but I was not able to complete a full runtime test against a local MySQL server. Automated tests and migration checks passed using SQLite.

2. **External Email Delivery:** Registration, password-reset, and ticket-response emails were checked using fake mail or Laravel’s log mail driver. A real external SMTP inbox test was not performed. SMTP credentials must be configured in `.env` for real delivery.

3. **Deletion Consequences:** Deleting a permitted user can remove resolved tickets owned by that user. Disposable test accounts should be used when demonstrating deletion.

4. **Response Email Failures:** A ticket response is saved before its notification email is sent. If an external mail transport fails, the ticket should be reloaded before attempting another response.

5. **Registration Email Failures:** Registration is handled inside a database transaction. If the confirmation email cannot be generated, account creation is rolled back so registration can be attempted again.

## Final Result

The Client Support Portal implements all 27 user stories and provides a complete workflow for registration, authentication, ticket handling, administrator responses, internal notes, category management, user administration, email notifications, and protected data access.

All 52 automated tests passed, the frontend type check and production build completed successfully, and the main application workflows were verified through the browser. The remaining external checks are a complete MySQL runtime test and real SMTP delivery.
