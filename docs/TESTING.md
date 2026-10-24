# Technical Testing Handbook — MizanFlow

This document details the automated testing architecture, execution workflows, and quality assurance procedures for the **MizanFlow** application.

---

## 1. Test Architecture & Overview

MizanFlow employs a multi-layered verification strategy combining automated unit/feature tests, database transaction isolation, dual-engine DBMS validation, PSR-12 code style linting, front-end build verification, and headless browser automation via Chrome DevTools Protocol (CDP).

### Test Suite Metrics (Current Release: 1.0-RC1)
- **Total Test Cases:** **108 tests**
- **Total Assertions:** **456 assertions**
- **Pass Rate:** **100% (0 errors, 0 failures, 0 skipped)**
- **Test Execution Duration:** ~8.5s (SQLite In-Memory) / ~20.4s (MySQL 8.4 LTS)

---

## 2. Test Catalog & Coverage Matrix

| Test Suite | Class Path | Tests | Assertions | Core Scenarios Covered |
| :--- | :--- | :---: | :---: | :--- |
| **Authentication & Session** | `tests/Feature/AuthenticationTest.php` | 9 | 31 | Login validation, Bcrypt verification, session fixation protection (`session()->regenerate()`), remember-me token handling, logout session clearance, inactive user login blocking. |
| **Master Data & Authorization** | `tests/Feature/MasterDataTest.php` | 18 | 61 | CRUD operations for Categories, Units, Products (SKU & Barcode unique constraints), Warehouses, Customers (credit limit enforcement), Suppliers; pagination and server-side search filtering. |
| **Procurement & Inventory** | `tests/Feature/ProcurementAndInventoryTest.php` | 24 | 96 | Purchase order drafting, atomic approval workflow, Moving Weighted Average Cost (Moving WAC) calculation, double-entry stock movement ledger, inter-warehouse transfers (draft $\rightarrow$ ship $\rightarrow$ receive), in-transit quantity protection. |
| **Sales & POS Workflow** | `tests/Feature/SalesWorkflowTest.php` | 18 | 81 | Multi-item sales orders, server-side price/tax recalculation (tamper resistance), atomic stock decrement with pessimistic locking (`lockForUpdate`), credit limit validation, split cash/credit payments, simplified tax invoice rendering, administrative voiding/reversal (`VoidSaleAction`). |
| **Dashboard, Reports & Alerts** | `tests/Feature/DashboardAndReportsTest.php` | 30 | 123 | Real-time zero-dummy SQL aggregations, gross profit calculation `(Subtotal - Discount) - COGS`, cashier financial masking (zero cost disclosure), inventory valuation report, ledger movements report, customer debt aging, threshold alerts (low stock, credit limit, overdue invoices), sanitized audit logs. |
| **Release Candidate Audit** | `tests/Feature/ReleaseCandidateAuditTest.php` | 8 | 64 | End-to-end commercial cycle, insufficient stock prevention, idempotency (double-approval prevention), atomic rollback on mid-transaction item failure, full role-permission matrix across all routes, historic cost snapshot immutability, empty/negative payload rejection, competing simultaneous transactions race condition test. |

---

## 3. Dual-Engine Database Testing

To guarantee that the application behaves identically in development and production environments, MizanFlow provides automated test execution against both **SQLite In-Memory** and actual **MySQL 8.4 LTS**.

### A. Testing on SQLite In-Memory (Fast Suite)
Used for rapid developer feedback and continuous integration pipelines:
```powershell
php artisan test
```
*Configured in `phpunit.xml`:*
- Uses `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
- Fast execution time (~8 to 12 seconds).
- Completely isolated in RAM; never writes to disk or modifies local databases.

### B. Testing on MySQL 8.4 LTS (Production Parity Suite)
Used to verify enterprise database engine behavior, strict SQL modes, InnoDB row-level locking, and exact decimal precision:
```powershell
.\vendor\bin\phpunit -c phpunit.mysql.xml
```
*Configured in `phpunit.mysql.xml`:*
- Target database: `mizanflow_testing` on `127.0.0.1:3306`.
- Uses Laravel's `RefreshDatabase` trait: executes database migrations once at suite startup, and wraps every individual test in an atomic transaction (`BEGIN ... ROLLBACK`).
- Verifies InnoDB row-level pessimistic locking (`SELECT ... FOR UPDATE`), `DECIMAL(15, 4)` and `DECIMAL(12, 3)` field precision, and foreign key cascades/restrictions (`restrictOnDelete`).

---

## 4. Code Formatting & Static Analysis

MizanFlow enforces Laravel's official styling standards (PSR-12) via **Laravel Pint**:

- **Check code formatting without modifying files:**
  ```powershell
  .\vendor\bin\pint --test
  ```
- **Automatically format and fix any code style discrepancies:**
  ```powershell
  .\vendor\bin\pint
  ```

---

## 5. Front-End Asset Pipeline Verification

All client-side assets (CSS, JS, Cairo font subsets, Bootstrap 5 RTL, Bootstrap Icons) are compiled and bundled using **Vite**:

```powershell
npm run build
```
*Verification criteria:*
- Zero compilation errors.
- Output generated in `public/build/assets/`.
- Manifest file generated at `public/build/manifest.json`.

---

## 6. Real Browser Automation & UI Audit (Chrome DevTools Protocol)

Rather than relying on mocked headless tools, MizanFlow includes an automated browser test script (`docs/reviews/browser_audit.js`) that drives a real **Google Chrome** instance via the Chrome DevTools Protocol (CDP):

### How to Run Browser Automation:
1. Ensure the local Laravel server is active:
   ```powershell
   php artisan serve --port=8000
   ```
2. In a separate terminal, run the Node.js audit script:
   ```powershell
   node docs/reviews/browser_audit.js
   ```

### Automated Verifications Performed by CDP:
1. **Console Error Detection:** Listens to `Runtime.exceptionThrown` and `Runtime.consoleAPICalled` across all pages. Result: **0 JavaScript errors**.
2. **Typography & RTL Check:** Verifies `dir="rtl"`, `lang="ar"`, and Cairo font computed family application.
3. **Responsive Viewport Audit:**
   - Desktop view: 1440 × 900 px.
   - Mobile view: 375 × 812 px.
   - Horizontal overflow check: `document.documentElement.scrollWidth > window.innerWidth` $\rightarrow$ **false** (zero overflow).
4. **Print Media Simulation:** Tests `@media print` emulation on simplified tax invoice views.
5. **Real Screenshot Capture:** Automatically captures and persists 10 high-resolution PNG screenshots into `docs/reviews/screenshots/`.

---

## 7. Adding New Tests

When contributing new features or bug fixes to MizanFlow:
1. Every new commercial feature must be accompanied by a Feature Test in `tests/Feature/`.
2. Wrap business operations in database transactions or use `RefreshDatabase`.
3. Assert both positive outcomes (HTTP 200/302, database records, ledger entries) and negative edge cases (HTTP 403, 422, insufficient stock exceptions).
4. Run `.\vendor\bin\pint --test` and `php artisan test` before committing changes.
