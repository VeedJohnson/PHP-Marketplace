# Ad listing Application - Source Code Documentation

## Overview
An ad listing web application built with Symfony 6.4, enabling users to post and manage advertisements with advanced features including user roles, image management, and location-based filtering.

## Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0
- Symfony CLI

## Installation
1. Unzip the project

2. Install dependencies
```bash
composer install
```

3. Configure environment variables
```bash
cp .env .env.local
```
Edit `.env` with your database credentials:
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/marketplace?serverVersion=8.0.32&charset=utf8mb4"
MAILER_DSN=smtp://user:pass@smtp.example.com:25
```

4. Create database and run migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Create initial super admin
```bash
php bin/console app:create-super-admin
```

6. Load fixtures (optional)
```bash
php bin/console doctrine:fixtures:load
```

## Project Structure

### Controllers
- `SecurityController`: Handles authentication
- `RegistrationController`: User registration and email verification
- `AdvertisementController`: Advertisement CRUD operations
- `AdminController`: Administrative functions
- `ProfileController`: User profile management

### Entities
- `User`: User management with roles and ban system
- `Advertisement`: Core advertisement functionality
- `Category`: Advertisement categorization
- `AdvertisementImage`: Image management

### Services
- `ImageUploader`: Handles file uploads
- `LocationProvider`: Manages location data

### Forms
- `AdvertisementType`: Advertisement creation/editing
- `RegistrationFormType`: User registration
- `ResetPasswordFormType`: Password reset

## Features

### User Management
- Registration with email verification
- Role-based access control
    - SUPER_ADMIN
    - ADMIN
    - MODERATOR
    - USER
- User banning system
- Password reset functionality

### Advertisement Management
- Create, read, update, delete advertisements
- Multiple image upload (max 5 images)
- Category selection
- Location-based filtering

### Search & Filtering
- Title search
- Category filtering
- Location filtering
- Price range filtering
- Pagination

### Administrative Features
- User management
- Content moderation
- Category management
- Ban system management

## Security
- Form CSRF protection
- Password hashing
- Role-based access control
- Custom security voter implementation
- Email verification
- Ban system

## Testing
```bash
# Run tests
php bin/phpunit

# Run specific test suite
php bin/phpunit --testsuite=Unit
```

## Development

### Adding New Features
1. Create necessary entity/entities
```bash
php bin/console make:entity
```

2. Create controller
```bash
php bin/console make:controller
```

3. Generate migration
```bash
php bin/console make:migration
```

4. Create form type if needed
```bash
php bin/console make:form
```

### Coding Standards
- Follow PSR-12
- Use PHP 8.1 features
- Document methods and classes
- Use type hints
- Follow Symfony best practices

## Deployment
1. Set environment to production
```bash
composer install --no-dev --optimize-autoloader
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

2. Run migrations
```bash
php bin/console doctrine:migrations:migrate --env=prod
```

## Known Issues
- Session handling in tests requires specific configuration
- Image deletion requires manual cleanup of files
- Location data is currently static

## Future Improvements
- Implement real-time notifications
- Add image optimization
- Integrate cloud storage for images
- Add message system between users
- Enhance search with Elasticsearch

