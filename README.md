# Travel Requests API

A Laravel-based API for managing corporate travel requests with role-based access control.

## Features

- **Authentication**: API token-based authentication using Laravel Sanctum
- **Role-based Access Control**: Different permissions for employees, managers, and administrators
- **Travel Request Management**: Create, read, update, and delete travel requests
- **Status Management**: Track travel request status (pending, approved, rejected, cancelled)
- **RESTful API**: Clean and consistent API endpoints

## Tech Stack

- **Laravel 11.x**: Modern PHP framework
- **MySQL 8.4**: Database
- **Redis**: Caching and sessions
- **Laravel Sanctum**: API authentication
- **Laravel Sail**: Docker development environment

## Getting Started

### Prerequisites

- Docker Desktop
- Git

### Installation

1. Clone the repository:
```bash
git clone git@github.com:gustavobotti/travel-requests-api.git
cd travel-requests-api
```

2. Copy the environment file:
```bash
copy .env.example .env
```

3. Start the Docker containers:
```bash
./vendor/bin/sail up -d
```

4. Install dependencies (if not already installed):
```bash
./vendor/bin/sail composer install
```

5. Generate application key:
```bash
./vendor/bin/sail artisan key:generate
```

6. Run migrations:
```bash
./vendor/bin/sail artisan migrate
```

7. (Optional) Seed the database with sample data:
```bash
./vendor/bin/sail artisan db:seed
```

## API Documentation

### Authentication

All API endpoints require authentication using Bearer tokens.

### Endpoints

- `POST /api/register` - Register a new user
- `POST /api/login` - Login and receive authentication token
- `GET /api/travel-requests` - List all travel requests
- `POST /api/travel-requests` - Create a new travel request
- `GET /api/travel-requests/{id}` - Get a specific travel request
- `PUT /api/travel-requests/{id}` - Update a travel request
- `DELETE /api/travel-requests/{id}` - Delete a travel request

## Development

This project uses Laravel Sail for development. Common commands:

```bash
# Start containers
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Run tests
./vendor/bin/sail test

# Access MySQL
./vendor/bin/sail mysql

# View logs
./vendor/bin/sail logs
```

## License

This project is open-sourced software licensed under the MIT license.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
