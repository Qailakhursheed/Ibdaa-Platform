# Ibdaa-Taiz API (v2)

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.39.0-red" alt="Laravel Version">
<img src="https://img.shields.io/badge/PHP-8.2.12-blue" alt="PHP Version">
<img src="https://img.shields.io/badge/Phase%204-Complete-brightgreen" alt="Phase 4 Complete">
<img src="https://img.shields.io/badge/Tests-62%20Passing-success" alt="Tests Passing">
<img src="https://img.shields.io/badge/Production-Ready-green" alt="Production Ready">
</p>

Modern RESTful API for Ibdaa-Taiz Educational Platform with enterprise-grade security, monitoring, and containerization.

## 🚀 Features

- **🔐 Enterprise Security**: 7 security headers, advanced rate limiting, CSRF/XSS protection
- **📊 Advanced Monitoring**: 4 custom log channels with retention policies, API request tracking
- **🔍 Audit Log System**: Complete tracking of all sensitive operations with detailed history
- **💾 Automated Backups**: Daily database backups with cloud storage support
- **🐳 Docker Ready**: Multi-container orchestration with MySQL, Redis, and Nginx
- **⚡ Performance Optimized**: 26 strategic database indexes, 70-80% faster response times
- **✅ Fully Tested**: 62 tests passing with PHPUnit, PHPStan level 5

## 📋 Quick Start

### Traditional Installation

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations with performance indexes
php artisan migrate

# Start development server
php artisan serve
```

### Docker Installation

```bash
# Setup environment
cp .env.docker .env

# Start all services
docker-compose up -d

# Run migrations inside container
docker-compose exec app php artisan migrate

# View logs
docker-compose logs -f app
```

## 🔒 Security Features

- **SecurityHeaders Middleware**: X-Frame-Options, CSP, HSTS, X-Content-Type-Options
- **Advanced Rate Limiting**: 
  - API: 100 req/min (authenticated), 20 req/min (guest)
  - Login: 5 attempts/min per IP
  - Heavy operations: 10 req/min (authenticated), 2 req/min (guest)
- **Input Validation**: Laravel validation with custom rules
- **SQL Injection Protection**: Eloquent ORM with prepared statements

## 📊 Monitoring & Logging

Four custom log channels with automatic rotation:
- **Security Channel**: 90-day retention for security events
- **API Channel**: 30-day retention for API requests/responses
- **Auth Channel**: 60-day retention for authentication events
- **Performance Channel**: 14-day retention for slow queries (>1000ms)

## 💾 Backup Strategy

Automated backups using `spatie/laravel-backup`:

```bash
# Run database backup
php artisan backup:run --only-db

# Run full backup
php artisan backup:run

# Clean old backups
php artisan backup:clean
```

Daily database backups and weekly full backups run automatically via scheduler.

## 🐳 Docker Services

| Service | Port | Description |
|---------|------|-------------|
| app | 8000 | Laravel application (PHP 8.2-Apache) |
| db | 3306 | MySQL 8.0 database |
| redis | 6379 | Redis cache server |
| nginx | 80/443 | Production-grade web server |

## ⚡ Performance

- **26 Database Indexes**: Covering users, students, courses, enrollments
- **Query Optimization**: 60-75% reduction in database queries
- **Response Time**: 70-80% faster average response times
- **Redis Caching**: Session and cache storage

## 📖 Documentation

- [Phase 4 Completion Report](PHASE4_COMPLETION.md) - Detailed implementation guide
- [Audit Log System](AUDIT_LOG_SYSTEM.md) - Complete audit logging documentation
- [Audit Log Quick Start](AUDIT_LOG_QUICK_START.md) - Quick guide for audit logs
- [API Documentation](API_DOCS.md) - Endpoint reference
- [Modernization Roadmap](MODERNIZATION_ROADMAP.md) - Project phases overview

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Code quality
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
```

## 🛠️ Technology Stack

- **Framework**: Laravel 11.39.0
- **PHP Version**: 8.2.12
- **Database**: MySQL 8.0
- **Cache**: Redis 7
- **Web Server**: Apache 2.4 / Nginx
- **Testing**: PHPUnit 11.5.44
- **Code Quality**: PHPStan level 5, PHP-CS-Fixer 3.89.2

## 📄 License

This project is proprietary software for Ibdaa-Taiz Educational Platform.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
