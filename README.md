# GST Flow

GST Flow is a Laravel + MongoDB application for managing Indian GST operations end-to-end: business profiles, customers, products, HSN/GST classification, invoices, reporting, and compliance-oriented activity tracking.

## Tech Stack

- **Backend:** Laravel 12, Sanctum, MongoDB (`mongodb/laravel-mongodb`)
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Vite
- **Deployment:** Vercel (PHP runtime + static assets)

## What the app does

1. **Profile and master data**
   - Business profiles
   - Customers
   - Products
   - HSN codes
   - Tax slabs

2. **Invoice workflow**
   - Create and manage invoices
   - GST split (CGST/SGST/IGST) and totals
   - Invoice duplication and status updates
   - Version history endpoint

3. **Search and intelligence**
   - Smart HSN search endpoint with typo/fuzzy/keyword matching
   - HSN import and enrichment pipeline
   - Search logs and analytics (most searched, failed searches, low-confidence)

4. **Admin and observability**
   - User and role administration
   - Activity logs
   - Tax/HSN sync hooks and audit endpoints

## Key API surface

All API routes are under `/api` and protected via session/Sanctum auth unless noted.

- **Auth:** `/api/auth/*`
- **Profiles:** `/api/business-profiles`
- **Customers:** `/api/customers`
- **Products:** `/api/products`
- **HSN codes:** `/api/hsn-codes`, `/api/hsn-codes/catalog`, `/api/hsn-codes/sync`
- **Smart HSN search:** `/api/hsn/search`, `/api/hsn/search/select`
- **HSN admin:** `/api/hsn/products`, `/api/hsn/import`, `/api/hsn/analytics`
- **Invoices:** `/api/invoices`, `/api/invoices/{id}/duplicate`, `/api/invoices/{id}/status`, `/api/invoices/{id}/versions`
- **Reports/exports:** `/api/reports/*`, `/api/export/*`

## Local development

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MongoDB (local or Atlas)
- PHP MongoDB extension (`ext-mongodb`) compatible with `composer.lock`

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Configure MongoDB in `.env`:

```env
DB_CONNECTION=mongodb
MONGODB_URI=your-mongodb-uri
MONGODB_DATABASE=gst_platform
```

Run the app:

```bash
composer run dev
```

Build frontend assets:

```bash
npm run build
```

## Production notes

- Review `DEPLOYMENT.md` before deploying.
- Required production envs include `APP_KEY`, MongoDB connection values, and app URL settings.
- Vercel routing is configured in `vercel.json`.

## Data sync and updates

- HSN sync command:
  - `php artisan hsn:sync`
  - optional source override: `php artisan hsn:sync --source=https://...`
- Scheduled sync hooks are defined in `routes/console.php`.
- Source list for HSN catalog sync is configured via:
  - `HSN_CATALOG_SOURCES` (comma-separated URLs)

## Project structure (high-level)

- `app/Http/Controllers/Api` – API controllers
- `app/Services` – domain services (sync/search/audit)
- `app/Models` – MongoDB document models
- `resources/views/modules` – module pages (Blade + Alpine)
- `routes/api.php` / `routes/web.php` – route definitions

## Testing

Run tests:

```bash
composer test
```

> Note: tests require a working MongoDB setup and compatible PHP MongoDB extension.

## License

Internal project repository. Use according to your organization’s policy.

