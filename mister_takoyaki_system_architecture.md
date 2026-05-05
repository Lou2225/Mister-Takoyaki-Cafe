# Enterprise System Architecture: Mister Takoyaki Cafe Ecosystem

**System Tier:** Enterprise Grade Business Management 
**Architecture:** TALL Stack Distributed SPA (Single Page Application)
**Compliance:** Audit-Ready, Multi-Tenant Batch Tracking, FEFO/FIFO Inventory Logic

---

## 1. Executive Summary

The **Mister Takoyaki Cafe Ecosystem** is an enterprise-grade specialized ERP designed for high-volume fast-food operations. Unlike traditional POS systems, it integrates a sophisticated **Menu Recipe Core** linked directly to a **Batch-Level Inventory Engine**, ensuring milligram-precise COGS (Cost of Goods Sold) tracking and expiration management across multiple geographical branches.

---

## 2. Professional Technology Stack

The platform is engineered using the **TALL Stack** to achieve sub-second responsiveness and a unified, premium SaaS aesthetic.

| Layer | Technology | Primary Role |
| :--- | :--- | :--- |
| **Foundation** | Laravel 10.x | Industrial-strength PHP framework for security and scalability. |
| **Reactive UI** | Livewire 2.x | Server-driven SPA architecture for zero-refresh state management. |
| **Interactivity** | Alpine.js | Micro-interactions and localized client state. |
| **Aesthetics** | Tailwind CSS | Modern, utility-first styling for premium interface design. |
| **Storage** | MySQL 8.x | High-performance relational data mapping via Eloquent ORM. |

---

## 3. High-Level Logic Architecture

### 3.1 Tiered Entity Mapping
The system operates on a global/branch hierarchy:
- **Central Catalog (Super Admin):** Manages the global dictionary of ingredients and menu items.
- **Branch Context (Branch Manager):** Scoped view of physical stock, sales performance, and localized telemetry.

### 3.2 Automated Recipe Engine
Every sale at the POS triggers the **Deduction Engine**:
1. Queries the `Recipe` matrix for the sold product.
2. Identifies required ingredients in grams/ml/pcs.
3. Automatically executes **FEFO (First-Expiry-First-Out)** logic on the `StockBatch` table.
4. Updates `BranchIngredientStock` aggregate totals in real-time.

---

## 4. Organizational Roles (RBAC)

| Role | Access Scope | Key Features |
| :--- | :--- | :--- |
| **Super Admin** | Global Ecosystem | Branch Setup, Global Menu/Recipe Definition, Total Financial Oversight. |
| **Branch Manager** | Branch Operations | Inventory Adjustments, Local Staffing, Branch KPIs. |
| **Cashier** | POS Operations | Order Processing, Multi-Method Payments, Receipting. |
| **Kitchen Staff** | Production (KDS) | Order Serialization, Preparation Timers, Ticket Status Flow. |

---

## 5. Enterprise Modules

### 5.1 Intelligence Dashboard
Real-time KPI visualizations including **Consumption Velocity**, **Waste Variance**, and **Expiring-Soon Alerts (7-day window)**.

### 5.2 Stock & Batch Management
A multi-pillar inventory system supporting:
- **Ingredient Categorization:** Grouping items for cleaner procurement reporting.
- **Batch Tracking:** Unique IDs for deliveries linked to Expiry Dates.
- **Multi-Branch Mapping:** Centralized creation and broadcasting of ingredients to select branches.

### 5.3 Menu & Recipe Matrix
Full-scale product lifecycle management. Allows linking products to multiple ingredients with precise quantities, enabling automated COGS calculation and stock depletion.

### 5.4 POS & KDS (Hybrid SPA)
Low-latency terminals for ordering and production, ensuring no communication gap between the front-of-house and kitchen.

---

## 6. Database Schema Design (Enterprise View)

- **`ingredients`**: Base raw materials dictionary.
- **`ingredient_categories`**: Logical grouping for procurement (e.g., Seafood, Packaging).
- **`products`**: Menu items with pricing and status.
- **`recipes`**: The "Bridge" mapping products to ingredient deductions.
- **`stock_batches`**: The ledger for perishable goods tracking (Expiry/Batch ID).
- **`stock_movements`**: immutable audit trail for every single unit modification.
- **`branch_ingredient_stocks`**: The materialized view of current branch availability.

---

## 7. Compliance & Auditability

Every critical operation (Stock Adjustment, Order Creation, Batch Receiving) is logged with:
- `user_id`: Accountability for the transaction.
- `timestamp`: Precise timing for forensic audit.
- `remarks`: Human-readable context for variance.

---

**© 2026 Mister Takoyaki Cafe Management Systems**
