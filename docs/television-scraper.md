## Television scraper u eponuda projektu

### Pregled – šta radi TV scraper

**Television scraper** u `eponuda` projektu je skup konzolnih komandi i servisa koji:
- **otvore stranicu** sa televizorima / TV prijemnicima na `shoptok.si`,
- **izvuku sve proizvode** (naziv, cenu, sliku, link, eksterni ID, specifikacije),
- **upišu ili ažuriraju ih** u našoj `televisions` tabeli preko repozitorijuma,
- opciono ih **vežu za našu `tv_categories` tabelu**.

Ključni delovi:
- **`ScrapeTelevisions`** (komanda)
- **`ScrapeTelevisionCategories`** (komanda)
- **`TelevisionScraperService`** (glavna logika za proizvode)
- **`TelevisionCategoryScraperService`** (nadgradnja za kategorije + proizvode)
- **`TelevisionRepository`** + **`TelevisionRepositoryInterface`** (upis u bazu)

---

### 1. Kako se pokreće – konzolne komande

#### 1.1. `ScrapeTelevisions`

Fajl: `app/Console/Commands/ScrapeTelevisions.php`

- **Artisan komanda**:  
  - `scrape:televisions {url=https://www.shoptok.si/televizorji/cene/206}`
- U metodi `handle()`:
  - iz argumenata uzima `url` (ili podrazumevanu vrednost),
  - poziva `TelevisionScraperService->scrape($url)`,
  - ispisuje koliko je televizora uspešno scrape‑ovano.

#### 1.2. `ScrapeTelevisionCategories`

Fajl: `app/Console/Commands/ScrapeTelevisionCategories.php`

- **Artisan komanda**:  
  - `scrape:television-categories {url=https://www.shoptok.si/tv-prijamnici/cene/56}`
- U metodi `handle()`:
  - iz argumenata uzima početni `url` (stranica sa kategorijama TV prijemnika),
  - poziva `TelevisionCategoryScraperService->scrapeCategories($url)`,
  - ispisuje koliko je ukupno proizvoda prikupljeno iz svih kategorija.

---

### 2. `TelevisionScraperService` – jezgro logike za televizore

Fajl: `app/Services/TelevisionScraperService.php`

#### 2.1. Ulazne metode

- **`scrape(string $url): int`**
  - samo prosleđuje na **`scrapeForCategory($url)`**.

- **`scrapeForCategory(string $url, ?int $categoryId = null): int`**
  - poziva **`fetchHtml($url)`** da povuče HTML,
  - kreira `Symfony DomCrawler` nad HTML‑om,
  - pronalazi sve proizvode na stranici preko selektora:
    - `.b-paging-product, .product.b-paging-product, [class*="b-paging-product--vertical"]`
  - za svaki takav čvor:
    - poziva `extractProductData($node, $url)` – izvlači sve podatke o TV‑u,
    - ako je prosleđen `$categoryId` → dodaje `'tv_category_id' => $categoryId` u podatke,
    - poziva `saveProduct($productData)` da upiše/ažurira proizvod,
    - broji koliko je proizvoda uspešno procesirano i vraća taj broj.

**Suština:** ova metoda iterira kroz “kockice” proizvoda na listi i za svaku kockicu izvuče korisne informacije i upiše ih u bazu.

---

#### 2.2. Dohvatanje HTML‑a – anti‑bot i fallback

**`fetchHtml(string $url): string`**

1. **Validacija URL‑a**
   - Preko `UrlValidator::validate($url)` (SSRF zaštita).

2. **Pokušaj sa Browsershot (headless Chrome)**
   - `Browsershot::url($url)`:
     - setuje user-agent na realan Chrome,
     - koristi `waitUntilNetworkIdle()` da sačeka JS i mrežu,
     - postavlja `timeout(60)`,
     - dodaje razne `--no-sandbox` i slične argumente za headless okruženje.
   - Pokušava više mogućih putanja do Chrome/Chromium (`/usr/bin/chromium-browser`, `/usr/bin/google-chrome`, itd.).
   - Čita `bodyHtml()` – kompletan HTML posle izvršenog JS‑a.
   - Ako je HTML prazan → baca exception.

3. **Fallback na cURL (samo za “normalne” greške)**
   - Ako exception kaže da nema Chrome‑a (`Could not find Chrome`, `ChromeLauncher`), **ne ide na cURL**, nego baca jasnu grešku (jer će verovatno dobiti 403).
   - U svim ostalim slučajevima koristi **`fetchHtmlWithCurl($url)`**.

**`fetchHtmlWithCurl(string $url): string`**

- Kreira privremeni cookie fajl da imitira sesiju.
- Ubacuje random delay 1–3 sekunde (`usleep`) da se izbegne rate limiting.
- Setuje:
  - random user‑agent iz liste realnih browsera,
  - HTTP/2,
  - “realne” HTTP header‑e (Accept, Accept‑Language, Accept‑Encoding, Referrer, DNT, itd.).
- Prati redirekcije, isključuje neke SSL provere (pragmatično).
- Dekompresuje gzip/deflate (`CURLOPT_ENCODING => ''`).
- Ako dobije:
  - cURL grešku → baca exception sa detaljima,
  - status 403 → baca exception da je sajt blokirao automatizovane zahteve,
  - bilo šta osim 200 ili prazan HTML → baca exception.
- Inače vraća HTML.

**Poenta:** prvo se pokušava “pravi browser” (Browsershot), a ako padne iz drugih razloga, koristi se “napucani” cURL koji se ponaša kao običan korisnik.

---

#### 2.3. Ekstrakcija podataka o produktu

**`extractProductData(Crawler $node, string $baseUrl): ?array`**

Koraci:

1. **Naziv (`name`)**
   - `extractText(...)` nad listom selektora:
     - `.product-name`, `.product-title`, `h2`, `h3`, `[class*="name"]`, `[class*="title"]`
   - Ako naziv ne postoji → vraća `null` (taj čvor se preskače).

2. **Cena (`price`)**
   - `extractPrice($node)`:
     - primarno traži element sa atributom `event-viewitem-price` unutar istog “product” bloka (`b-paging-product`),
     - ako nema, traži `.b-paging-product__price` ili `[class*="b-paging-product__price"]`,
     - iz tog čvora:
       - prvo pokušava da parsira tekst,
       - ako ne uspe, gleda `data-price`, `data-price-value`, `content`,
       - svaku vrednost šalje u `parsePrice()` i `isValidPrice()`.
   - Ako cena ispadne `null` → proizvod se preskače i ne snima u bazu.

3. **Slika (`image`)**
   - `extractImage($node, $baseUrl)`:
     - prolazi kroz više image selektora (`img.lazy`, `[class*="lazy"] img`, `img`, `.product-image img`, ...),
     - za lazy‑load prvo gleda `data-src`, `data-lazy-src`, `data-original`, pa `src`,
     - koristi `makeAbsoluteUrl()` da apsolutizuje URL slike u odnosu na `baseUrl`.

4. **Link proizvoda (`product_link`)**
   - `extractLink($node, $baseUrl)`:
     - pokušava da uzme prvi `a` tag u okviru čvora,
     - ako ne uspe, traži `a` među ancestor‑ima,
     - koristi `makeAbsoluteUrl()` za puni URL.

5. **Eksterni ID (`external_id`)**
   - `extractExternalId($productLink)`:
     - regex `/(\\d+)(?:/|$)/` pokušava da izvuče ID iz URL‑a (npr. `.../12345/` → `12345`),
     - ako nema takav pattern → koristi `md5($url)`.

6. **Specifikacije (`specs`)**
   - `extractSpecs($node)`:
     - traži `.specs`, `.product-specs`, `[class*="spec"]`,
     - čisti tekst i vraća vrednost.

7. **Sanitizacija**
   - `sanitizeString()` preko `strip_tags` + `htmlspecialchars` uklanja HTML i XSS.

Metoda vraća niz:  
`['name', 'price', 'image', 'product_link', 'external_id', 'specs']`.

Ako fali naziv ili cena → vraća `null` i proizvod se ne snima.

---

#### 2.4. Logika oko cene – `parsePrice()` i `isValidPrice()`

- **`parsePrice(string $priceText): ?float`**
  - uklanja sve osim cifara, tačke i zareza,
  - prepoznaje EU format (`1.234,56`) naspram US formata (`1,234.56`),
  - konvertuje u `float`,
  - ako je rezultat `<= 0` → vraća `null`.

- **`isValidPrice(float $price, string $originalText): bool`**
  - hard‑kodovane granice za TV:
    - ako je `< 50` ili `> 5000` → odbacuje cenu (van normalnog opsega),
  - ako je između `2020` i `2030` → tretira kao godinu, ne cenu,
  - ako je `> 2000`, a u originalnom tekstu nema indikatora valute (`€`, `EUR`, `euro`, `cen`) → tretira kao verovatno model ili godinu i odbacuje.

Cilj je da se čudni brojevi (modeli, godine, ID‑evi) ne upišu kao cene.

---

#### 2.5. Čuvanje u bazu – `saveProduct()`

**`saveProduct(array $data): void`**

- Ako `external_id` nedostaje:
  - pravi ga kao `md5(name + product_link)` – deterministički ID.
- Koristi **`TelevisionRepository`**:
  - `updateOrCreate(['external_id' => $data['external_id']], $data)`:
    - ako TV sa tim `external_id` već postoji → radi **update**,
    - ako ne postoji → radi **create**.

Na ovaj način se sprečava dupliranje proizvoda kroz više scrape‑ova.

`TelevisionRepository` interface i implementacija (`TelevisionRepository.php`) su standardni Eloquent repozitorijum (paginate, findByExternalId, create, update, updateOrCreate).

---

### 3. `TelevisionCategoryScraperService` – kategorije + proizvodi

Fajl: `app/Services/TelevisionCategoryScraperService.php`  
Nasleđuje `TelevisionScraperService`, pa koristi svu postojeću logiku za proizvode.

#### 3.1. Konstruktor

- Prima:
  - `TelevisionRepositoryInterface` (prosleđuje parent servisu),
  - `TvCategoryRepositoryInterface` (za rad sa kategorijama).

#### 3.2. Glavna metoda – `scrapeCategories()`

**`scrapeCategories(string $entryUrl = 'https://www.shoptok.si/tv-prijamnici/cene/56'): int`**

- Povlači HTML ulazne stranice preko `fetchHtml()` (nasleđeno).
- Kreira `DomCrawler` nad tim HTML‑om.
- Traži elemente sa klasama koje predstavljaju blokove kategorija:
  - `[class*="col-4"][class*="col-md-3"][class*="col-lg-2"][class*="col-xl-1-5"][class*="mb-5"]`
- Za svaki takav “category” blok:
  - `extractCategoryData($categoryNode, $entryUrl)`:
    - `extractCategoryName()` – hvata naziv kategorije unutar kombinacije klasa (`text-center`, `font-semibold`, itd.) uz fallback sa “partial class” selektorom,
    - `extractCategoryUrl()` – traži `a` u tom bloku i pravi apsolutnu adresu,
    - `extractCategoryImage()` – traži `picture` + `source[srcset]` ili `img` i pravi apsolutni URL slike.
  - Ako je kategorija validna (ima ime i URL):
    - poziva `categoryRepository->updateOrCreate(['url' => $categoryData['url']], [...])` sa:
      - `name`,
      - `slug` (preko `generateSlug($url)` – poslednji deo path‑a kroz `Str::slug`),
      - `parent_id` trenutno `null`.
    - zatim za tu kategoriju poziva **`$this->scrapeForCategory($categoryData['url'], $category->id)`**:
      - što:
        - povlači HTML stranice za tu kategoriju,
        - izvuče sve proizvode (kao u `TelevisionScraperService`),
        - setuje `tv_category_id` na ID te kategorije,
        - upisuje ih kroz `TelevisionRepository`.

Metoda na kraju vraća **ukupan broj proizvoda iz svih kategorija**.

---

### 4. API strana – kako se ovi podaci koriste

Iako nije direktno deo scrappera, ovo zaokružuje sliku.

- **`TelevisionController@index`**
  - koristi `TelevisionRepository->paginate(...)` i vraća paginiranu listu TV uređaja,
  - podržava filter preko `category_id`.

- **`TelevisionIndexRequest`**
  - validira `page`, `per_page`, `category_id`.

- Na frontendu (`resources/js`):
  - `TelevisionsPage.vue`, `TvReceiverPage.vue`, `TelevisionCard.vue`, itd.
  - prikazuju ono što je scraper upisao u bazu.

---

### 5. Kratak rezime

**Television scraper**:
- preko Browsershot/cURL‑a dovuče HTML sa `shoptok.si`,
- iz HTML‑a, uz robustne CSS selektore i heuristike, izvlači naziv, cenu, sliku, link, eksterni ID i specifikacije za svaki TV,
- validira i normalizuje podatke (posebno cene),
- upisuje ili ažurira ih u `televisions` tabeli, uz opciono vezivanje na `tv_categories` kada se koristi kategorijski scraper.


