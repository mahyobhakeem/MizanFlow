# Security Policy — MizanFlow

## Supported Versions

Only the current Release Candidate and stable releases receive security updates.

| Version | Supported          |
| :---    | :---:              |
| 1.0-RC1 | :white_check_mark: |
| < 1.0   | :x:                |

---

## Reporting a Vulnerability

We take the security of MizanFlow seriously. If you discover a security vulnerability, please follow responsible disclosure principles:

1. **Do not open a public issue.**
2. Send a detailed report to the security team or system administrator.
3. Include:
   - Type of vulnerability (e.g., SQL injection, XSS, CSRF, privilege escalation).
   - Step-by-step reproduction instructions or proof-of-concept payload.
   - The affected components, routes, or files.
   - Any proposed remediation or mitigation.
4. You will receive an acknowledgment within 48 hours, followed by a status update and CVE assessment if applicable.

---

## Implemented Security Controls

MizanFlow is engineered with defense-in-depth principles to safeguard commercial, financial, and inventory data:

### 1. Authentication & Session Security
- **Bcrypt Password Hashing:** Uses `BCRYPT_ROUNDS=12` in production for high-cost key derivation resistant to brute-force attacks.
- **Session Fixation Defense:** Calls `request()->session()->regenerate()` immediately upon successful login.
- **Database Session Storage:** Sessions are persisted in the database with configurable encryption (`SESSION_ENCRYPT=true`).
- **Brute-Force Rate Limiting:** Login requests are throttled per username and IP address using Laravel's rate limiting.

### 2. Role-Based Access Control (RBAC) & Least Privilege
- **Three-Tier Role Hierarchy:** `admin`, `manager`, `cashier`.
- **Server-Side Enforcement:** Permissions are enforced via middleware (`CheckRole`), Policies (`SalePolicy`, `PurchasePolicy`), and Controller gates. Hiding UI elements is strictly treated as a visual convenience, never as an authorization boundary.
- **Financial Partitioning:** Cost of Goods Sold (COGS), profit margins, warehouse asset valuations, supplier payables, and customer debt exposure are strictly restricted to `admin` and `manager`. Cashiers receive zero financial metrics on their dashboard or POS screens.

### 3. Cross-Site Request Forgery (CSRF) Protection
- **Universal Token Verification:** Laravel's `ValidateCsrfToken` middleware is active across all web routes.
- **Strict Form Tokenization:** 100% of POST, PUT, and DELETE forms throughout the application include `@csrf`.

### 4. Cross-Site Scripting (XSS) Prevention
- **Automatic Output Encoding:** All Blade views use `{{ $variable }}` which automatically executes PHP `htmlspecialchars()` with UTF-8 encoding.
- **Zero Unescaped Directives:** The raw `{!! !!}` directive is strictly prohibited and verified to be absent (0 occurrences across all views).

### 5. SQL Injection Prevention
- **Prepared Statements:** 100% of database queries utilize Eloquent ORM or the query builder with PDO parameter binding (`?` placeholders).
- **Audit of Raw Expressions:** All instances of `DB::raw` and `whereRaw` (12 occurrences in total) are confined to static column arithmetic (e.g., `quantity * unit_cost_snapshot`) and contain zero concatenated user inputs.

### 6. Concurrency & Inventory Race Condition Protection
- **Pessimistic Row-Level Locking:** High-concurrency operations (such as sale completion, purchase posting, and stock transfers) utilize `ProductWarehouse::lockForUpdate()` within atomic database transactions (`DB::transaction()`).
- **Zero Negative Inventory Invariant:** Stock deduction logic verifies available quantities while holding the pessimistic lock, mathematically preventing negative inventory even under concurrent requests.
- **Idempotency Protection:** Commercial actions check document states (`isDraft()`) before execution, throwing exceptions if an already-completed invoice is submitted again.

### 7. Audit Logging & Sensitive Data Sanitization
- **Immutable Audit Trail:** Critical events (logins, sales completion, voids, purchase approvals, transfers) are permanently recorded in `audit_logs`.
- **Recursive Sanitizer:** The `AuditService::sanitize()` method recursively scrubs sensitive keys (including `password`, `secret`, `token`, `api_key`, `cvv`, `card`) and safely truncates descriptions to 500 characters to prevent strict-mode database errors.

### 8. Production Error Hardening
- **Zero Stack Trace Leaks:** Custom Arabic error pages (`403.blade.php`, `404.blade.php`, `500.blade.php`) are pre-rendered for production environments (`APP_DEBUG=false`), ensuring framework internals and environment secrets are never exposed to clients.

### 9. Dependency Vulnerability Audits
- Continuous auditing via `composer audit` and `npm audit` before every release candidate.
- Current release: **0 known vulnerabilities** in PHP and Node.js dependencies.
