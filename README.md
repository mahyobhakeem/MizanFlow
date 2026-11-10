# MizanFlow — Multi-Warehouse Inventory, Procurement & Sales Engine

[![CI Tests](https://github.com/mahyobhakeem/MizanFlow/actions/workflows/tests.yml/badge.svg)](https://github.com/mahyobhakeem/MizanFlow/actions/workflows/tests.yml)
[![Release](https://img.shields.io/badge/Release-v1.0.0-0d6efd.svg?style=flat-square&logo=github)](https://github.com/mahyobhakeem/MizanFlow/releases/tag/v1.0.0)
[![Automated Tests](https://img.shields.io/badge/Tests-108%20Passed%20%7C%20460%20Assertions-10b981.svg?style=flat-square)](docs/TESTING.md)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-ff2d20.svg?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-777bb4.svg?style=flat-square&logo=php)](https://www.php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%208.4%20LTS%20%2F%20SQLite-00758f.svg?style=flat-square&logo=mysql)](https://www.mysql.com)
[![Code Style](https://img.shields.io/badge/Code%20Style-PSR--12%20(Pint)-6f42c1.svg?style=flat-square)](https://laravel.com/docs/pint)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

**MizanFlow** is a production-grade commercial inventory, procurement, and point-of-sale management system engineered for single-enterprise commercial businesses operating across multiple regional warehouses and multi-tiered operational roles.

Built with a standard **Laravel 13** backend,**MySQL 8.4 LTS**, **Bootstrap 5 RTL**, and **Readex Pro & Plus Jakarta Sans typography**, MizanFlow delivers real-time stock ledger synchronization, strict concurrency protection, and tamper-resistant financial calculations.

---

## 💼 Business Value for Commercial Clients

Traditional inventory and point-of-sale systems frequently break down under multi-warehouse workloads, causing unfulfillable orders, inventory discrepancies, and margin inaccuracies. MizanFlow solves these challenges directly:

- **Eliminates Negative Stock & Overselling:** Prevents race conditions when multiple cashiers sell the last available unit simultaneously through database row-level locking.
- **Accurate Profit Margins (Moving WAC):** Real-time recalculation of Weighted Average Cost upon each purchase receipt ensures profit margin calculations reflect actual historical purchase costs rather than fluctuating market estimates.
- **Automated Customer Credit Control:** Credit limit checks actively prevent sales staff from extending credit beyond assigned limits, reducing bad debt exposure.
- **Multi-Warehouse Logistics Visibility:** A two-phase transfer pipeline (`ship` $\rightarrow$ `in_transit` $\rightarrow$ `receive`) prevents stock from disappearing or duplicating during transit between regional facilities.
- **Role-Partitioned Financial Privacy:** Cashiers can draft and complete sales at lightning speed while wholesale costs, margins, and procurement data remain strictly masked.
- **Tamper-Resistant Non-Destructive Accounting:** Approved commercial transactions cannot be silently deleted or edited. Reversals and voiding occur through atomic, double-entry audit movements.
- **Modern Arabic RTL First-Class UX:** Purpose-built Arabic enterprise interface with full RTL typography, responsive mobile drawers, and print-optimized simplified tax invoices (`@media print`).

---

## 🛠️ Engineering & Architecture Highlights

MizanFlow is architected according to enterprise-grade software standards, prioritizing mathematical precision, concurrency safety, and maintainability:

```
                       ┌─────────────────────────────────────────┐
                       │           Blade + Bootstrap 5           │
                       │           Cairo Font RTL UI             │
                       └────────────────────┬────────────────────┘
                                            │ HTTP / Inertia-Free
                       ┌────────────────────▼────────────────────┐
                       │         Laravel 13 HTTP Layer           │
                       │   Middleware (auth, role, CSRF, rate)   │
                       └────────────────────┬────────────────────┘
                                            │
                       ┌────────────────────▼────────────────────┐
                       │     Actions & Domain Services Layer     │
                       │ (ApprovePurchase, CompleteSale, WAC...) │
                       └────────────────────┬────────────────────┘
                                            │ Atomic Transactions + lockForUpdate
                       ┌────────────────────▼────────────────────┐
                       │           Eloquent Data Layer           │
                       │       (Immutable Snapshots & Audit)     │
                       └────────────────────┬────────────────────┘
                                            │
                       ┌────────────────────▼────────────────────┐
                       │        MySQL 8.4 LTS (InnoDB Engine)    │
                       │   DECIMAL(15, 4) Costs / (12, 3) Qty    │
                       └─────────────────────────────────────────┘
```

### 1. Concurrency Control & ACID Transaction Integrity
Critical business operations (`CompleteSaleAction`, `ApprovePurchaseAction`, `StockTransferAction`, and `VoidSaleAction`) execute within atomic `DB::transaction()` blocks. Pessimistic row-level locking (`SELECT ... FOR UPDATE` via `lockForUpdate()`) serializes concurrent access to stock records, mathematically eliminating race conditions and negative inventory.

### 2. High Financial Decimal Precision
To protect against IEEE 754 floating-point inaccuracies:
- **Cost & Price Fields:** Stored as `DECIMAL(15, 4)` for high-precision valuation and tax calculation.
- **Quantity Fields:** Stored as `DECIMAL(12, 3)` to support fractional, weighed, or metered units of measurement (`allow_decimal`).

### 3. Moving Weighted Average Cost (Moving WAC)
Inventory valuation updates in real-time per product per warehouse upon each purchase receipt:
$$\text{WAC}_{\text{new}} = \frac{(\text{Current Qty} \times \text{Current WAC}) + (\text{Incoming Qty} \times \text{Incoming Cost})}{\text{Current Qty} + \text{Incoming Qty}}$$

### 4. Immutable Historical Cost Snapshots
Upon sale approval, the active WAC is permanently captured in `SaleItem::unit_cost_snapshot`. Even if subsequent procurement alters the product's average cost, historical COGS and profit margin analytics remain immutable.

### 5. Multi-Tier Role-Based Access Control (RBAC)
Dedicated server-side policy gating and route middleware partition operational authority across three roles:
- **Administrator:** Full enterprise governance, user management, and security audit logs.
- **Operations Manager:** Warehouse operations, purchase approvals, stock transfers, sale voiding, alerts, and analytical reports.
- **Cashier:** POS sales drafting and checkout; cost prices, margins, purchase orders, and audit logs are masked.

### 6. Automated Quality Assurance & CI/CD
- **108 Automated Tests:** Comprehensive suite covering unit logic, feature workflows, concurrency race conditions, and end-to-end commercial scenarios with 100% pass rate.
- **GitHub Actions CI:** Automated continuous integration pipeline running on every push and pull request.
- **PSR-12 Standard:** Automated linting via Laravel Pint.

---

## 📸 Screenshot Gallery

All screenshots below showcase the live, verified application running in production styling:

### Core Commercial Workflow

| Screen | Description | Link |
| :--- | :--- | :--- |
| **01. Login Screen** | RTL authentication interface with one-click demo credentials quick-fill. | [View Screenshot](docs/screenshots/01_login_desktop.png) |
| **02. Executive Dashboard** | Real-time sales metrics, inventory valuation, gross profit, and branch indicators. | [View Screenshot](docs/screenshots/02_dashboard_desktop.png) |
| **03. Mobile Responsive View** | Responsive layout tested on 375×812 viewport with zero horizontal overflow. | [View Screenshot](docs/screenshots/03_dashboard_mobile.png) |
| **04. Products Catalog** | Master inventory directory with real-time search, category filters, and reorder levels. | [View Screenshot](docs/screenshots/04_products_desktop.png) |
| **05. Sales Invoices** | Invoice index with payment badges, customer breakdown, and status tags. | [View Screenshot](docs/screenshots/05_sales_list_desktop.png) |
| **06. Tax Invoice View** | Simplified tax invoice with item snapshots and customer credit balance breakdown. | [View Screenshot](docs/screenshots/06_sales_invoice_desktop.png) |
| **07. Invoice Print Mode** | CSS `@media print` layout hiding navigation and buttons for receipt printers. | [View Screenshot](docs/screenshots/07_sales_invoice_print.png) |
| **08. Analytical Sales Report** | COGS, net sales, profit margins, and top revenue-generating products ranking. | [View Screenshot](docs/screenshots/08_reports_sales_desktop.png) |
| **09. Alerts Center** | Operational monitoring for low stock, credit limits exceeding 85%, and overdue sales. | [View Screenshot](docs/screenshots/09_alerts_desktop.png) |
| **10. Audit & Security Trail** | System-wide immutable log tracking authenticated actions with sanitized metadata. | [View Screenshot](docs/screenshots/10_audit_logs_desktop.png) |

### Navigation & UX Architecture

| Screen | Description | Link |
| :--- | :--- | :--- |
| **Sidebar: Collapsed Groups** | Clean accordion layout with closed groups on initial dashboard entry. | [View Screenshot](docs/screenshots/sidebar_01_dashboard_collapsed.png) |
| **Sidebar: Sales Expanded** | Smooth one-group-at-a-time expansion with animated chevron indicator. | [View Screenshot](docs/screenshots/sidebar_02_sales_expanded.png) |
| **Sidebar: Inventory Expanded** | Accordion switching: previous group collapses as new group opens. | [View Screenshot](docs/screenshots/sidebar_03_inventory_expanded.png) |
| **Sidebar: Auto-Expansion** | Deep route navigation (`/products`) automatically activates and expands parent. | [View Screenshot](docs/screenshots/sidebar_04_products_auto_expanded.png) |
| **Sidebar: Reports Expansion** | Reports child route (`/reports/sales`) auto-expands parent with active highlight. | [View Screenshot](docs/screenshots/sidebar_05_reports_auto_expanded.png) |
| **Sidebar: Cashier Restricted** | Role-gated navigation: strictly masks purchases, reports, transfers, and audit logs. | [View Screenshot](docs/screenshots/sidebar_06_cashier_dashboard.png) |
| **Mobile Drawer Open** | 375px mobile viewport with touch offcanvas drawer and dismissible backdrop. | [View Screenshot](docs/screenshots/sidebar_07_mobile_drawer_open.png) |

---

## ⚡ Quick Start & Local Evaluation

Experience MizanFlow locally in under 3 minutes:

### Prerequisites
- **PHP:** 8.3 or 8.4 with extensions: `pdo_sqlite` (or `pdo_mysql`), `bcmath`, `mbstring`, `intl`, `curl`.
- **Composer:** 2.6+.
- **Node.js:** 20+ and npm.

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mahyobhakeem/MizanFlow.git
   cd MizanFlow
   ```

2. **Install PHP and Node dependencies:**
   ```bash
   composer install
   npm install --ignore-scripts
   ```

3. **Configure Environment:**
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

   > [!TIP]
   > **Instant Zero-Config Evaluation (SQLite):**
   > By default, you can run migrations on SQLite immediately without configuring MySQL:
   > ```ini
   > DB_CONNECTION=sqlite
   > DB_DATABASE=database/database.sqlite
   > ```
   > Create the database file via `type nul > database/database.sqlite` (Windows) or `touch database/database.sqlite` (Linux/macOS).

4. **Run Migrations & Seed Demo Data:**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

5. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

6. **Start Local Development Server:**
   ```bash
   php artisan serve --port=8000
   ```
   Open **`http://127.0.0.1:8000`** in your browser.

---

## 🔑 Demo Accounts & Operational Roles

The seed data provisions three pre-configured operational roles. On the login screen, convenient quick-fill buttons allow instant switching between roles:

| Role | Username | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@mizanflow.test` | `password123` | Full unrestricted governance, user management, and audit logs. |
| **Operations Manager** | `manager` | `manager@mizanflow.test` | `password123` | Procurement approvals, stock transfers, sale voiding, alerts, and analytics. |
| **Cashier / POS** | `cashier` | `cashier@mizanflow.test` | `password123` | POS sales order drafting and completion. Cost prices and margins masked. |

---

## 🧪 Automated Testing

Run the automated test suite locally:

```powershell
php artisan test
```

*Executes all 108 Unit and Feature tests with 460 assertions in ~8.5 seconds.*

To run the end-to-end commercial scenario test:
```powershell
php artisan test --filter=test_full_end_to_end_business_scenario
```

To run code formatting check:
```powershell
.\vendor\bin\pint --test
```

---

## 📚 Technical Documentation Index

Detailed architectural and operational documentation is available in the [`docs/`](docs/) directory:

- 📖 [User Guide (دليل المستخدم العربي)](docs/USER_GUIDE.md) — Comprehensive operational user manual.
- 🏗️ [Architecture Overview](docs/ARCHITECTURE.md) — System boundaries, data flow, and domain design.
- 🗄️ [Data Model & Schema](docs/DATA_MODEL.md) — Table definitions, constraints, indexes, and relationships.
- 🧪 [Testing Handbook](docs/TESTING.md) — Automated testing architecture and test scenarios.
- 📋 [QA Test Plan](docs/QA_TEST_PLAN.md) — Verification matrix and acceptance criteria.
- 🚀 [Deployment Guide](docs/DEPLOYMENT.md) — Production deployment with Nginx, PHP-FPM, and Supervisor.
- 🗺️ [Architecture Decisions (ADRs)](docs/DECISIONS.md) — Technical choices, trade-offs, and design records.
- 🔒 [Security Policy](SECURITY.md) — Security controls, vulnerability reporting, and threat model.

---

## 📄 License

MizanFlow is open-source software licensed under the [MIT License](LICENSE).
