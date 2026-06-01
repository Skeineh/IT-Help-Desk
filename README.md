# IT Help Desk & Ticketing System

Internship project developed for **Integrated Digital Systems (IDS)**.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | React + Vite + Tailwind CSS v3 |
| Backend | Laravel 11 |
| Database | MariaDB |
| Auth | JWT (`tymon/jwt-auth`) |

## Folder Structure

```
IThelpdesk/
├── backend/      # Laravel 11 API
└── frontend/     # React + Vite app
```

## Setup

### Backend

```bash
cd backend
composer install
cp .env.example .env
# Configure DB credentials in .env (MariaDB)
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend runs at `http://localhost:5173`, backend at `http://localhost:8000`.

## Seeded Users

6 users are seeded via `DatabaseSeeder`. All share the password:

```
Admin@12345
```

## Status

### Week 2 — Complete
- Laravel 11 backend with JWT authentication
- React frontend with login page
- Auth flow: login → JWT token → protected routes
