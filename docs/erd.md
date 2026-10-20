# Client Support Portal ERD

```mermaid
erDiagram
    USERS ||--o{ TICKETS : creates
    USERS o|--o{ TICKETS : assigned_to
    USERS ||--o{ RESPONSES : writes
    USERS ||--o{ NOTES : writes

    TICKETS ||--o{ RESPONSES : has
    TICKETS ||--o{ NOTES : has

    TICKETS ||--o{ CATEGORY_TICKET : categorized_with
    CATEGORIES ||--o{ CATEGORY_TICKET : used_by

    USERS {
        bigint id PK
        string first_name
        string last_name
        string email UK
        string phone_number "nullable"
        string password
        boolean admin "default false"
        timestamp created_at
        timestamp updated_at
    }

    TICKETS {
        bigint id PK
        bigint user_id FK
        bigint assigned_to FK "nullable"
        string title
        text description
        string status "default pending"
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK
        string title UK
        timestamp created_at
        timestamp updated_at
    }

    CATEGORY_TICKET {
        bigint ticket_id PK,FK
        bigint category_id PK,FK
    }

    RESPONSES {
        bigint id PK
        bigint ticket_id FK
        bigint user_id FK
        text body
        timestamp created_at
        timestamp updated_at
    }

    NOTES {
        bigint id PK
        bigint ticket_id FK
        bigint user_id FK
        text body
        timestamp created_at
        timestamp updated_at
    }
```

## Decisions

- `users.admin = false` means normal user.
- `users.admin = true` means administrator.
- `tickets.user_id` is the required creator.
- `tickets.assigned_to` is nullable because tickets may be unassigned.
- `tickets.status` uses `pending`, `in_progress`, or `resolved`.
- Tickets and categories have a many-to-many relationship through `category_ticket`.
- Responses and notes both belong to a ticket and an authoring user.
- Only administrators may later assign tickets, change status, create responses, and create/view notes. Those are backend business/authorization rules rather than foreign-key rules.
