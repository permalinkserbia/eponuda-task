## Television category scraper u eponuda projektu

### Pregled – šta radi category scraper

**Television category scraper** u `eponuda` projektu je nadogradnja na običan TV scraper koja:
- **scrape‑uje liste kategorija** TV uređaja / prijemnika sa `shoptok.si`,
- za svaku pronađenu kategoriju:
  - izvuče **naziv**, **URL** i **sliku kategorije**,
  - upiše ili ažurira je u tabeli **`tv_categories`**,
  - zatim pokrene **produkt scraper** za tu kategoriju i veže sve proizvode za odgovarajući `tv_category_id`.

Ključni delovi:
- **`ScrapeTelevisionCategories`** (Artisan komanda)
- **`TelevisionCategoryScraperService`** (glavna logika za kategorije + proizvode)
- **`TelevisionScraperService`** (nasleđeni deo za proizvode)
- **`TvCategoryRepositoryInterface`** + implementacija (upis kategorija)
- **`TelevisionRepositoryInterface`** (upis proizvoda)

---

### 1. Kako se pokreće – komanda `ScrapeTelevisionCategories`

Fajl: `app/Console/Commands/ScrapeTelevisionCategories.php`

- **Artisan komanda**:

  ```bash
  php artisan scrape:television-categories {url=https://www.shoptok.si/tv-prijamnici/cene/56}
  ```

  ili preko Sail‑a:

  ```bash
  ./vendor/bin/sail artisan scrape:television-categories
  ```

- U metodi `handle(TelevisionCategoryScraperService $scraperService)`:
  - čita argument `url` (ili koristi podrazumevanu vrednost),
  - ispisuje poruku da počinje scrape sa tog URL‑a,
  - poziva **`$scraperService->scrapeCategories($url)`**,
  - ispisuje koliko je ukupno proizvoda prikupljeno iz svih kategorija.

---

### 2. `TelevisionCategoryScraperService` – logika za kategorije

Fajl: `app/Services/TelevisionCategoryScraperService.php`  
Nasleđuje **`TelevisionScraperService`**, što znači da:
- koristi isti mehanizam za dohvatanje HTML‑a (`fetchHtml`, Browsershot + cURL fallback),
- koristi istu logiku za scrape proizvoda (`scrapeForCategory`, `extractProductData`, itd.),
- dodaje **specifičnu logiku za kategorije**.

#### 2.1. Konstruktor

```php
public function __construct(
    TelevisionRepositoryInterface $televisionRepository,
    private readonly TvCategoryRepositoryInterface $categoryRepository
) {
    parent::__construct($televisionRepository);
}
```

- `TelevisionRepositoryInterface` se prosleđuje parent servisu radi rada sa `televisions` tabelom.
- `TvCategoryRepositoryInterface` služi da se kategorije upisuju/azuriraju u `tv_categories`.

---

#### 2.2. Glavna metoda – `scrapeCategories()`

```php
public function scrapeCategories(string $entryUrl = 'https://www.shoptok.si/tv-prijamnici/cene/56'): int
```

Korak po korak:

1. **Dohvata HTML ulazne stranice** (`entryUrl`) preko nasleđenog `fetchHtml($entryUrl)`.
2. Kreira `Symfony\Component\DomCrawler\Crawler` nad tim HTML‑om.
3. **Pronalazi blokove kategorija** pomoću selektora:

   ```php
   $crawler->filter('[class*="col-4"][class*="col-md-3"][class*="col-lg-2"][class*="col-xl-1-5"][class*="mb-5"]')
   ```

   Svaki takav čvor (`$categoryNode`) predstavlja jednu kategoriju.

4. Za svaku kategoriju:
   - poziva **`extractCategoryData($categoryNode, $entryUrl)`**,
   - ako kategorija nije validna (nema ime ili URL) – preskače je,
   - ako je validna:
     - poziva `categoryRepository->updateOrCreate(['url' => $categoryData['url']], [...])` da je upiše/azurira u **`tv_categories`**,
     - dobija model kategorije sa njenim `id`,
     - poziva **`$this->scrapeForCategory($categoryData['url'], $category->id)`**, što:
       - scrape‑uje sve proizvode sa URL‑a kategorije,
       - za svaki proizvod setuje `tv_category_id = $category->id`,
       - upisuje/ažurira proizvode u tabeli `televisions`.
   - sabira ukupni broj proizvoda u `$totalProducts`.

5. Na kraju vraća **ukupan broj scrape‑ovanih proizvoda** iz svih pronađenih kategorija.

---

### 3. Ekstrakcija podataka o kategoriji

Centralna metoda:

```php
private function extractCategoryData(Crawler $categoryNode, string $baseUrl): ?array
```

Vraća:

```php
[
    'name'  => string,
    'url'   => string,
    'image' => ?string,
]
```

ili `null` ako neki ključni podatak nedostaje.

#### 3.1. Naziv kategorije – `extractCategoryName()`

```php
private function extractCategoryName(Crawler $categoryNode): ?string
```

- Primarno traži element sa klasama:

  ```php
  .text-center.line-height-13.mt-3.mb-0.text-16.font-semibold.font-poppins
  ```

- Ako ga ne nađe, pokušava **“partial class match”**:

  ```php
  [class*="text-center"][class*="line-height-13"][class*="font-semibold"]
  ```

- Ako ni to ne uspe ili je tekst prazan → vraća `null`.

**Cilj:** na strani `shoptok.si` ovo je tekst ispod ikone/slike kategorije (npr. naziv tipa “DVB-T prijemnici”, “Satelitski prijemnici”, itd.).

---

#### 3.2. URL kategorije – `extractCategoryUrl()`

```php
private function extractCategoryUrl(Crawler $categoryNode, string $baseUrl): ?string
```

- Pokušava da:
  - nađe prvi `a` tag **unutar** `categoryNode`,
  - pročita njegov `href`,
  - preko nasleđene `makeAbsoluteUrl($href, $baseUrl)` pretvori ga u **apsolutni URL**.
- Ako ne nađe `a` unutar čvora:
  - pokušava da nađe `a` u ancestor‑ima (`ancestors('a')->first()`),
  - opet pravi apsolutni URL ako postoji `href`.
- Ako ne uspe ni jedna varijanta → vraća `null`.

**Cilj:** dobije se URL liste proizvoda za tu kategoriju, npr. `https://www.shoptok.si/tv-prijamnici/dvb-t/cene/123`.

---

#### 3.3. Slika kategorije – `extractCategoryImage()`

```php
private function extractCategoryImage(Crawler $categoryNode, string $baseUrl): ?string
```

Koraci:

1. Traži `picture` element unutar `categoryNode`:
   - ako postoji, traži `source`:
     - čita atribut `srcset`,
     - uzima **prvi URL** iz `srcset` (pre prvog razmaka/zareza),
     - pravi apsolutnu adresu preko `makeAbsoluteUrl($firstUrl, $baseUrl)`.
   - ako `source` ne pomogne, pokušava `img` unutar `picture`:
     - čita jedan od: `src`, `data-src`, `data-lazy-src`, `data-original`,
     - opet pomoću `makeAbsoluteUrl()` pravi pun URL.

2. Ako nema `picture`, traži direktno `img` u `categoryNode`:
   - koristi iste atribute (`src`, `data-src`, itd.) i isti `makeAbsoluteUrl()`.

3. Ako ni to ne uspe → vraća `null`.

Slika kategorije nije obavezna za logiku aplikacije, ali je korisna ako želiš da prikazuješ “tile‑ove” kategorija na frontend‑u.

---

### 4. Generisanje slug‑a za kategoriju

```php
private function generateSlug(string $url): string
```

- Uzeće `path` iz URL‑a:

  ```php
  $path  = parse_url($url, PHP_URL_PATH);
  $parts = array_filter(explode('/', $path));
  $lastPart = end($parts);
  ```

- Poslednji deo path‑a (npr. `dvb-t-prijemnici`) šalje kroz:

  ```php
  return Str::slug($lastPart);
  ```

- Dobijeni `slug` se upisuje u kolonu `slug` u tabeli `tv_categories`.

**Poenta:** čak i ako je URL duži i kompleksniji, poslednji segment obično lepo reprezentuje ime kategorije.

---

### 5. Veza kategorija ↔ proizvodi

Zahvaljujući nasleđivanju od `TelevisionScraperService`, category scraper koristi metodu:

```php
$productsCount = $this->scrapeForCategory($categoryData['url'], $category->id);
```

Unutra:
- `scrapeForCategory()`:
  - scrape‑uje sve proizvode sa stranice kategorije,
  - za svaki proizvod:
    - ako je prosleđen `$categoryId`, dodaje:
      - `tv_category_id = $categoryId` u podatke,
  - poziva `saveProduct()` koji koristi `TelevisionRepository->updateOrCreate()` nad `external_id`.

Na ovaj način:
- **svaka kategorija** iz `tv_categories` dobija svoje proizvode u `televisions`,
- proizvodi se vezuju preko **foreign key‑a `tv_category_id`**,
- ponovni scrape ažurira postojeće proizvode (preko `external_id`), a ne pravi duplikate.

---

### 6. Tipičan flow korišćenja

1. Pokreneš:

   ```bash
   ./vendor/bin/sail artisan migrate:fresh   # po potrebi, reset baze
   ./vendor/bin/sail artisan scrape:television-categories
   ```

2. Category scraper:
   - povuče ulaznu stranicu sa listom kategorija (`entryUrl`),
   - izvuče sve kategorije (naziv, URL, sliku),
   - upiše ih u tabelu `tv_categories`,
   - za svaku kategoriju startuje product scraper:
     - dovlači HTML liste proizvoda za tu kategoriju,
     - izvuče proizvode (naziv, cena, slika, link, specs, external_id),
     - upiše ih u `televisions` sa popunjenim `tv_category_id`.

3. Frontend (npr. `TelevisionsPage.vue`, `TvReceiverPage.vue`) onda može:
   - da listu TV uređaja filtrira po `category_id`,
   - da iz tabele `tv_categories` prikaže listu dostupnih kategorija.

---

### 7. Kratak rezime

**Television category scraper**:
- scrape‑uje **stranicu sa kategorijama** na `shoptok.si`,
- za svaki “tile” kategorije izvuče **naziv**, **URL** i **sliku**, i upisuje ih u tabelu `tv_categories`,
- zatim za svaki URL kategorije koristi nasleđeni **TV product scraper** da:
  - izvuče sve proizvode u toj kategoriji,
  - sačuva ih u `televisions` sa ispravnim `tv_category_id`.

Na ovaj način dobijaš kompletan skup: **kategorije + proizvodi po kategoriji**, spreman za API i frontend.


