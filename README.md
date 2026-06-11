# Corporate Travel API

A RESTful API built with Laravel for managing corporate travel requests.

## Technologies

- **Framework:** Laravel 12.x
- **PHP:** 8.2+
- **Database:** MySQL 8.4
- **Authentication:** Laravel Sanctum
- **Development Environment:** Laravel Sail (Docker)
- **Mail Testing:** Mailpit
- **Testing:** PHPUnit

## Prerequisites

- Docker
- Git

## Installation & Setup

### 1. Clone the repository

```bash
git clone git@github.com:gustavobotti/travel-requests-api.git
cd travel-requests-api
```

### 2. Install dependencies

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 composer install --ignore-platform-reqs
```

### 3. Environment configuration

```bash
cp .env.example .env
```

### 4. Configure the Sail alias (optional but recommended)

```bash
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'

or simply use `vendor\bin\sail` instead of `sail` in all commands.
```

### 5. Start the application

```bash
sail up -d
```

### 6. Generate the application key

```bash
sail artisan key:generate
```

### 7. Run migrations and seed the database

```bash
sail artisan migrate:fresh --seed
```

### 8. Start the queue worker (required for email notifications)

```bash
sail artisan queue:work
```

Keep this running in a separate terminal window to process email notifications.

### 9. Run the tests (optional)

```bash
sail test
```

## Accessing the Application

Once the containers are running, you can access:

- **API:** http://localhost
- **Mailpit (Email Testing):** http://localhost:8026
- **MySQL Database:** localhost:3307
  - Database: `laravel`
  - Username: `sail`
  - Password: `password`

## Testing Real Email Notifications

To test the email notification system with real API calls:

1. Make sure the queue worker is running (see step 8 above)
2. Run the test script:

```bash
bash test-notification.sh
```

This script will:
- Register/log in two test users (requester and approver)
- Create 2 travel requests
- Change their statuses (approve, cancel, then cancel the approved one)
- Trigger email notifications to Mailpit

Check the emails at: **http://localhost:8026**

## Stopping the Application

```bash
sail down
```

To stop and remove volumes:

```bash
sail down -v
```

## Additional Commands

### Run artisan commands
```bash
sail artisan [command]
```

### Run composer commands
```bash
sail composer [command]
```

### Access the container shell
```bash
sail shell
```

### View logs
```bash
sail logs -f
```

### Re-seed the database
```bash
sail artisan migrate:fresh --seed
```

---
