# Eponuda - Laravel Scraper Project

A complete Laravel project implementing clean architecture principles with Repository Pattern, Service Layer, and VueJS frontend. This project scrapes product data from shoptok.si and displays it through a modern Vue.js interface.

## Quick Start

```bash
# 1. Clone and enter directory
git clone <repository-url>
cd eponuda

# 2. Copy environment file
cp .env.example .env

# 3. Start Docker containers
./vendor/bin/sail up -d

# 4. Generate app key
./vendor/bin/sail artisan key:generate

# 5. Run migrations
./vendor/bin/sail artisan migrate

# 6. Install Node dependencies and build
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 7. Install Puppeteer Chrome (for scraping)
./vendor/bin/sail artisan tinker --execute="
exec('cd /var/www/html && npx puppeteer browsers install chrome-headless-shell 2>&1', \$output, \$return);
echo implode(PHP_EOL, \$output);
"

# 8. Scrape categories and products
./vendor/bin/sail artisan scrape:television-categories

# 9. Visit http://localhost/televisions or http://localhost/tv-sprejemniki
```

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
- Docker and Docker Compose installed
- Git
- User must be in the `docker` group (for Linux)
  ```bash
  sudo usermod -aG docker $USER
  # Log out and back in for changes to take effect
  ```

### Step-by-Step Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd eponuda
   ```

2. **Set up environment file**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and configure:
   ```env
   APP_NAME=Eponuda
   APP_ENV=local
   APP_KEY=  # Will be generated in next step
   APP_DEBUG=true
   APP_URL=http://localhost
   
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=eponuda
   DB_USERNAME=sail
   DB_PASSWORD=password
   
   WWWUSER=1000
   WWWGROUP=1000
   ```

3. **Start Docker containers with Laravel Sail**
   ```bash
   ./vendor/bin/sail up -d
   ```
   
   This will:
   - Build the custom Docker image with Chromium support
   - Start MySQL, Redis, and Laravel services
   - Install Puppeteer's Chrome for web scraping

4. **Generate application key**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

5. **Run database migrations**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

6. **Install Node.js dependencies**
   ```bash
   ./vendor/bin/sail npm install
   ```

7. **Build frontend assets**
   ```bash
   ./vendor/bin/sail npm run build
   ```

8. **Install Puppeteer Chrome (if not already installed)**
   ```bash
   ./vendor/bin/sail artisan tinker --execute="
   exec('cd /var/www/html && npx puppeteer browsers install chrome-headless-shell 2>&1', \$output, \$return);
   echo implode(PHP_EOL, \$output);
   "
   ```

## Running the Project

### Start Services

Start all Docker containers:
```bash
./vendor/bin/sail up -d
```

### Access the Application

- **Frontend**: http://localhost
- **Televisions Page**: http://localhost/televisions
- **TV Receivers Page**: http://localhost/tv-sprejemniki
- **API**: http://localhost/api/televisions

### Development Mode

For frontend development with hot reload:
```bash
./vendor/bin/sail npm run dev
```

The Vite dev server will run on port 5173 (or as configured in `.env`).

### Stop Services

Stop all containers:
```bash
./vendor/bin/sail down
```

Stop and remove volumes (⚠️ deletes database data):
```bash
./vendor/bin/sail down -v
```

## Usage

### Scraping Categories and Products

The main scraping command populates all categories and their products:

```bash
./vendor/bin/sail artisan scrape:television-categories
```

Or specify a custom entry URL:
```bash
./vendor/bin/sail artisan scrape:television-categories "https://www.shoptok.si/tv-prijamnici/cene/56"
```

**What this command does:**
1. Fetches the category listing page using Browsershot (headless Chrome)
2. Extracts all categories with their names, URLs, and images
3. Creates/updates categories in the database
4. For each category, scrapes all products using the television scraper
5. Associates products with their respective categories
6. Returns the total count of scraped products

**Note**: The scraper uses Browsershot with Puppeteer's Chrome. Ensure Chrome is installed (see Installation step 8) or the scraper will fail with a 403 error.

**Example output:**
```
Starting to scrape TV categories from: https://www.shoptok.si/tv-prijamnici/cene/56
Successfully scraped 68 products from all categories.
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
- **Pages**: Full page components (`TelevisionsPage`, `TvReceiverPage`)
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

#### Frontend Development

Start Vite dev server with hot reload:
```bash
./vendor/bin/sail npm run dev
```

Build for production:
```bash
./vendor/bin/sail npm run build
```

#### Running Artisan Commands

All Laravel artisan commands should be run through Sail:
```bash
./vendor/bin/sail artisan <command>
```

Examples:
```bash
# Run migrations
./vendor/bin/sail artisan migrate

# Run migrations fresh (⚠️ deletes all data)
./vendor/bin/sail artisan migrate:fresh

# Access Tinker
./vendor/bin/sail artisan tinker

# Clear cache
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
```

#### Accessing the Container

Open a shell in the Laravel container:
```bash
./vendor/bin/sail shell
```

Or run a one-off command:
```bash
./vendor/bin/sail exec laravel.test <command>
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

## Troubleshooting

### Common Issues

#### 1. Permission Denied Errors

If you get permission errors, ensure:
- User is in the `docker` group: `sudo usermod -aG docker $USER`
- Log out and back in after adding to docker group
- Check file permissions: `chmod -R 777 storage bootstrap/cache`

#### 2. Browsershot/Chrome Not Found

If scraping fails with "Could not find Chrome" error:
```bash
./vendor/bin/sail artisan tinker --execute="
exec('cd /var/www/html && npx puppeteer browsers install chrome-headless-shell 2>&1', \$output, \$return);
echo implode(PHP_EOL, \$output);
"
```

#### 3. Container Won't Start

- Check if ports 80, 3307, 6379 are available
- Review logs: `./vendor/bin/sail logs`
- Rebuild containers: `./vendor/bin/sail build --no-cache`

#### 4. Database Connection Issues

- Ensure MySQL container is running: `./vendor/bin/sail ps`
- Check `.env` database credentials
- Verify database exists: `./vendor/bin/sail mysql -e "SHOW DATABASES;"`

#### 5. Frontend Not Loading

- Rebuild assets: `./vendor/bin/sail npm run build`
- Clear Laravel cache: `./vendor/bin/sail artisan cache:clear`
- Check browser console for errors

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
