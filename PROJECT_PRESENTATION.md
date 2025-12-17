# Eponuda - Laravel Scraper Project Presentation

## 1. Project Overview

- **Purpose**: Web scraping application that extracts television product data from shoptok.si and displays it through a modern Vue.js interface
- **Test Assignment Goal**: Demonstrate clean architecture principles, repository pattern implementation, and full-stack development capabilities
- **Core Functionality**:
  - Scrapes TV categories and products from shoptok.si
  - Stores data in database with category relationships
  - Provides RESTful API endpoints for frontend consumption
  - Displays products in paginated grid layout with category filtering

## 2. Tech Stack

- **Backend**:
  - PHP 8.2
  - Laravel 12.0
  - MySQL 8.4 (via Docker)
  - SQLite (for development/testing)
  
- **Frontend**:
  - Vue.js 3.5.25 (Composition API)
  - Tailwind CSS 4.0
  - Vite 7.0.7 (build tool)
  - Axios 1.13.2 (HTTP client)

- **Scraping**:
  - Spatie Browsershot 5.1 (headless Chrome via Puppeteer)
  - Symfony DOM Crawler 8.0 (HTML parsing)

- **Development Tools**:
  - Laravel Sail (Docker orchestration)
  - Laravel Pint (code formatting)
  - PHPUnit 11.5.3 (testing)
  - Laravel Pail (log viewer)

- **Infrastructure**:
  - Docker & Docker Compose
  - Redis (caching)
  - Custom Dockerfile with Chromium support

## 3. Project Structure

```
app/
├── Console/Commands/          # Artisan commands for scraping
│   ├── ScrapeTelevisionCategories.php
│   └── ScrapeTelevisions.php
├── Http/
│   ├── Controllers/Api/       # Thin API controllers
│   │   ├── TelevisionController.php
│   │   └── TvCategoryController.php
│   ├── Requests/              # Form request validation
│   │   ├── TelevisionIndexRequest.php
│   │   └── TvCategoryProductsRequest.php
│   └── Resources/             # API resource transformers
│       ├── TelevisionResource.php
│       └── TvCategoryResource.php
├── Models/                     # Eloquent models
│   ├── Television.php
│   ├── TvCategory.php
│   └── User.php
├── Repositories/               # Repository pattern implementation
│   ├── TelevisionRepository.php
│   ├── TelevisionRepositoryInterface.php
│   ├── TvCategoryRepository.php
│   └── TvCategoryRepositoryInterface.php
└── Services/                   # Business logic layer
    ├── TelevisionScraperService.php
    ├── TelevisionCategoryScraperService.php
    └── UrlValidator.php

resources/js/
├── api/                        # API client modules
│   ├── televisions.js
│   └── categories.js
├── components/                 # Reusable Vue components
│   ├── TelevisionCard.vue
│   └── Pagination.vue
├── composables/                # Vue composables (reusable logic)
│   ├── useTelevisions.js
│   └── useCategories.js
└── pages/                      # Vue page components
    ├── TelevisionsPage.vue
    └── TvReceiverPage.vue
```

**Structure Rationale**:
- Clear separation between HTTP layer, business logic, and data access
- Repository interfaces enable dependency injection and testability
- Frontend follows Vue 3 Composition API best practices with composables
- API resources provide consistent JSON transformation

## 4. Architecture Decisions

- **Clean Architecture Layering**:
  - Controllers → Services → Repositories → Models → Database
  - No database calls in controllers or services
  - All data access through repository interfaces

- **Repository Pattern**:
  - Interfaces define contracts (`TelevisionRepositoryInterface`, `TvCategoryRepositoryInterface`)
  - Implementations handle Eloquent queries
  - Registered in `AppServiceProvider` for dependency injection
  - Enables easy mocking for testing

- **Service Layer**:
  - `TelevisionScraperService`: Handles product scraping logic
  - `TelevisionCategoryScraperService`: Extends scraper service, handles category scraping
  - `UrlValidator`: Security utility to prevent SSRF attacks
  - Services use repositories, never Eloquent directly

- **Inheritance Strategy**:
  - `TelevisionCategoryScraperService` extends `TelevisionScraperService`
  - Reuses product scraping logic while adding category-specific functionality
  - Demonstrates DRY principle

- **Request Validation**:
  - Form Request classes (`TelevisionIndexRequest`, `TvCategoryProductsRequest`)
  - Centralized validation rules
  - Automatic 422 responses on validation failure

- **API Resources**:
  - `TelevisionResource` and `TvCategoryResource` transform models to JSON
  - Conditional loading of relationships
  - Consistent API response format

## 5. Backend Logic

- **Models**:
  - `Television`: Product data (name, price, image, specs, external_id, tv_category_id)
  - `TvCategory`: Hierarchical categories (name, slug, url, parent_id)
  - Relationships: `Television` belongsTo `TvCategory`, `TvCategory` hasMany `Television`

- **Controllers**:
  - `TelevisionController::index()`: Paginated product listing with optional category filter
  - `TvCategoryController::index()`: List all categories
  - `TvCategoryController::products()`: Products filtered by category ID
  - Controllers only handle HTTP concerns (request/response, validation)
  - Return API resources, never raw models

- **Repositories**:
  - `TelevisionRepository`: `paginate()`, `findByExternalId()`, `create()`, `update()`, `updateOrCreate()`
  - `TvCategoryRepository`: `all()`, `findBySlug()`, `findByUrl()`, `getSubcategories()`, `exists()`
  - All database queries encapsulated here
  - Type hints with return types (`LengthAwarePaginator`, `Collection`, `?Television`)

- **Services**:
  - `TelevisionScraperService::scrapeForCategory()`: Main scraping logic
    - Fetches HTML via Browsershot (headless Chrome) with fallback to cURL
    - Parses DOM using Symfony Crawler
    - Extracts product data (name, price, image, link, specs)
    - Validates prices (50-5000 EUR range, rejects years/model numbers)
    - Saves via repository using `updateOrCreate()` with `external_id`
  - `TelevisionCategoryScraperService::scrapeCategories()`: Category scraping
    - Extracts categories from listing page
    - Creates/updates categories
    - Scrapes products for each category
  - `UrlValidator::validate()`: SSRF protection
    - Validates URL format
    - Blocks private/internal IP addresses
    - Only allows HTTP/HTTPS protocols

- **Validation & Error Handling**:
  - Form Request validation with rules (page, per_page, category_id)
  - Try-catch blocks in services with logging
  - HTTP status codes: 404 for not found, 422 for validation errors
  - Rate limiting: 60 requests/minute per IP/user

## 6. Data Flow

**Scraping Flow**:
1. Artisan command `scrape:television-categories` executed
2. Command injects `TelevisionCategoryScraperService`
3. Service calls `fetchHtml()` with URL validation
4. Browsershot renders page (executes JavaScript)
5. DOM Crawler parses HTML
6. Category nodes extracted, category data saved via `TvCategoryRepository`
7. For each category, `scrapeForCategory()` called
8. Product nodes extracted, data transformed
9. Products saved via `TelevisionRepository::updateOrCreate()` (prevents duplicates using `external_id`)

**API Request Flow**:
1. HTTP request to `/api/televisions?page=1&category_id=5`
2. Route middleware applies rate limiting
3. `TelevisionIndexRequest` validates query parameters
4. `TelevisionController::index()` receives validated request
5. Controller calls `TelevisionRepository::paginate()`
6. Repository builds Eloquent query with filters
7. Paginated results returned
8. `TelevisionResource` transforms each model to JSON
9. Response: `{ data: [...], meta: { pagination }, links: {...} }`

**Frontend Flow**:
1. Vue component mounts (`TelevisionsPage.vue`)
2. Calls `useTelevisions()` composable
3. Composable calls `televisionApi.getTelevisions()`
4. Axios makes HTTP request to Laravel API
5. Response data transformed and stored in reactive refs
6. Component renders grid of `TelevisionCard` components
7. Pagination component handles page changes
8. On page change, new API request triggered

## 7. Code Quality

- **PSR-12 Compliance**: Code formatted with Laravel Pint
- **Type Hints**: All methods have parameter and return type hints
- **Readonly Properties**: Constructor properties marked `readonly` where appropriate
- **Dependency Injection**: All dependencies injected via constructor
- **Interface-Based Design**: Repositories use interfaces, enabling testability
- **Single Responsibility**: Each class has one clear purpose
- **DRY Principle**: Shared logic extracted (e.g., `makeAbsoluteUrl()`)
- **Security**: URL validation prevents SSRF, input sanitization prevents XSS
- **Error Handling**: Try-catch blocks with logging, graceful degradation
- **Documentation**: PHPDoc comments on public methods

**Frontend Quality**:
- Composition API for better code organization
- Composables for reusable logic (`useTelevisions`, `useCategories`)
- Component-based architecture
- Reactive state management
- Error and loading states handled

## 8. Trade-offs & Decisions

- **Browsershot vs cURL**: Primary use of Browsershot (headless Chrome) to bypass bot detection, with cURL fallback for non-Chrome environments. Trade-off: More resource-intensive but more reliable scraping.

- **Repository Pattern**: Added abstraction layer increases code but improves testability and maintainability. Appropriate for test assignment to demonstrate architectural knowledge.

- **Service Inheritance**: `TelevisionCategoryScraperService` extends `TelevisionScraperService` to reuse code. Trade-off: Inheritance coupling vs code duplication. Chosen for DRY principle.

- **External ID Strategy**: Uses `external_id` field with `updateOrCreate()` to prevent duplicates. Extracts from URL or generates MD5 hash. Trade-off: Simple approach vs more complex deduplication logic.

- **Price Validation**: Hard-coded range (50-5000 EUR) and heuristic checks (reject years 2020-2030). Trade-off: May reject valid edge cases but prevents common scraping errors.

- **SQLite for Development**: Uses SQLite in development, MySQL in production. Trade-off: Simpler setup vs production parity.

- **Vue 3 Composition API**: Modern approach but requires more setup than Options API. Chosen to demonstrate current best practices.

- **No Authentication**: Public API endpoints without authentication. Appropriate for test assignment scope, but would need auth in production.

## 9. Possible Improvements

- **Testing**:
  - Unit tests for services and repositories
  - Feature tests for API endpoints
  - Integration tests for scraping logic
  - Frontend component tests

- **Performance**:
  - Caching of API responses (Redis)
  - Queue jobs for scraping (currently synchronous)
  - Database query optimization (eager loading relationships)
  - Image optimization and CDN

- **Scalability**:
  - Horizontal scaling with queue workers
  - Database read replicas
  - API response caching
  - Pagination optimization for large datasets

- **Features**:
  - Search functionality
  - Price comparison across multiple sources
  - Product detail pages
  - User favorites/wishlist
  - Email alerts for price drops

- **Code**:
  - DTOs for data transfer between layers
  - Event-driven architecture for scraping events
  - More comprehensive error handling
  - API versioning

- **Security**:
  - Authentication and authorization
  - API rate limiting per user
  - CSRF protection for web routes
  - Input sanitization improvements

- **Monitoring**:
  - Logging improvements (structured logging)
  - Error tracking (Sentry)
  - Performance monitoring
  - Scraping success rate metrics

## 10. Summary

- **Clean Architecture**: Successfully implements layered architecture with clear separation of concerns
- **Repository Pattern**: Proper use of interfaces and dependency injection demonstrates SOLID principles
- **Full-Stack**: Complete solution from scraping to API to modern Vue.js frontend
- **Production-Ready Patterns**: Uses Laravel best practices (Form Requests, Resources, Service Providers)
- **Security Considerations**: URL validation, input sanitization, rate limiting
- **Maintainable Code**: Well-structured, type-hinted, documented codebase
- **Test Assignment Scope**: Demonstrates architectural knowledge, PHP/Laravel expertise, and frontend capabilities within reasonable scope

The project successfully demonstrates the ability to build a maintainable, scalable Laravel application following industry best practices while delivering a functional web scraping and display system.

