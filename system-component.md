# IOMP System Component Status

Status reflects verified repository behavior. A checked item is implemented and covered by an executable test or direct build verification. Partial items have a working foundation but still require named follow-up work.

## Public Portal

- [x] Professional responsive landing page with light and dark themes
- [x] Opportunity search and advanced cascading filters
- [x] Opportunity detail with financial, contact and document sections
- [x] Public investor inquiry form with validation, consent and throttling
- [x] Versioned public opportunity API under `/api/v1`
- [x] PWA manifest, service worker and offline fallback
- [ ] Opportunity comparison
- [~] Interactive Leaflet opportunity point map implemented; approved district GeoJSON remains a production publication gate
- [x] Public district directory and district detail

## Authentication And Access

- [x] Separate investor and staff login flows
- [x] Registration, password reset and email-verification foundations
- [x] Active staff-account enforcement for staff routes
- [x] Spatie role and workflow permission matrix
- [x] Least-privilege Field Agent role for district and opportunity submission
- [x] Environment-configured default Super Administrator seeder
- [x] Normalized, audited multi-district officer assignments with active-period restrictions
- [ ] MFA user experience and enforcement
- [ ] User, role and permission management screens
- [x] JWT login, rotating refresh, database revocation and logout for protected API routes

## Region And District Management

- [x] Normalized region and district models with UUID routing
- [x] Separate regional capital and district capital fields
- [x] Canonical seed artifact with 16 regions and 261 districts
- [x] Idempotent district registry seeder
- [x] Draft to under-review to published workflow service
- [x] Submit, reject, reassign and publish actions
- [x] SLA due dates, immutable events, audit entries and queued notifications
- [x] Paginated district staff work queue and review workspace
- [x] District overview indicators for coverage, readiness and workflow state
- [x] Permission-controlled district draft create, edit and soft delete
- [ ] Approved GeoJSON import and geometry validation

## Opportunity Management

- [x] Normalized opportunity, financial, contact and document relationships
- [x] Draft to pending approval to approved to active lifecycle
- [x] Submit, approve, reject, reassign, activate, complete and cancel actions
- [x] Configurable SLA due dates and breach indicators
- [x] Immutable workflow events with actor, assignment, reason and timestamp
- [x] Material workflow activity logging with before and after values
- [x] Queued workflow mail and database notifications
- [x] Paginated staff work queue and approval workspace
- [x] Opportunity overview indicators for value, reach and workflow state
- [x] Permission-controlled opportunity draft create, edit and soft delete
- [x] Staff management for sectors, sub-sectors and enterprise types
- [x] Deterministic 150+ opportunity demonstration dataset
- [x] Scheduled SLA breach scanner and escalation notification
- [ ] Notification-template management

## Investor Inquiries

- [x] Public opportunity-linked submission and tracking reference
- [x] Inquiry persistence, status and assignment-ready schema
- [ ] Staff inquiry assignment and response workspace
- [ ] Inquiry response notification

## Oversight And Operations

- [x] Staff dashboard with core workflow, district, inquiry and SLA indicators
- [x] Spatie activity-log persistence for workflow actions
- [x] Database queue and notification storage
- [x] Permission-gated audit log viewer with log, event, subject, actor and date filters
- [x] SLA monitoring dashboard with breach and at-risk alerts and per-queue timeline tracking
- [x] Baseline security-headers middleware on all web responses (clickjacking, MIME-sniffing, referrer, HSTS on TLS)
- [x] Audit log CSV/PDF export and configurable, scheduler-enforced retention window
- [ ] Reports dashboard and exports
- [ ] Central system, workflow and notification settings UI
- [ ] Maintenance control UI, administrator exception and IP whitelist
- [x] OpenAPI 3.1 contract and locally bundled Swagger UI
- [ ] Administrative messaging and onboarding tour

## Extended Programme Components

These programme components are named in the broader concept material but are not defined as completed IOMP prototype modules in the current implementation.

- [x] Investor onboarding architecture implemented through normalized profiles, KYC documents, workflow cases, immutable events, SLA tracking and permission-gated review
- [x] Investor profile creation and resumable backfill
- [x] KYC document catalogue, private quarantine storage, checksums and authorized downloads
- [x] Investor onboarding submission, staff review, action-required, approval and rejection workflow
- [x] Immutable onboarding events, activity audit, permissions, SLA dates and queued notifications
- [x] Investor onboarding portal and cursor-paginated staff review workspace
- [ ] Automated malware scanner integration and compliance-provider checks
- [ ] Normalized investor organizations, memberships and addresses
- [ ] Investor aftercare cases, interactions, tasks and inquiry conversion
- [ ] Act 1173 renewal rules after authoritative legal/process confirmation
- [x] Certificate registry with UUID routing, reference types and immutable issued snapshots
- [x] RSA/SHA-256 signing, canonical payload hashes, key IDs and retained-key verification
- [x] Opaque QR verification URLs with encrypted token recovery and hashed public lookup
- [x] Minimal public web/API verification with rate limiting and no private officer/evidence fields
- [x] District-scoped staff registry, field inspection handoff and append-only officer decisions
- [x] Idempotent verification records with optional GPS, notes and private evidence quarantine
- [x] Audited issue, suspend, reinstate and revoke lifecycle transitions under row locks
- [x] Queued private QR/PDF generation with authorized downloads and retry-safe cleanup
- [x] Cursor-paginated, indexed certificate/assignment queues and bounded aggregate queries
- [x] Online-only validity checks excluded from service-worker caching
- [ ] Production signing-key custody/HSM or approved key-management service
- [ ] Automated evidence malware scanner and approved retention/export policy
- [ ] JWT-protected field API and OpenAPI contract after authentication approval
- [ ] Explicitly freshness-labelled offline synchronization after device/staleness approval
- [x] Explainable investor-to-opportunity matchmaking with normalized preferences and bounded candidate ranking
- [ ] ORC, GIS and payment integrations
- [x] AI assistant with governed, grounded and cited responses
- [ ] Broader internal automation and operational dashboards

## GIPA Assistant

A retrieval-augmented assistant that answers public and investor questions from a curated knowledge base and live platform data. It runs on a deterministic offline driver by default (no external calls, fully testable) with an optional OpenAI driver.

- [x] Floating chat widget on public pages, the landing page and the investor workspace with an attention-drawing animated launcher and reduced-motion fallback
- [x] Grounded retrieval-augmented answers with inline source citations from published knowledge documents
- [x] Deterministic offline driver by default with an optional OpenAI chat/embedding driver selected by `ASSISTANT_DRIVER`
- [x] Knowledge indexer that chunks, embeds and checksums documents, computing embeddings before opening the write transaction
- [x] Embedding-model-scoped retrieval so only chunks from the active model are compared
- [x] Live platform-statistics tool gated to count questions about supported metrics (opportunities, sectors, districts, regions)
- [x] Certificate-verification tool that returns QR/verification-link guidance and never fabricates a certificate result
- [x] Session-bound conversation memory with guest resume protected by a session-token match and owner check for authenticated users
- [x] Guardrails for out-of-scope refusal, prompt-injection deflection, question-length limits and per-session rate limiting
- [x] Permission-gated staff knowledge base (`assistant.knowledge.view` / `assistant.knowledge.manage`) to create, edit, publish, delete and re-index documents
- [x] Queued full re-index job with queued/running/completed/failed status surfaced on the knowledge-base screen
- [x] Scheduled retention pruning command that deletes conversations older than the configured period and cascades their messages
- [ ] Streaming responses and multi-language answers

## Oversight And Security

Elevated-staff tooling for compliance traceability and service-level governance, plus a security-hardening pass informed by an OWASP Top 10 review.

- [x] Audit log viewer over Spatie activity records with filters for log, event, subject, actor and date range, and expandable before/after property detail
- [x] SLA monitoring dashboard aggregating breached, at-risk and on-track counts across opportunity, district and onboarding queues with per-record deadline timelines
- [x] New `audit.logs.view` and `sla.monitor.view` permissions granted to Super Administrator and Reviewer / Approver
- [x] Permission-gated routes and navigation group revealed only to authorised staff
- [x] Baseline security-headers middleware (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Cross-Origin-Opener-Policy`, `Permissions-Policy`, conditional `Strict-Transport-Security`)
- [x] Strict Content-Security-Policy with a fresh per-request nonce; former inline event handlers externalised to first-party scripts so `script-src` runs without `unsafe-inline`
- [x] Audit-log CSV and PDF export (filter-aware, `audit.logs.export` gated) and a configurable retention window enforced by a daily `activitylog:clean` schedule
- [x] Hourly `sla:escalate` command that emails assigned reviewers on breach and records `sla_escalated_at` to prevent duplicate alerts
- [x] Verified safe posture: Eloquent-only queries (no raw SQL), explicit `$fillable` on all models, whitelisted file-upload MIME/size with private disk and SHA-256 integrity, escaped Blade output, environment-only secrets
- [x] Documented production `.env` hardening (`SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT`, `SESSION_SAME_SITE`)
- [ ] Automated dependency and static-analysis scanning in CI

## Verification

- [x] District registry test verifies 16 regions and 261 unique districts
- [x] Domain-model tests verify UUIDs, relationships, casts and event immutability
- [x] Public discovery tests verify visibility, filters, pagination and bounded queries
- [x] Administrative workflow tests verify permissions, transitions, SLA, audit and notifications
- [x] Investor onboarding tests verify registration, backfill, KYC gates, private storage, ownership, permissions, audit and decisions
- [x] Certificate tests verify district scope, immutable signing, tamper detection, key rotation, public privacy, idempotency, artifacts and query bounds
- [x] Assistant tests verify grounded citations, live tools, refusal without context, prompt-injection deflection, rate limiting, session-bound conversation memory and guest-resume protection
- [x] Assistant maintenance tests verify retention pruning and queued re-index status
- [x] Assistant knowledge-management tests verify permission scope, document create/index, update and delete
- [x] Oversight tests verify audit-log permission gating, filtering and SLA dashboard access
- [x] Audit-export tests verify export permission gating, CSV streaming and format validation
- [x] SLA escalation tests verify one-time breach notification to the assigned reviewer and in-window suppression
- [x] Security-headers tests verify baseline hardening headers, TLS-only HSTS and a nonce-based Content-Security-Policy
- [x] Frontend production assets compile through Vite

## Data Dependencies

- [x] Spreadsheet district registry normalized to `database/data/ghana-districts.json`
- [x] Regional and district capitals retained at their correct hierarchy levels
- [ ] Authoritative district boundary GeoJSON must be confirmed by the client before map publication
- [ ] Exact workflow SLA durations and escalation recipients require client approval before production