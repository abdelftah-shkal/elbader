# Elbader

Elbader is a web application with a Laravel backend. This repository contains the backend application in the `backend/` directory.

## Tech stack

- PHP (Laravel)
- Composer
- Node.js / npm (for frontend tooling and assets inside `backend`)

## Repository layout

- backend/ — Laravel application and all backend code

## Prerequisites

- PHP 8.0+ (or as required by the Laravel version used)
- Composer
- Node.js and npm (or pnpm/yarn)
- A database (MySQL, PostgreSQL, SQLite, etc.)

## Setup (backend)

1. Clone the repository

   git clone https://github.com/abdelftah-shkal/elbader.git

2. Install backend dependencies

   cd elbader/backend
   composer install
   npm install

3. Environment

   Copy the example environment and update values (DB, APP_URL, etc.):

   cp .env.example .env
   php artisan key:generate

   Update the database settings in `.env` to match your local database.

4. Database

   Run migrations (and optionally seeders):

   php artisan migrate
   # or to refresh and seed
   # php artisan migrate:fresh --seed

5. Build assets (for development)

   npm run dev

6. Serve the application

   php artisan serve

   The application will be available at the address shown by the command (usually http://127.0.0.1:8000).

## Postman

A Postman collection is included in `backend/postman/` (if present). Import it to test API endpoints.

## Running tests

From the `backend/` directory run:

    vendor/bin/phpunit

## Contributing

Contributions are welcome. Please open issues or pull requests describing your changes.

## License

This project does not include an explicit license file. If you intend to open-source it, add a LICENSE file (for example MIT).

---

If you'd like, I can also:
- Improve the backend/README.md to be specific to this project (it currently contains a generic Laravel README).
- Add a LICENSE file.
- Add setup scripts or Docker configuration to simplify local setup.
