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
- [ ] Interactive production map backed by approved district GeoJSON
- [ ] Public district directory and district detail

## Authentication And Access

- [x] Separate investor and staff login flows
- [x] Registration, password reset and email-verification foundations
- [x] Active staff-account enforcement for staff routes
- [x] Spatie role and workflow permission matrix
- [x] Least-privilege Field Agent role for district and opportunity submission
- [x] Environment-configured default Super Administrator seeder
- [ ] District-level officer assignment restrictions
- [ ] MFA user experience and enforcement
- [ ] User, role and permission management screens
- [ ] JWT login and refresh for protected API routes

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
- [ ] Scheduled SLA breach scanner and escalation notification
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
- [ ] Audit log viewer, retention controls and export
- [ ] Reports dashboard and exports
- [ ] Central system, workflow and notification settings UI
- [ ] Maintenance control UI, administrator exception and IP whitelist
- [ ] OpenAPI / Swagger UI
- [ ] Administrative messaging and onboarding tour

## Extended Programme Components

These programme components are named in the broader concept material but are not defined as completed IOMP prototype modules in the current implementation.

- [x] Source-reviewed Component 1 architecture and delivery gates documented in `dev-Docs/investor-onboarding-architecture.md`
- [x] Investor profile creation and resumable backfill
- [x] KYC document catalogue, private quarantine storage, checksums and authorized downloads
- [x] Investor onboarding submission, staff review, action-required, approval and rejection workflow
- [x] Immutable onboarding events, activity audit, permissions, SLA dates and queued notifications
- [x] Investor onboarding portal and cursor-paginated staff review workspace
- [ ] Automated malware scanner integration and compliance-provider checks
- [ ] Normalized investor organizations, memberships and addresses
- [ ] Investor aftercare cases, interactions, tasks and inquiry conversion
- [ ] Act 1173 renewal rules after authoritative legal/process confirmation
- [ ] Certificates and anti-forgery QR verification
- [ ] Investor-to-business matchmaking
- [ ] ORC, GIS and payment integrations
- [ ] AI assistant with governed responses
- [ ] Broader internal automation and operational dashboards

## Verification

- [x] District registry test verifies 16 regions and 261 unique districts
- [x] Domain-model tests verify UUIDs, relationships, casts and event immutability
- [x] Public discovery tests verify visibility, filters, pagination and bounded queries
- [x] Administrative workflow tests verify permissions, transitions, SLA, audit and notifications
- [x] Investor onboarding tests verify registration, backfill, KYC gates, private storage, ownership, permissions, audit and decisions
- [x] Frontend production assets compile through Vite

## Data Dependencies

- [x] Spreadsheet district registry normalized to `database/data/ghana-districts.json`
- [x] Regional and district capitals retained at their correct hierarchy levels
- [ ] Authoritative district boundary GeoJSON must be confirmed by the client before map publication
- [ ] Exact workflow SLA durations and escalation recipients require client approval before production