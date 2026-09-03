# IOMP: Investment Opportunities Mapping Platform

IOMP is a Laravel platform for governing, publishing, discovering, and matching investment opportunities across Ghana. It combines public opportunity and district discovery with investor onboarding, staff workflows, cryptographically signed certificates, field verification, and versioned APIs.

## Platform Capabilities

- Public opportunity search, district directory/detail, and Leaflet point map.
- Explainable investor-to-opportunity matching by sector, region, capital range, currency, and district readiness.
- Professional investor workspace for preferences, recommendations, inquiries, profile, and KYC progress.
- District and opportunity draft/review/publication workflows with SLA timestamps and immutable events.
- Private KYC quarantine and permission-gated review.
- RSA/SHA-256 certificate signing, private QR/PDF artifacts, lifecycle controls, and public verification.
- Versioned public catalog APIs and JWT-protected investor APIs.
- Short-lived access JWTs, opaque hashed refresh tokens, rotation, replay prevention, and immediate revocation.
- OpenAPI 3.1 contract and locally bundled Swagger UI.

See [system-component.md](system-component.md) for verified implementation status and [resultDocs/platform-user-guide.md](resultDocs/platform-user-guide.md) for user journeys and data flows.

## Technology

- PHP 8.2 and Laravel 12
- Blade, Tailwind CSS 4, Vite 7
- Leaflet 1.9, Chart.js 4, Lucide icons
- MySQL-compatible production schema; SQLite test support
- Spatie Permission, Activitylog, and MediaLibrary
- Firebase PHP JWT 7.1
- Dompdf and Endroid QR Code
- PHPUnit 11 and Laravel Pint

## Local Setup

Prerequisites: PHP 8.2+, Composer, Node.js/npm, and a configured database.

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
npm install
php artisan migrate
npm run build
php artisan serve
```

Run the queue worker in a second terminal:

```powershell
php artisan queue:work --tries=3
```

The application is then available at `http://localhost:8000`. In environments using a local virtual host, use the configured `APP_URL` instead.

## Configuration

Configure database, mail, queue, cache, and storage through `.env`. Important platform variables include:

- `DISTRICT_REVIEW_SLA_HOURS`, `OPPORTUNITY_REVIEW_SLA_HOURS`, `INVESTOR_ONBOARDING_REVIEW_SLA_HOURS`
- `CERTIFICATE_ACTIVE_KEY_ID`, `CERTIFICATE_SIGNATURE_ALGORITHM`, and certificate key paths
- `JWT_SIGNING_KEY`, `JWT_ACCESS_TTL_MINUTES`, `JWT_REFRESH_TTL_DAYS`
- `DEFAULT_SYSTEM_USER_*` for baseline administration
- `IOMP_SEED_DEMO_DATA` and `DEMO_*` for opt-in demonstration records

Use a unique high-entropy `JWT_SIGNING_KEY` in every production environment. Access JWTs default to 15 minutes. Refresh secrets default to 30 days, are stored only as SHA-256 hashes, and rotate on use.

## Demo Data

Demo scenarios are opt-in. Populate `DEFAULT_SYSTEM_USER_*`, `DEMO_*`, and certificate variables, then set `IOMP_SEED_DEMO_DATA=true`.

Provision the local certificate signing key before seeding:

```powershell
php artisan certificates:key-generate
php artisan migrate --seed
php artisan queue:work --stop-when-empty --tries=3
```

The idempotent chain creates Ghana reference data, role-based staff, 150 investor profiles, 150+ opportunities, onboarding cases, certificates, lifecycle scenarios, field verifications, and in-app alerts. Demo bulk alerts do not send external email. Never use demo credentials or locally generated signing keys in production.

## Matchmaking

Investor preferences use normalized sector and region pivots plus indexed scalar budget/currency/readiness fields. Candidate eligibility is applied in SQL to published opportunities before scoring. The service caps candidates at 200 and returns at most 12 matches.

The deterministic score is sector 40, region 25, investment range 25, and district readiness up to 10. Every result includes reasons. This is an explainable decision-support ranking, not an automated approval decision.

Apply new migrations before using this component:

```powershell
php artisan migrate
```

## Maps and Geographic Data

The district directory is at `/districts`. Leaflet renders published opportunities with valid Ghana-range coordinates, and browser geolocation is optional.

`public/data/ghana.geojson` contains a national outline only. It is not district geometry and must not determine district identity. Publishing district polygons remains gated on client-approved GeoJSON, code reconciliation, EPSG:4326 geometry validation, and an operational import/rollback process.

## API and Swagger

- Interactive Swagger UI: `/api/documentation`
- OpenAPI contract: `/openapi.yaml`
- API base: `/api/v1`

Public endpoints cover regions, districts, sectors, sub-sectors, opportunities, inquiries, and certificate verification. Protected investor endpoints expose only the authenticated investor's profile, preferences, and matches.

Authentication flow:

1. `POST /api/v1/auth/login`
2. Send `Authorization: Bearer <access_token>`
3. `POST /api/v1/auth/refresh` once per refresh token
4. `POST /api/v1/auth/logout` to revoke the session

Login, refresh, protected, public catalog, inquiry, and verification endpoints are separately throttled. Refresh tokens must be held in secure client storage and never placed in URLs.

## Development and Verification

```powershell
vendor\bin\pint
php artisan test
npm run build
```

Useful narrow checks:

```powershell
php artisan test tests/Feature/InvestorOpportunityMatcherTest.php
php artisan test tests/Feature/JwtAuthenticationTest.php
php artisan test tests/Feature/PublicOpportunitySearchTest.php
php artisan route:list --path=api/v1
```

## Production Gates

Before production, complete or verify:

- Approved district boundary dataset and geometry import validation.
- External secret manager or approved key custody for JWT and certificate keys.
- TLS, secure cookies, trusted proxy/CORS configuration, and restrictive database credentials.
- Private object storage permissions, malware scanning, evidence retention, and deletion policies.
- Queue supervision, failed-job alerting, backups, restore testing, logging, and rate-limit monitoring.
- Final SLA durations, escalation recipients, privacy text, email/SMS providers, and contact information.
- Load, accessibility, browser, mobile, and penetration testing.

## Repository Guide

- `app/Services`: workflow, search, signing, verification, and matching domain logic.
- `app/Http/Controllers/API`: versioned authentication, catalog, and investor APIs.
- `resources/views`: public, investor, staff, and Swagger interfaces.
- `database/migrations`: normalized schema and compound indexes.
- `database/seeders`: baseline and opt-in deterministic demo scenarios.
- `resultDocs`: architecture notes and operational user guides.
- `pro-docs`: source requirements and controlled reference material.
