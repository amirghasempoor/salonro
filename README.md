# SalonRo

A beauty-salon booking platform. Customers discover salons and book appointments with experts, experts manage their availability and reservations, and salon managers run their halls, staff and services.

The backend is a Laravel API served from `backend/`. The frontend is a separate Next.js application (not part of this repository) that consumes the API.

## Roles

| Role | Guard | Description |
| --- | --- | --- |
| `user` | `web` (session) | Customers. Register/login, view profile, create/update/cancel reservations. |
| `expert` | `expert` (Sanctum token) | Works in one or more halls, handles reservations, defines working hours and portfolio. |
| `manager` | `expert` (Sanctum token) | Salon hall owner. Manages halls, hall staff, hall services and the global service catalog. |

Both experts and managers live in the `experts` table and are distinguished by a role from `spatie/laravel-permission`.

## Tech stack

- **PHP 8.3+ / Laravel 13**
- **PostgreSQL 18** (Docker)
- **Redis 8** (Docker)
- **Nginx** (Docker, used as the API gateway)
- **Laravel Sanctum** — token auth for the `expert` guard, sessions for the `web` guard
- **spatie/laravel-permission** — role management
- **ipe/smsir-php** — SMS delivery (OTP / notifications)
- **Next.js** — frontend (separate repository)

## Repository layout

```
.
├── backend/               # Laravel API
│   ├── app/
│   │   ├── Expert/        # Expert + manager controllers, requests, resources
│   │   ├── User/          # Customer controllers, requests, resources
│   │   ├── Enums/         # ReservationStates, Roles
│   │   ├── Facades/       # File, Otp, Sms, DataTable
│   │   ├── Services/      # DataTable query-builder service
│   │   └── Models/        # Eloquent models
│   ├── database/
│   │   ├── migrations/    # PostgreSQL schema
│   │   └── seeders/       # Provinces, cities, services, roles
│   └── routes/
│       ├── api.php        # Public endpoints (provinces, cities)
│       ├── web.php        # Customer endpoints (auth, profile, reservations)
│       └── expert.php     # Expert + manager endpoints
├── nginx/salonro.conf     # Nginx site config
└── docker-compose.yml     # Postgres, Redis, Nginx, Laravel PHP-FPM
```

## Getting started

### Prerequisites

- Docker + Docker Compose
- A Next.js frontend pointing at the API base URL

### Environment

Create `.env` at the repository root:

```env
PROJECT_NAME="salonro"
BACKEND_PATH="./backend"
FRONTEND_PATH=""          # Next.js app path, if mounted alongside

POSTGRES_USER="salonro_user"
POSTGRES_DB="salonro_db"
POSTGRES_PASSWORD="<your-password>"
```

Then configure `backend/.env` (start from `backend/.env.example` and set the DB credentials to match, plus `APP_KEY`):

```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=salonro_db
DB_USERNAME=salonro_user
DB_PASSWORD=<your-password>

SMSIR_API_KEY=          # from your SmsIr panel
SMSIR_LINE_NUMBER=
```

### Run with Docker

```bash
docker compose up -d
```

This starts `salonro-db` (PostgreSQL, host port `8093`), `salonro-redis`, `salonro-nginx` (host port `8002`) and `salonro-laravel` (PHP-FPM, host port `9000`). Nginx proxies the API at `http://localhost:8002`.

### Migrate and seed

Inside the `salonro-laravel` container (or locally in `backend/`):

```bash
composer install
php artisan migrate
php artisan db:seed
```

The seeder loads provinces, cities, the global service catalog and the expert-guard roles (`admin`, `expert`, `manager`).

### Without Docker

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Authentication

The API uses two authentication systems:

| Guard | Driver | Auth flow | Token/session |
| --- | --- | --- | --- |
| `web` | session | `/auth/*` on the customer routes | PHP session cookie + CSRF token |
| `expert` | sanctum | `/expert/auth/*` | Bearer token (`EXPERT_TOKEN`), valid 1 week |

Both guards support register, OTP login and password login. The `expert` guard also supports OTP sign-up (a missing expert is created on first OTP login).

## API overview

### Public

| Method | Route | Description |
| --- | --- | --- |
| GET | `/api/provinces/list` | List provinces |
| GET | `/api/cities/{province_id}` | List cities of a province |

### Customer (`web` guard)

| Method | Route | Description |
| --- | --- | --- |
| POST | `/auth/register` | Register a customer |
| POST | `/auth/send_otp` | Send OTP |
| POST | `/auth/login_with_otp` | Login with OTP |
| POST | `/auth/login_with_password` | Login with password |
| POST | `/auth/logout` | Logout |
| GET | `/profile/info` | Profile info |
| POST | `/profile/edit` | Update profile (KYC) |
| POST | `/profile/change_password` | Change password |
| GET/POST | `/reservations` | List / create reservations |
| GET/POST/DELETE | `/reservations/{reservation}` | Show / update / delete a reservation |

### Expert & manager (`expert` guard, prefix `/expert`)

| Method | Route | Description |
| --- | --- | --- |
| POST | `/expert/auth/register` | Register as expert or manager |
| POST | `/expert/auth/send_otp` | Send OTP |
| POST | `/expert/auth/login_with_otp` | Login with OTP |
| POST | `/expert/auth/login_with_password` | Login with password |
| POST | `/expert/auth/logout` | Logout |
| GET | `/expert/profile/info` | Profile info |
| POST | `/expert/profile/update` | Update profile |
| POST | `/expert/profile/change_password` | Change password |
| POST | `/expert/profile/change_avatar` | Change avatar |
| POST | `/expert/profile/upload_portfolio` | Upload portfolio images |
| POST | `/expert/profile/define_working_hour` | Define weekly working hours |
| POST | `/expert/profile/define_role` | Switch between expert / manager |
| GET/POST | `/expert/halls` | List / create own halls |
| GET/POST/DELETE | `/expert/halls/{hall}` | Show / update / delete a hall |
| GET | `/expert/halls/services/{hall}` | Services offered by a hall |
| GET/POST | `/expert/reservations` | List / create reservations |
| GET/POST/DELETE | `/expert/reservations/{reservation}` | Show / update / delete a reservation |
| GET/POST | `/expert/service_categories` | Manage the global service catalog |
| GET/POST | `/expert/staff/{hall}` | List / add staff to a hall |
| GET/POST/DELETE | `/expert/staff/{hall}/{expert}` | Show / update / delete a staff expert |
| GET/POST | `/expert/services/{hall}` | Manage a hall's services and prices |
| GET/POST/DELETE | `/expert/services/{hall}/{hallService}` | Show / update / delete a hall service |

List endpoints accept `start`, `size`, and JSON-encoded `filters` / `sorting` query parameters consumed by the `DataTable` service.

## SMS

OTP codes and notifications are sent through the SmsIr SDK (`ipe/smsir-php`). Every send is logged to `sms_histories` with the API status, pack ID, message IDs and cost. Set `SMSIR_API_KEY` and `SMSIR_LINE_NUMBER` in `backend/.env`.

## Development

```bash
# Run tests
php artisan test

# Code style
vendor/bin/pint
```

## Status

The core flows are implemented but the project is under active development. Known gaps are tracked as issues in the codebase (authorization checks, reservation conflict validation, and a cancel-flow implementation).
