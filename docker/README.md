# Docker Setup for Tourstack

This directory contains Docker configuration files for running the Tourstack application with Nginx, PHP-FPM, and MySQL.

## Services

| Service    | Container Name       | Port            |
| ---------- | -------------------- | --------------- |
| Nginx      | tourstack-nginx      | 80              |
| PHP-FPM    | tourstack-php        | 9000 (internal) |
| MySQL      | tourstack-mysql      | 3306            |
| phpMyAdmin | tourstack-phpmyadmin | 8080            |

## Quick Start

### 1. Build and Start Containers

```bash
docker-compose up -d --build
```

### 2. Access the Application

- **Website**: http://localhost
- **phpMyAdmin**: http://localhost:8080

### 3. Import Database

If you have an existing database dump, you can import it:

```bash
docker exec -i tourstack-mysql mysql -uroot -prootpassword tourstack < your_database.sql
```

## Database Credentials

| Setting       | Value              |
| ------------- | ------------------ |
| Host          | mysql              |
| Database      | tourstack          |
| Root Password | rootpassword       |
| User          | tourstack_user     |
| User Password | tourstack_password |

> **Note**: For production, change these credentials in `docker-compose.yml`

## Useful Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Rebuild PHP container
docker-compose up -d --build php

# Access MySQL CLI
docker exec -it tourstack-mysql mysql -uroot -prootpassword

# Access PHP container
docker exec -it tourstack-php bash
```

## File Structure

```
docker/
├── nginx/
│   └── default.conf    # Nginx configuration
├── php/
│   ├── Dockerfile      # PHP-FPM image configuration
│   └── php.ini         # PHP configuration
└── README.md           # This file
```

## Production Recommendations

1. Change all default passwords
2. Enable opcache in `php/php.ini`
3. Set `display_errors = Off` in `php/php.ini`
4. Use a `.env` file for environment variables
5. Add SSL/TLS with Let's Encrypt
