# Eponuda - Laravel Scraper Project

A complete Laravel project implementing clean architecture principles with Repository Pattern, Service Layer, and VueJS frontend. This project scrapes product data from shoptok.si and displays it through a modern Vue.js interface.

## Architecture

This project follows **Clean Architecture** principles with strict layering:

```
Controller → Service → Repository → Model → Database
```

### Key Principles:
- **No DB calls in controllers or services** - All database operations go through repositories
- **No business logic in controllers** - Controllers only handle HTTP requests/responses
- **Service layer handles business logic** - Scraping, data transformation, etc.
- **Repository pattern for data access** - Interfaces define contracts, implementations handle DB

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── DTO/                      # Data Transfer Objects (optional)
├── Http/
│   ├── Controllers/Api/      # API controllers
│   └── Resources/            # API Resources for JSON transformation
├── Models/                   # Eloquent models
├── Repositories/            # Repository interfaces and implementations
└── Services/                 # Business logic services

resources/
└── js/
    ├── api/                  # API client modules
    ├── components/          # Reusable Vue components
    ├── composables/          # Vue composables (reusable logic)
    └── pages/                # Vue page components
```

## Installation

### Prerequisites
- Docker and Docker Compose
- Git

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd eponuda
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=eponuda
   DB_USERNAME=sail
   DB_PASSWORD=password
   ```

6. **Start Laravel Sail**
   ```bash
   ./vendor/bin/sail up -d
   ```

7. **Run migrations**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   # Or for development:
   npm run dev
   ```

## Usage

### Scraping Televisions

Scrape televisions from the default URL:
```bash
./vendor/bin/sail artisan scrape:televisions
```

Or specify a custom URL:
```bash
./vendor/bin/sail artisan scrape:televisions "https://www.shoptok.si/televizorji/cene/206"
```

### Scraping TV Categories (Bonus)

Scrape a category and all its subcategories:
```bash
./vendor/bin/sail artisan scrape:tv-category
```

Or specify a custom URL:
```bash
./vendor/bin/sail artisan scrape:tv-category "https://www.shoptok.si/tv-prijamnici/cene/56"
```

## API Documentation

### Endpoints

#### GET `/api/televisions`
Get paginated list of televisions.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `category_id` (optional): Filter by category ID

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Samsung 55\" QLED TV",
      "price": 899.99,
      "image": "https://...",
      "product_link": "https://...",
      "specs": "...",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "current_page": 1,
  "last_page": 10,
  "per_page": 20,
  "total": 200
}
```

#### GET `/api/tv-categories`
Get list of TV categories.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "TV Receivers",
      "slug": "tv-receivers",
      "url": "https://...",
      "parent_id": null
    }
  ]
}
```

#### GET `/api/tv-categories/{id}/products`
Get paginated products for a specific category.

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response:** Same format as `/api/televisions`

## Frontend

### Vue.js Structure

The frontend is built with Vue 3 using the Composition API and follows best practices:

- **Components**: Reusable UI components (`TelevisionCard`, `Pagination`)
- **Pages**: Full page components (`TelevisionsPage`, `TvSprejemnikiPage`)
- **Composables**: Reusable logic (`useTelevisions`, `useCategories`)
- **API Modules**: Axios-based API clients (`televisions.js`, `categories.js`)

### Pages

#### `/televisions`
Displays all televisions in a grid layout with pagination.

#### `/tv-sprejemniki`
Category browsing page with:
- Left sidebar: List of categories (fetched from API)
- Right side: Product grid filtered by selected category
- Pagination support

### Development

Start the development server:
```bash
npm run dev
```

Build for production:
```bash
npm run build
```

## Architecture Details

### Repository Pattern

**Interface Example:**
```php
interface TelevisionRepositoryInterface
{
    public function paginate(int $perPage = 20, ?int $categoryId = null): LengthAwarePaginator;
    public function findByExternalId(string $externalId): ?Television;
    public function create(array $data): Television;
}
```

**Implementation:**
```php
class TelevisionRepository implements TelevisionRepositoryInterface
{
    // All database operations here
}
```

**Dependency Injection:**
Registered in `AppServiceProvider`:
```php
$this->app->bind(TelevisionRepositoryInterface::class, TelevisionRepository::class);
```

### Service Layer

Services contain business logic and use repositories for data access:

```php
class TelevisionScraperService
{
    public function __construct(
        private readonly TelevisionRepositoryInterface $repository
    ) {}
    
    public function scrape(string $url): int
    {
        // Scraping logic
        // Uses repository to save data
    }
}
```

### Controllers

Controllers are thin and only handle HTTP concerns:

```php
class TelevisionController extends Controller
{
    public function __construct(
        private readonly TelevisionRepositoryInterface $repository
    ) {}
    
    public function index(Request $request)
    {
        return TelevisionResource::collection(
            $this->repository->paginate(20)
        );
    }
}
```

## Database Schema

### `televisions` table
- `id` (primary key)
- `name` (string)
- `price` (decimal)
- `image` (string, nullable)
- `product_link` (string, nullable)
- `specs` (text, nullable)
- `tv_category_id` (foreign key, nullable)
- `external_id` (string, unique, nullable)
- `timestamps`

### `tv_categories` table
- `id` (primary key)
- `name` (string)
- `slug` (string, unique)
- `url` (string)
- `parent_id` (foreign key, nullable)
- `timestamps`

## Code Quality

- **PSR-12** coding standards
- **Type hints** throughout
- **Dependency Injection** for all dependencies
- **Interface-based design** for repositories
- **Clean separation of concerns**

## Testing

Run tests:
```bash
./vendor/bin/sail artisan test
```

## Contributing

1. Follow PSR-12 coding standards
2. Maintain clean architecture principles
3. Write tests for new features
4. Update documentation

## License

MIT License
