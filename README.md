# IT Help Desk & Ticketing Management System

Full-stack internship project for an IT Help Desk and Ticketing Management System.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Frontend | React, Vite, Tailwind CSS |
| Backend | PHP Laravel 11 |
| Database | MySQL / MariaDB |
| Authentication | JWT with `tymon/jwt-auth` |
| Roles | Admin, Manager, Agent, Employee |

## Completed Up To Week 5

- Environment setup and project structure
- Authentication and role-based access
- Admin-created user accounts
- First-login forced password change for admin-created users
- Ticket CRUD with categories, priorities, statuses and assignment workflow
- Ticket comments and replies
- Internal notes visible only to Admin, Manager and Agent roles
- Ticket history/timeline
- Secure ticket attachment upload/download/delete
- Notification backend, notification bell, unread count and notification center
- Near-real-time notification polling every 30 seconds
- Dashboard analytics with real database counts
- Basic charts for tickets by status, category and priority
- Migrations, SQL script and seed data updated for Week 5

## Important Account Rule

There is no public registration page. Users do not self-register.

Only an Admin can create user accounts from the Admin Create User page. New admin-created users receive a temporary password and must change it on first login before they can access tickets, dashboard, notifications or admin pages.

## Folder Structure

```text
IT-Help-Desk-main/
  backend/      Laravel 11 API
  frontend/     React + Vite app
  databaseCode.sql
  seed_data.sql
```

## Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan serve
```

Configure these `.env` values for MariaDB/MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ITHelpDesk
DB_USERNAME=root
DB_PASSWORD=
JWT_SECRET=generated-by-php-artisan-jwt-secret
```

Attachments are stored through Laravel's local storage disk and downloaded through authenticated API routes. A public storage symlink is not required for downloads, but `php artisan storage:link` is safe if you also use public storage elsewhere.

## Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

The frontend defaults to `http://localhost:8000/api` if `VITE_API_URL` is not set.

Optional `.env`:

```env
VITE_API_URL=http://localhost:8000/api
```

## Demo Accounts

All demo accounts use:

```text
Admin@12345
```

| Role | Email |
| --- | --- |
| Admin | admin@company.com |
| Manager | sarah.manager@company.com |
| Agent | john.agent@company.com |
| Agent | lisa.agent@company.com |
| Employee | mike@company.com |
| Employee | anna@company.com |

Seeded demo users have `MustChangePassword = false` for easier demos. Users created from the Admin Create User page are created with `MustChangePassword = true`.

## Main API Endpoints

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `POST /api/change-password`
- `POST /api/admin/users`
- `GET /api/admin/roles`
- `GET /api/dashboard/stats`
- `GET /api/tickets`
- `POST /api/tickets`
- `GET /api/tickets/{ticket}`
- `PUT /api/tickets/{ticket}`
- `DELETE /api/tickets/{ticket}`
- `GET /api/tickets/{ticket}/comments`
- `POST /api/tickets/{ticket}/comments`
- `GET /api/tickets/{ticket}/attachments`
- `POST /api/tickets/{ticket}/attachments`
- `GET /api/attachments/{attachment}/download`
- `GET /api/tickets/{ticket}/history`
- `GET /api/notifications`
- `GET /api/notifications/unread-count`
- `PUT /api/notifications/{notification}/read`
- `PUT /api/notifications/read-all`

## Notifications

Notifications are implemented with polling rather than WebSockets. The frontend fetches the unread count every 30 seconds and refreshes notification lists when the bell or notification center is opened.

Notifications are created for assignment, status changes, priority changes, comments, internal notes and attachments. The actor who performed the action is not notified.

## Database Scripts

Use Laravel migrations for normal setup:

```bash
php artisan migrate:fresh --seed
```

The root SQL files are also updated:

- `databaseCode.sql` creates the database and all Week 5 tables/columns.
- `seed_data.sql` inserts demo roles, lookup values, users, sample tickets, comments, history and notifications.
