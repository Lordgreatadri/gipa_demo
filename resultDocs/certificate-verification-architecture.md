# Certificates and Anti-Forgery Verification MVP

## 1. Purpose and source basis

This architecture defines Component 2 of the IOMP programme: a certificate registry and field-verification workflow for District Officers. It is based on:

- `pro-docs/IOMP_SYSTEM _POC.docx`
- `pro-docs/IOMP_Technical_Prototype_Document.docx`
- `dev-Docs/certificates-anti-forgery-verification.txt`
- the implemented Laravel prototype and its district, investor, workflow, audit, media, API and PWA conventions

The formal client documents require server-side RBAC, district-level restrictions, material action auditing, versioned APIs, secure private files, responsive/PWA behavior, bounded performance, and no indiscriminate caching of sensitive authenticated records. The certificate research brief adds QR-based lookup, cryptographic integrity, public verification, District Officer decisions, evidence, revocation, and offline field considerations.

This document began as the architecture gate. The online MVP described here is now implemented, except for the production/deferred gates explicitly listed below.

Implementation defaults selected by the technical lead for the MVP:

- Super Administrators approve temporal District Officer assignments.
- Reviewer / Approver users issue, suspend, reinstate and revoke certificates.
- The baseline type is `Investment Registration Certificate`; additional legal types remain configurable.
- RSA with SHA-256 is the implemented OpenSSL profile because it is portable across the current Windows/PHP and deployment toolchain.
- Local/UAT keys are provisioned outside `public/` with `php artisan certificates:key-generate`; production must use approved managed key custody.
- Public verification and session-authenticated officer inspection are online-first. Protected JWT APIs and offline synchronization remain gated.

## 2. MVP outcome

The MVP must provide a cryptographically verifiable certificate registry. A QR code is only a transport to a public verification URL; it is not evidence of authenticity by itself.

The MVP has two deliberately separate journeys:

1. Public verification exposes the minimum information needed to establish authenticity.
2. Authenticated field verification lets an assigned District Officer inspect full authorized details and append a verification decision with optional evidence and location.

A District Officer must never edit the issued certificate, its signed payload, or an existing verification. Corrections require a controlled supersession, suspension, revocation, or new certificate.

## 3. Confirmed scope

### 3.1 Included

- Certificate type reference data.
- Draft preparation and authorized issuance.
- Immutable issued payload snapshot.
- SHA-256 payload hash and asymmetric digital signature.
- Key identifier and signing algorithm recorded per certificate for key rotation.
- QR code containing only a public verification URL.
- Minimal public online verification page and versioned public API response.
- Certificate status handling: draft, active, expired, suspended, revoked, cancelled and superseded.
- District Officer registry/list and mobile-first certificate lookup.
- Append-only officer decisions: valid, suspicious or invalid.
- Separate system verdicts: authentic, signature-invalid, expired, suspended, revoked, cancelled or not-found.
- Optional notes, GPS coordinates and private photo/document evidence.
- Immutable certificate lifecycle events and verification records.
- Spatie activity logging for material actions.
- Dashboard counts and bounded, paginated work queues.
- Rate limiting and abuse-resistant not-found responses.

### 3.2 Deferred

- Offline claims of current validity.
- IndexedDB synchronization and offline submission queue.
- ORC, payment, GIS or external PKI integration.
- Automated image forensics, OCR or counterfeit classification.
- Bulk issuance and bulk revocation.
- Public verification of supporting evidence.
- Legal renewal rules or Act 1173 automation without authoritative confirmation.
- A production certificate authority or hardware security module integration.

The existing service worker excludes `/api` and authenticated/private paths. Offline field verification therefore requires a separate threat model and synchronization design. Until implemented, the product must state that verification requires an online check.

## 4. Domain boundaries

### 4.1 Certificate Registry

Owns certificate types, drafts, issuance, immutable signed payloads, current status projection, expiry, supersession, and lifecycle events.

### 4.2 Integrity and Signing

Canonicalizes a versioned payload, computes its hash, signs it, and verifies the stored signature. Signing keys never enter the database or source control.

### 4.3 Public Verification

Resolves an opaque public identifier, verifies signature integrity, derives the effective status, records privacy-safe telemetry, and returns a minimal response.

### 4.4 Field Verification

Authorizes an active staff member against explicit district assignments and permissions, then appends an officer decision. It cannot update the certificate payload.

### 4.5 Evidence and Audit

Stores evidence privately through MediaLibrary, records checksums and malware-scan state, and logs material actions without leaking certificate secrets or unnecessary personal data.

## 5. Normalized data model

All governed tables retain an internal numeric key and a UUID for external routing. Foreign keys use restrictive deletion unless a nullable historical actor is intentionally retained.

### 5.1 `staff_district_assignments`

This is preferred over adding `district_id` to `users`, because an officer may cover more than one district and assignment history is auditable.

- `id`, `uuid`
- `user_id`
- `district_id`
- `assigned_by`
- `starts_at`
- `ends_at` nullable
- `is_primary`
- timestamps

Indexes:

- unique `user_id, district_id, starts_at`
- `user_id, ends_at`
- `district_id, ends_at`

An assignment is active when `starts_at <= now` and `ends_at` is null or in the future. Roles grant capabilities; assignments constrain the records on which those capabilities operate.

### 5.2 `certificate_types`

- `id`, `uuid`
- unique `code`
- `name`, `description`
- `default_validity_months` nullable
- `is_active`, `sort_order`
- `created_by`, `updated_by`, timestamps

Types are reference data. Legal names, required fields, validity and renewal rules require client approval.

### 5.3 `certificates`

- `id`, `uuid`
- unique `certificate_number`
- unique random `public_token_hash`; the plaintext token appears only in the verification URL
- `certificate_type_id`
- `investor_profile_id` nullable
- `opportunity_id` nullable
- `district_id`
- `status` indexed
- `holder_name_snapshot`
- `organization_name_snapshot` nullable
- `project_name_snapshot` nullable
- `issued_at`, `expires_at` nullable
- `issued_by` nullable
- `canonicalization_version`
- `signed_payload` JSON
- `payload_hash` CHAR(64)
- `signature_algorithm`
- `signing_key_id`
- `digital_signature` text
- `supersedes_certificate_id` nullable
- `created_by`, `updated_by`, `version`, timestamps

The signed payload is a stable snapshot. Mutable investor, opportunity or district names must not be read live when checking the original signature. The payload contains stable public identifiers and the approved display snapshot, never internal auto-increment IDs or secret data.

`status`, revocation reason and mutable operational fields are not silently rewritten into the signed payload. Every status change is authorized and represented by an immutable lifecycle event. A corrected identity or project detail requires supersession and re-issuance.

Indexes:

- unique `certificate_number`
- unique `public_token_hash`
- `district_id, status, expires_at`
- `investor_profile_id, status`
- `certificate_type_id, status`
- `issued_at`

### 5.4 `certificate_lifecycle_events`

- `id`, `uuid`
- `certificate_id`
- `actor_id` nullable
- `action`: issued, suspended, reinstated, revoked, cancelled, superseded, expired
- `from_status`, `to_status`
- `reason` nullable
- `metadata` JSON with a schema version
- `occurred_at`

Events reject update and delete operations at the model layer. Database privileges and retention controls must also prevent ordinary application users from altering them.

### 5.5 `certificate_verifications`

Each scan/inspection creates a new append-only record.

- `id`, `uuid`
- unique `reference`
- `certificate_id`
- `officer_id`
- `district_id` snapshot
- `system_result`: authentic, signature_invalid, expired, suspended, revoked, cancelled
- `officer_decision`: valid, suspicious, invalid
- `notes` nullable
- `latitude`, `longitude` nullable
- `accuracy_metres` nullable
- `connectivity`: online or offline
- `registry_checked_at`
- `client_recorded_at` nullable
- unique `idempotency_key` nullable
- `ip_address` nullable
- `user_agent` nullable
- `created_at`

No `updated_at` is required because records are immutable. `district_id` is copied from the certificate to preserve the inspection context and support bounded district reports.

The system result and officer decision are separate. An officer cannot override a failed signature or revoked status by choosing `valid`.

Indexes:

- `certificate_id, created_at`
- `officer_id, created_at`
- `district_id, created_at`
- `system_result, created_at`
- `officer_decision, created_at`

### 5.6 Verification evidence

Use Spatie MediaLibrary on `CertificateVerification` with a single private collection such as `field_evidence`. Media records already provide normalized polymorphic file metadata, so a duplicate evidence-path table is unnecessary for the MVP.

Additional verification-level fields:

- evidence checksum SHA-256 in media custom properties
- malware scan status and timestamp
- original capture timestamp when trustworthy

Evidence is never public and is downloaded only through an authorized controller. Allowed types, maximum size, retention and malware-scanning infrastructure require client confirmation.

## 6. Certificate integrity

### 6.1 Canonical payload

A `CertificatePayloadCanonicalizer` must produce deterministic JSON from an allowlisted, ordered schema. The payload includes:

- schema/canonicalization version
- certificate UUID and certificate number
- certificate type code
- holder and organization snapshots
- opportunity/project public identifier and snapshot when present
- district UUID, code and name snapshot
- issue and expiry dates

It excludes current status, verification history, database numeric IDs, timestamps unrelated to issuance, and secret values.

### 6.2 Signing

The MVP uses PHP OpenSSL with RSA/SHA-256 and records `signing_key_id`, algorithm and signature with each certificate. The signing implementation rejects unsupported algorithm labels. A future managed signing service may introduce an approved RSA-PSS or ECDSA profile under a new key ID and canonicalization/signature compatibility test suite.

Configuration references key locations or a signing service; it never contains committed key material. Issuance fails closed when the active private key is unavailable. Public keys remain available by key ID so certificates issued with retired keys can still be verified.

Using `APP_KEY` as an HMAC signing key is rejected for this component because it couples certificate signatures to application encryption-key rotation and does not support independent public-key verification or clean custody separation.

### 6.3 Effective verification result

1. Resolve the token using its SHA-256 hash.
2. Recompute the canonical payload hash with the stored version.
3. Compare hashes using `hash_equals`.
4. Resolve the trusted public key by `signing_key_id`.
5. Verify the digital signature.
6. Derive current status, including expiry at request time.
7. Return the minimum authorized representation.

Unknown tokens and malformed tokens return the same public not-found response. Public responses must not reveal whether a sequential number exists.

## 7. Authorization model

Create certificate permissions:

- `certificates.view`
- `certificates.issue`
- `certificates.verify`
- `certificates.suspend`
- `certificates.revoke`
- `certificates.evidence.view`
- `certificates.audit.view`
- `certificates.reference-data.manage`

Proposed role defaults:

- Super Administrator: all permissions through the existing global gate override.
- Reviewer / Approver: view, issue, suspend and revoke.
- District Officer: view and verify, constrained to active district assignments.
- Field Agent: no certificate access by default until the client explicitly authorizes that role.
- Content / Data Manager: certificate type management only if approved.

Policies must enforce both permission and scope. List queries must be scoped in SQL before pagination; they must not load all certificates and filter in PHP.

A District Officer may view and verify a certificate only when:

- the user is active staff;
- the user has the required permission;
- an active assignment exists for the certificate district; and
- the certificate is in a state eligible for inspection.

Super Administrator access is still audited.

## 8. Workflows

### 8.1 Issuance

Draft -> issue -> active

Issuance runs in a transaction with a row lock. It validates the type, district, holder snapshot, dates and signing configuration; allocates a unique number; creates the signed payload; saves the certificate; appends an issued event; and writes an activity log. PDF/QR generation occurs after commit through a queued job.

### 8.2 Field verification

Lookup/scan -> server integrity check -> full authorized view -> officer decision -> optional evidence -> append verification -> audit

The submission uses an idempotency key to prevent duplicate records after mobile retries. The service locks the certificate long enough to derive a consistent system result, but it never changes the certificate payload or status.

Suggested decision constraints:

- `valid` is allowed only when system result is `authentic` and certificate status is active.
- `suspicious` is allowed for any found certificate and triggers review notification.
- `invalid` records a physical/document mismatch; it does not itself revoke the registry record.
- Signature invalidity always produces a security alert and cannot be downgraded by the officer.

### 8.3 Suspension, revocation and supersession

These are separate privileged actions requiring a reason. They update the current status projection under a row lock, increment `version`, append a lifecycle event, and write an activity log. Existing verification history remains untouched.

## 9. HTTP and API surfaces

### 9.1 Public

- `GET /c/{token}`: responsive minimal verification page.
- `GET /api/v1/certificates/{token}/verify`: minimal JSON, versioned and rate-limited.

Public fields:

- authenticity/effective result
- certificate number
- certificate type
- holder/organization display snapshot approved for public disclosure
- project snapshot when approved
- district
- issue and expiry dates
- online check timestamp

Do not expose internal IDs, officer data, notes, evidence, audit history, personal contact data or full investor KYC data.

### 9.2 Authenticated staff

- certificate overview and paginated registry
- district-scoped verification queue/list
- certificate detail and integrity result
- append verification
- private evidence download
- privileged issue/suspend/revoke actions

Controller routes remain cacheable and map directly to methods. Form Requests validate all writes. Policies and services repeat authorization at the controlling mutation boundary.

The TOR states JWT for protected APIs. The MVP can use session-authenticated web routes for the field UI, but a protected API must not be declared production-ready until the client approves and the maintained JWT/OAuth implementation is selected. JWT handling must not be handwritten.

## 10. Security and privacy controls

- Default-deny policies and server-side district scope.
- CSRF protection for web writes and deliberate token auth for protected APIs.
- HTTPS, secure cookies and approved security headers in production.
- Private evidence storage with randomized names and authorized streaming.
- MIME/content validation, size limits, checksums and quarantine before evidence is trusted.
- No private certificate payloads or authenticated API responses in the general service-worker cache.
- No signing keys, passwords, tokens or personal identifiers in logs.
- Rate limits for public verification, staff submissions and evidence uploads.
- Generic public not-found behavior to reduce enumeration.
- Idempotency for mobile verification submissions.
- Request correlation ID recorded in activity properties where available.
- Coordinates treated as sensitive operational data with explicit capture consent and retention.
- Signature failure creates a high-priority audit/security event.
- Dependency audit, SAST, DAST and penetration testing remain production release gates.

## 11. Performance and memory rules

- Always paginate registry and verification lists; use cursor pagination for high-write chronological queues.
- Select only columns used by each view or API resource.
- Eager-load only required relationships and constrain their selected columns.
- Scope by indexed district/status predicates in SQL.
- Build dashboard metrics with grouped or conditional aggregate queries, not one query per widget.
- Use `exists` checks for authorization scope rather than loading assignment collections.
- Stream private evidence downloads; never load file bytes into PHP memory.
- Queue PDF, QR, image conversion, notifications and bulk reports after transaction commit.
- Cache stable certificate types and public-key metadata; invalidate deliberately.
- Do not cache an active-status result longer than the approved revocation freshness window.
- Do not cache public not-found results for a long period.
- Keep documents in object/private storage, not database blobs.
- Add query-count tests for index/detail/dashboard surfaces and load tests for public verification.

The client target is at least 95% of agreed API traffic within two seconds. Concrete concurrency, volume, cache TTL, RPO/RTO and availability targets still require approval.

## 12. Offline design gate

Offline mode is a later slice and must never display `currently valid`. An offline result must say that it is cached, name `last synchronized at`, and show the age of the record.

Only records within an officer's active district assignments may be synchronized. Cached data must be minimal, encrypted where platform support allows, bounded by count/age, deleted when assignments end, and refreshed on reconnect. Offline submissions require local idempotency keys and conflict handling.

Before offline implementation, approve:

- maximum acceptable staleness;
- permitted cached fields;
- device enrollment and loss response;
- cache encryption strategy;
- assignment revocation behavior;
- evidence upload queue limits;
- whether an offline physical inspection may be recorded without making an authenticity claim.

## 13. Delivery slices and gates

### Slice 1: scope, assignments and domain foundation

- Confirm issuance authority, types, numbering, public fields and retention.
- Add certificate permission constants and role mappings.
- Add normalized staff district assignments and policy tests.
- Add certificate types, certificates, lifecycle events and relationships.
- Add immutable-model and database constraint tests.

Gate: a District Officer cannot query or open another district's certificate, and issued payload/lifecycle records cannot be edited through ordinary model operations.

### Slice 2: integrity and issuance

- Implement versioned canonicalizer and asymmetric signer/verifier.
- Add key-ID configuration and fail-closed startup/issuance checks.
- Implement transactional issuance and status services.
- Add known-answer, tamper, key-rotation and concurrency tests.

Gate: changing any signed field causes verification failure; old certificates remain verifiable after signing-key rotation.

### Slice 3: public verification

- Add opaque token generation and hashed lookup.
- Add minimal public page and `/api/v1` resource.
- Add rate limiting, privacy, enumeration and bounded-query tests.
- Generate QR linking to the public page.

Gate: the endpoint reveals only approved fields and reliably distinguishes authentic status internally without exposing lookup details.

### Slice 4: District Officer field workflow

- Add mobile-first lookup/scan entry and district-scoped registry.
- Add append-only verification service and idempotency.
- Add GPS/notes with validation.
- Add suspicious/signature-failure notifications and activity audit.

Gate: retries do not duplicate verification records, and no officer action mutates the signed certificate.

### Slice 5: evidence and certificate PDF

- Add quarantined private evidence through MediaLibrary.
- Add authorized evidence streaming and malware-scan boundary.
- Generate PDF and QR asynchronously with Dompdf and an approved QR package.
- Add storage failure cleanup and memory tests.

Gate: evidence and PDFs are inaccessible without authorization; failed jobs do not produce partially issued records.

### Slice 6: dashboards, operations and hardening

- Add bounded aggregate reports and expiry/revocation monitoring.
- Add retention/export controls when approved.
- Run accessibility, dependency, security and load checks.
- Design offline synchronization only after the offline gate decisions are approved.

## 14. Client decisions required before Slice 2

1. Which institution and roles may issue, suspend, revoke and reinstate certificates?
2. What certificate types, required fields, numbering formats and validity periods apply?
3. Is the holder always an onboarded investor, or may a business/organization hold a certificate independently?
4. Which holder/project fields may be disclosed publicly?
5. Can one District Officer cover multiple districts, and who approves assignments?
6. Must verification evidence be mandatory for suspicious/invalid decisions?
7. What file types, maximum sizes, malware scanner, retention period and export rules apply?
8. Is GPS mandatory, optional, or prohibited for any workflow?
9. Is JWT contractually mandatory for the protected field API, and which maintained implementation is approved?
10. Who owns signing keys, where are they stored, which algorithm is approved, and what is the rotation/recovery procedure?
11. What revocation propagation and offline-staleness windows are acceptable?
12. What audit retention, data residency, backup RPO/RTO and incident-response rules apply?
13. Must the MVP integrate with an existing certificate registry or import historical certificates?
14. Are renewal and Act 1173 rules in scope, and what authoritative legal/process source governs them?

## 15. Initial acceptance scenarios

- Public scan of an authentic active certificate shows a minimal verified result and online timestamp.
- Unknown and malformed tokens return the same public response.
- A modified signed payload returns signature/hash failure.
- An expired, suspended, revoked or cancelled certificate never displays as currently valid.
- A District Officer can inspect and append a decision only within an active district assignment.
- A District Officer cannot edit a certificate or prior verification.
- A suspicious or signature-invalid result creates an immutable verification, activity audit and alert.
- Repeating a mobile submission with the same idempotency key creates one verification.
- Evidence remains private and streams only to authorized users.
- Registry and history pages remain paginated and within explicit query-count budgets.
- Key rotation preserves verification of certificates signed by a retired trusted key.
- Offline UI, when introduced, clearly states the cached timestamp and never claims current validity.
