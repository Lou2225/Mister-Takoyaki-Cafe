# Mister Takoyaki Centralized Sales and Management System
## User Manual — Version 1.4 (September 2026)

| Field | Value |
|---|---|
| Document | Mister Takoyaki User Manual |
| System | Centralized Sales and Management System |
| Version | 1.4 |
| Status | Final |
| Prepared By | Development Team |
| Approved By | Management |

---

## Quick Start

**Who This Manual Is For:** Authorized Super Admins, Administrators, Cashiers, and Riders. The system centralizes POS, KDS, inventory, branch requests, reviews, reporting, and mobile delivery workflows.

| Step | Task | What to Do |
|---|---|---|
| 1 | Log in | Open the system URL, enter your registered email and password, then click Login. |
| 2 | Check Dashboard | Review sales, inventory alerts, live orders, and operational KPIs. |
| 3 | Process an order | Open POS, choose items and options, review the cart, select order type, take payment, then click Place Order. |
| 4 | Prepare orders | Use KDS, open the Processing Queue, mark tickets as Ready, then serve or hand to a rider. |
| 5 | Monitor stock | Open Stock Management, review batches and health badges, then adjust or request supplies when needed. |

> [!NOTE]
> **Role Scoping:** What users see is automatically scoped by their role and assigned branch. Super Admin has global access, Admin manages a specific branch, Cashier handles front-of-house operations, and Rider receives delivery assignments through the Rider app.

---

## Table of Contents

1. Introduction
2. System Overview
3. Accessing the System
4. User Roles and Permissions
5. Dashboard
6. POS and Orders
7. Order Management
8. Kitchen Display System (KDS)
9. Menu Management
10. Options Library Management
11. Category Management
12. Stock and Inventory Management
13. Stock Adjustments and Physical Count Reconciliation
14. Branch Stock Ordering and Inter-Branch Transfers
15. Branch Management
16. User and Staff Management
17. Customer Feedback and Reviews
18. Business Reports and Intelligence
19. System Settings and Platform Configuration
20. Notifications and Alert Management
21. Profile Settings

Appendix A — Mobile Ecosystem  
Appendix B — Troubleshooting  
Appendix C — Frequently Asked Questions  
Appendix D — Glossary

---

## 1. Introduction

### 1.1 Purpose

This User Manual provides comprehensive instructions for using the Mister Takoyaki Centralized Sales and Management System. It is written to guide all authorized personnel — from the business owner to front-line cashiers — in performing day-to-day transactions, managing multi-tier inventory, monitoring kitchen workflows, assigning delivery riders, and interpreting business intelligence reports.

### 1.2 Scope

The system provides centralized control of the following:

- Point-of-sale transactions (Cash and Static QR GCash)
- Dynamic, real-time kitchen displays (KDS)
- Multi-tier ingredient tracking with bulk-to-base-unit conversions
- Branch-to-branch stock ordering with approval workflows
- Rider dispatch and proof-of-delivery logistics
- Configurable customer review collection
- Predictive business analytics using weighted linear regression

### 1.3 Intended Users

| Role | Description |
|---|---|
| Super Admin (Owner) | Absolute access to all system modules: global menu, category and options-library configuration, all branch data, forecasting, and all system settings. |
| Administrator (Branch Manager) | Manages a specific branch: staff, local inventory adjustments, stock requests, branch-specific product availability toggling, and operational settings. |
| Cashier (Staff) | Front-of-house access: POS terminal, KDS, order management, and dashboard for their assigned branch only. |
| Rider | Appears in KDS dispatch lists and receives delivery assignments through the Rider mobile application. |

---

## 2. System Overview

The Mister Takoyaki Web System is a real-time application built on Laravel Livewire, designed to unify all cafe operations under a single platform. Key architectural strengths include the following:

- **Ingredient-Level Stock Safety:** The POS cannot oversell. Ingredient deductions are calculated automatically per order item and selected option.
- **Modular Customization Library:** Standardizes customization groups (for example, "Sugar Level") across the entire product catalog, with automatic ingredient deduction rules that follow the template.
- **Immutable Ledger Architecture:** Every sale, void, and refund writes a permanent, tamper-proof record to the ledger, guaranteeing audit integrity.
- **Dual-Window Predictive Analytics:** Uses Weighted Linear Regression with IQR-based outlier removal and day-of-week seasonality analysis to produce accurate 7-day and 6-month forecasts.
- **Integrated Multi-App Ecosystem:** Parallel APIs support a customer-facing delivery app and a rider dispatch app, both synchronized in real time to the web dashboard.

---

## 3. Accessing the System

### 3.1 Opening the System

1. Open a supported web browser.
2. Navigate to the system URL provided by your system administrator.
3. The Login page is displayed by default.

### 3.2 Logging In

1. Enter your registered email address.
2. Enter your password.
3. Click **Log In**.
4. The system authenticates your session and redirects you to the Dashboard. All data you see will be automatically scoped to your assigned role and branch.

### 3.3 Forgot Password — 3-Step OTP Recovery

The password reset flow uses a cryptographically secure 6-digit One-Time Password (OTP) sent to your registered email address.

#### Step 1: Request Reset Code

1. On the Login screen, click the **Forgot your password?** link.
2. Enter the email address associated with your account.
3. Click **Email Password Reset Code**.
4. The system generates a 6-digit OTP and emails it to you. The code is valid for 10 minutes.
5. If you do not receive the email within a minute, click **Resend Code**. A 60-second cooldown applies between resend requests.

#### Step 2: Verify Code

1. Enter the 6-digit numeric code from your email into the verification field.
2. Click **Verify Code**.
3. The system validates the code. You have a maximum of 5 attempts before the code is invalidated and you must request a new one. An expired or exhausted code returns you automatically to Step 1.

#### Step 3: Set New Password

1. Enter your new password. It must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, and one number.
2. Confirm the new password.
3. Click **Reset Password**. Your session token is valid for 15 minutes after the OTP is verified. After that, you must restart from Step 1.
4. You are redirected to the Login page upon success.

> [!NOTE]
> **Security Assurance:** The OTP is a one-time-use, server-side hashed token. It is deleted immediately after successful verification. The email step does not confirm or deny whether an account exists for the entered email address, which prevents account enumeration attacks.

---

## 4. User Roles and Permissions

### 4.1 Permissions Matrix

| Module | Super Admin | Admin | Cashier |
|---|---|---|---|
| Dashboard | Yes | Yes | Yes |
| POS and Orders | Yes | Yes | Yes |
| Order Management | Yes | Yes | Yes |
| Kitchen Display (KDS) | Yes | Yes | Yes |
| Menu and Options (View/Toggle) | Yes | Yes | No |
| Menu and Options (Create/Edit/Delete) | Yes | No | No |
| Options Library | Yes | Yes | No |
| Category Management | Yes | No | No |
| Inventory and Stock Management | Yes | Yes | View Only |
| Stock Adjustment and Waste Logging | Yes | Yes | No |
| Branch Requests (Submit) | No | Yes | No |
| Branch Requests (Approve/Dispatch) | Yes | No | No |
| Branch Management | Yes | No | No |
| User Management (Own Branch Staff) | Yes | Yes | No |
| User Management (Cross-Branch/All) | Yes | No | No |
| Business Intelligence | Yes | Yes | No |
| Customer Reviews | Yes | Yes | No |
| System Settings (All Tabs) | Yes | No | No |
| System Settings (Inventory/POS/Reviews) | Yes | Yes | No |
| Notifications | Yes | Yes | Yes |
| Profile Settings | Yes | Yes | Yes |

### 4.2 Admin Access Note on Menu and Options

Admin accounts can view the product catalog and toggle per-branch availability. The Add Product, Edit Product, and Delete Product actions are hidden from Admin accounts entirely. An Admin's branch override is limited to toggling a product's status between Active and Hidden for their own branch, without affecting the global product record or other branches.

---

## 5. Dashboard

### 5.1 Overview

The Dashboard is the operational command center of the Mister Takoyaki system. It unifies real-time financial telemetry, multi-channel sales distribution, predictive linear trends, ingredient consumption velocity, multi-branch geographic mapping, stock deficiency alerts, and a live terminal order feed in a single interface.

- **Target Audience:** Super Admin (Global Enterprise & branch-specific views), Administrator (Branch-scoped operational view), Cashier (Branch-scoped front-of-house view).
- **Refresh & Reactive Architecture:** Live background polling updates metrics in real time. Dynamic recalculation triggers immediately upon order completion, void, refund, or stock adjustment. An in-memory request-level segment cache guarantees instant rendering without serving stale persistent cache data.

---

### 5.2 RBAC Welcome Banner

At the top of the dashboard, a responsive welcome banner provides immediate context and role-specific orientation.

- **Time-Aware Greeting:** Dynamically displays "Good morning" (before 12:00 PM), "Good afternoon" (12:00 PM – 6:00 PM), or "Good evening" (after 6:00 PM) alongside the logged-in user's first name.
- **Centralized Role Theming:**
  - **Super Admin:** Indigo gradient banner with `SUPER ADMIN` badge, shield icon, and the subtitle *"Enterprise command center and network-wide telemetry."*
  - **Administrator:** Rose gradient banner with `BRANCH ADMIN` badge, store icon, and the subtitle *"Branch operational control, inventory oversight, and staff management."*
  - **Cashier:** Emerald gradient banner with `CASHIER` badge, register icon, and the subtitle *"Front-of-house order terminal, kitchen dispatch, and payment processing."*
- **Dismiss Control:** Users can click the close (`✕`) button on the right to dismiss the banner for the current session with an animated fade-out transition.

---

### 5.3 Unified Control Panel

Located directly below the welcome banner, the control panel provides global filtering and report generation tools.

#### 5.3.1 Branch Selector
- **Super Admin:** An interactive dropdown selector allows toggling between **All Locations (Global / Enterprise Overview)** and any specific individual branch.
- **Administrator & Cashier:** Fixed to a locked branch badge displaying their assigned branch name with a location pin icon, preventing unauthorized cross-branch data access.

#### 5.3.2 Integrated 3-in-1 Date Filter (`x-date-filter`)
The integrated date filter scopes all dashboard cards, charts, and metrics simultaneously. Clicking the filter trigger opens an interactive calendar panel:
- **Responsive Calendar View:** Automatically renders a dual-month side-by-side calendar on desktop screens (screen width 1024px or higher) and a single-month view on mobile devices.
- **Quick Range Presets:**
  - **Today:** Current day (00:00 to 23:59).
  - **Yesterday:** Previous calendar day.
  - **This week:** Rolling 7-day window up to today.
  - **Last week:** Monday through Sunday of the previous calendar week.
  - **This month:** Rolling 30-day window up to today.
  - **Last month:** 1st day to the final day of the previous calendar month.
  - **This year:** January 1 of the current year up to today.
  - **Last year:** January 1 to December 31 of the previous calendar year.
  - **All time:** Clears date boundaries to evaluate all recorded historical data.
- **Custom Range Picker:** Users can click any start date followed by an end date directly on the calendar.
- **Date Validation Engine:** Prevents selecting future dates and prevents setting a start date later than the end date. If an invalid range is submitted, a red validation alert banner is displayed at the top of the dashboard (*"Start date cannot be in the future"* or *"Start date cannot be after end date"*).

#### 5.3.3 Executive Report Generation (`Generate Dashboard`)
- The top control bar features a **Generate Dashboard** button (`x-report-dropdown`).
- Clicking the button automatically compiles and streams a standardized executive PDF report (`Dashboard_Report_YYYY-MM-DD.pdf`).
- **Report Contents:** Executive summary table of all financial KPIs, active staff ratios, top 5 selling products with volume and revenue, shortage inventory alerts, expiring batches within 7 days, and ingredient consumption velocity.
- The underlying export engine also supports CSV and Excel-compatible exports.

---

### 5.4 Financial Intelligence KPI Cards

The financial section features five interactive cards with real-time calculations and embedded sparkline trend charts.

| KPI Card | Description | Visual Features | Slide-Over Metric |
|---|---|---|---|
| **Gross Revenue** (Hero Card) | Total revenue before deductions, including discounts. | Live pulsing emerald indicator, total discounts applied counter (`₱... Discounts Applied`), and green gradient sparkline. | `Revenue` |
| **Gross Profit** (Cashflow Card) | Net sales minus COGS and inventory wastage loss. | Displays Gross Profit Margin percentage (`%`), solid green **Revenue Collected** inflow bar, and 12-block pulsing rose **COGS & Spoilage** outflow bar. | `Profit` |
| **Net Sales** | Revenue collected excluding delivery fees. | Displays exact delivery fee deduction notice (`Excl. ₱... delivery`) and bottom emerald sparkline. | `Revenue` |
| **Average Order Value (AOV)** | Average monetary spend per completed transaction (`Net Sales ÷ Order Count`). | Displays average spend per transaction with bottom indigo sparkline. | `AOV` |
| **Ingredient Costs (COGS)** | Total cost of raw materials consumed, derived from recipe deductions. | Displays period cost of goods sold with bottom rose sparkline. | `COGS` |

> [!TIP]
> **Interactive Cards:** Clicking any KPI card or its *"View"* / *"View breakdown →"* link instantly opens the **Intelligence Report Slide-Over Panel** for deep-dive analysis.

---

### 5.5 Intelligence Report Slide-Over Panel (`x-side-panel`)

Clicking a KPI card opens an interactive slide-over drawer from the right side of the screen, providing granular mathematical and categorical breakdowns:

#### 5.5.1 Identity & Formula Header
The header displays the metric initial avatar, active date filter pill, and the exact mathematical formula used by the system:
- **Revenue:** `Σ (Sales Price × Qty)`
- **COGS:** `Σ (Inventory Used × Unit Cost)`
- **AOV:** `Total Revenue ÷ Order Count`
- **Profit:** `Net Sales - COGS - Waste`
- **Margin:** `(Net Profit ÷ Revenue) × 100`

#### 5.5.2 Profit Waterfall View
When inspecting **Gross Profit**, the slide-over displays a step-by-step accounting waterfall:
1. **Total Revenue Inflow:** Gross sales collected.
2. **Resource Consumption (COGS):** Deducted raw material cost based on branch purchase costs and recipe deductions.
3. **Variance & Spoilage:** Deducted monetary cost of recorded waste, spoiled stock, and physical discrepancy adjustments.
4. **Gross Profit Result:** Final net profit card with highlighted margin percentage badge.

#### 5.5.3 Categorical & Bucket Distributions
For other metrics, the drawer displays categorized data nodes with volume badges and percentage contribution:
- **Revenue & COGS:** Grouped by menu category (e.g., Classic Takoyaki, Premium Takoyaki, Beverages, Add-ons), including service charges, delivery deductions, and discount impacts.
- **AOV Buckets:** Segmented into ticket brackets to evaluate customer spending habits:
  - *Light Snack (Under ₱200)*
  - *Standard Meal (₱200 – ₱500)*
  - *Family Pack (₱501 – ₱1,000)*
  - *Party / Bulk (Above ₱1,000)*

---

### 5.6 Operations Index

Positioned beside the financial cards, the Operations Index provides four high-level operational counters:

- **Menu Items:** Count of currently active products available in the catalog.
- **Ingredients:** Total raw materials and packaging items actively tracked in inventory.
- **Inventory Alerts:** Combined count of low-stock ingredients (below reorder threshold) and batches expiring within 7 days. Highlights in **bold red** when greater than zero to signal immediate attention.
- **Staff:** Ratio of active accounts to total registered accounts for the selected branch (e.g., `3 / 6 Active Accounts`).

---

### 5.7 Spend and Revenue Activity Chart

An interactive visualizer powered by ApexCharts that illustrates business trends and forecast projections over time.

- **Metric View Switcher:**
  - **Sales:** Overlays Gross Sales against Ingredient Costs (COGS) to track gross margin spread.
  - **Vol (Volume):** Plots total completed order volume alongside a predictive trendline forecast.
  - **Profit:** Compares Gross Profit against COGS with a predictive forecast projection.
- **Adaptive Time-Resolution:**
  - When the active date filter is set to a single day (e.g., *Today* or *Yesterday*), the chart automatically switches to a **24-hour hourly resolution**.
  - When multi-day, monthly, or yearly ranges are selected, the chart groups data by day or week.
- **Interactive Tooltips:** Hovering over data points reveals exact monetary values, timestamps, and trend indicators.

---

### 5.8 Branch Live Map & Expanded Modal (Super Admin / Multi-Branch)

For enterprise accounts and multi-branch overviews, an interactive Leaflet map visualizes the geographical footprint and sales performance of all Laguna branch locations.

- **Map Mode Switcher:**
  - **Satellite:** High-resolution aerial satellite imagery (ESRI World Imagery).
  - **Street View:** Standard road and topography map (OpenStreetMap).
  - **Vector View:** Minimalist high-contrast vector cartography (CartoDB Positron).
- **Interactive Branch Markers:**
  - Hovering over a branch marker displays a tooltip with the branch name, period sales amount, and percentage share of total network sales.
  - The top-performing branch is highlighted with a **pulsing emerald marker** and a star indicator (★).
- **Expanded Map Modal (`branch-map-expanded`):**
  - Clicking the expand button in the map header opens a full-screen modal with an expanded 520px high map canvas.
  - Includes a 4-column summary grid showing all branches and their respective sales contributions.
- **Branch Leaderboard:** A ranked performance list directly below the inline map displays each branch's sales amount and network percentage contribution.

---

### 5.9 Secondary Analytical Grid

#### 5.9.1 Revenue Channels & Payment Methods
- **Fulfillment Split:** Visual progress bars displaying order count and percentage distribution across all fulfillment types:
  - **Dine-in** (Emerald)
  - **Take-out** (Blue)
  - **Delivery** (Amber)
  - **Pick-up** (Indigo)
- **Payment Methods Breakdown:** Real-time breakdown of transactions processed via **Cash** or **GCash (Static QR)**, displaying two-letter avatar badges, order count, and total monetary value collected in PHP (`₱`).

#### 5.9.2 Ingredient Consumption (Usage Velocity)
- Displays the fastest-moving raw materials ranked by daily consumption rate (`X / DAY`).
- Includes visual progress bars dynamically styled according to the active user role's theme color (Indigo for Super Admin, Rose for Branch Admin, Emerald for Cashier).

#### 5.9.3 Inventory Health & Efficiency Audit
- **Segmented Battery Indicator:** A high-tech segmented battery graphic that dynamically reflects inventory efficiency (0% to 100%):
  - **Emerald (>80%):** Healthy turnover with minimal waste loss.
  - **Amber (50% – 80%):** Moderate variance; usage should be monitored.
  - **Rose (<50%):** Critical waste or severe stock discrepancies detected.
- **Efficiency Score & Waste Loss:** Displays overall efficiency score percentage, total monetary waste loss (`₱`), and waste variance as a percentage of consumed stock value.

---

### 5.10 Operational Workstation Grid

The bottom row of the dashboard serves as a quick-action operational hub:

#### 5.10.1 Top Products
- Lists the top 5 best-selling menu items ranked by volume.
- Displays product initial avatar, item name, category badge, total units sold, and total revenue generated.
- Features a **Load Extended Catalog** shortcut button linking directly to Menu Management (`/menu`).

#### 5.10.2 Stock Alerts Widget (Sliding Tabs)
A dual-tab sliding widget that separates stock shortages from expiring batches:
- **Shortage (Deficiency Tab):** Lists ingredients currently below their configured minimum threshold, indicating ingredient name, branch name, and current stock quantity.
  - *Empty State:* Displays *"Stock Secured — All ingredients meet the threshold"* when inventory is healthy.
- **Expiry (Expiration Tab):** Lists batches expiring within 7 days or already expired. An amber pulsing notification dot appears on the tab header when active expiration risks exist.
  - Expired batches display an **`EXPIRED`** badge in red.
  - Soon-to-expire batches display a countdown badge (e.g., **`3d`**) in amber.
  - *Empty State:* Displays *"No Expirations — Materials life-cycle is healthy"* when all batches are fresh.
- Features an **Expand Full Ledger** shortcut button linking directly to Stock Management (`/stock`).

#### 5.10.3 Live Order Feed
- A high-contrast dark console widget displaying the 10 most recent branch orders in real time with an active pulsing green status dot.
- Displays order reference ID, elapsed time (e.g., *"2m ago"*), status label, and total amount.
- Status borders are color-coded:
  - **Amber Border:** Orders in `Preparing` state.
  - **Emerald Border:** Orders in `Ready` or `New` state.
  - **Gray Border:** Orders in `Completed` state.
- Features a **POS Console** shortcut button linking directly to the POS terminal (`/pos`).

---

## 6. POS and Orders

### 6.1 Opening the POS Terminal

- **Target Roles:** Super Admin, Administrator, Cashier.
- Navigate to **POS and Orders** (or `/pos`) from the sidebar.
- The top bar contains Category Navigation Tabs, Live Search Bar, Held Orders indicator button, and the Layout Sort toggle button.
- The main area displays an interactive Product Card Grid with instant live search, category filtering, and visual stock health indicators.
- The right panel (desktop) or bottom drawer (mobile) displays the real-time Order Summary cart, line item modifiers, discount indicators, fulfillment method selector, table/reference input, and payment triggers.

#### Stock Badges on Product Cards

| Status | Visual Indicator | Operational Behavior |
|---|---|---|
| **In Stock** | Green badge showing available units | Item can be freely added to cart up to the available ingredient limit. |
| **Low Stock** | Amber badge (10 or fewer units remaining) | Item is nearing depletion based on raw material availability. |
| **Out of Stock** | Grayed out card with red "Out of Stock" badge | One or more mandatory recipe ingredients are depleted. Card is grayed out and clicking is disabled. |

---

### 6.2 Adding Items to the Cart

#### Standard (Non-Customizable) Items
1. Tap the product card or click **Add to Cart**.
2. The item is instantly added to the cart with a quantity of 1.
3. Tapping again increments the quantity. When the cart quantity reaches the maximum physical stock limit (calculated from raw ingredient balances), further additions are prevented.

#### Customizable Items with Options and Modifiers
1. Tap the product card to open the **Product Customization Modal**.
2. **Option Groups:** Select choices for required groups (e.g., Flavor, Size). Required groups must have a valid selection before the item can be added. Options with depleted ingredients are automatically disabled with an *"Out of Stock"* tag.
3. **Modifiers & Add-ons:** Select optional toppings or add-ons with associated price increments.
4. Tap **Add to Order** to confirm, or **Cancel** to discard.
5. Adding the same product with identical options increments the existing line item's quantity rather than creating duplicate lines.

---

### 6.3 Managing Cart Items: The Two-Column Edit Modal

Tapping the note/pencil icon on any cart row opens the **Two-Column Item Customization & Discount Modal** (`edit-cart-item`):

#### Left Column: Options & Modifiers Reconfiguration
- Displays all option groups and modifier selections for the product.
- Cashiers can change flavors, sizes, or toppings directly without removing and re-adding the item to the cart.
- Options depleted in branch inventory are disabled with out-of-stock badges.

#### Right Column: Quantity, Notes & Discounts
- **Quantity Stepper (+/-):** Adjust line quantity with live validation against branch ingredient inventory.
- **Special Instructions:** Free-text textarea for kitchen notes (e.g., *"Extra crispy"*, *"Sauce on the side"*, *"No mayo"*). Notes print on kitchen tickets and customer receipts.
- **Regular Discount Toggle:** Applies the system-configured regular discount percentage (e.g., 10%) directly to this specific line item.
- **Senior / PWD Discount Toggle:** Applies the statutory 20% Senior Citizen / PWD discount rate and flags the line as VAT-exempt.

> [!NOTE]
> **Stock Safety Guard:** The modal's **Save Changes** button is dynamically disabled (`canSaveEditOrder()`) if a required option is unavailable or if the requested quantity exceeds physical branch stock. Changes update client-side immediately without full-page reloads.

#### Cart Line Removal & Clearing
- Use the (-) stepper to reduce quantity to zero, or click the red trash icon to delete a single item with confirmation.
- Click **Clear Cart** in the order header to remove all items. A confirmation prompt prevents accidental clearing.

---

### 6.4 Fulfillment Method and Table Reference

- **Order Type Selector:** Choose between **Dine-in**, **Take-out**, **Delivery**, or **Pick-up**.
- **Service Charge:** Configured service charges are automatically applied only when the order method is set to **Dine-in**.
- **Table / Customer Reference:**
  - When **Dine-in** is selected, entering a **Table Number** is strictly required to ensure kitchen and floor staff deliver food to the correct table.
  - When **Take-out**, **Delivery**, or **Pick-up** is selected, enter the customer's name, reference tag, or mobile contact number.

---

### 6.5 Holding and Restoring Draft Orders

#### Holding a Cart
1. Click the **Hold Order** icon (pause symbol) in the Order Summary header.
2. The system generates a unique reference code (e.g., `DFT-XXXXXX`) and saves all line items, customizations, special instructions, discount flags, order type, and table reference.
3. Ingredient stock is not deducted while an order remains in draft status.
4. The cart is cleared immediately so the cashier can serve the next customer.

#### Restoring or Deleting Held Orders
1. Click the **Held Orders** button in the top bar. The badge counter displays the number of active held drafts.
2. The **Saved Drafts Panel** displays all branch drafts with table numbers, item counts, creation timestamps, and monetary totals.
3. Click **Restore** on any draft to load all items, options, modifiers, discounts, and customer references back into the active POS cart. The draft is then removed from the draft list.
4. Click the trash icon to permanently delete an abandoned draft.

---

### 6.6 Processing Payments & GCash Lock Safeguard

1. Tap **Proceed to Payment** (or **Place Order**) to open the payment modal. The left side shows the itemized order breakdown; the right side provides payment method selection.
2. Select the payment method:

| Payment Method | Transaction Flow |
|---|---|
| **Cash** | Enter the amount tendered or tap a quick tender button (**Exact**, **100**, **500**, **1000**). The system instantly calculates and displays the change amount. The amount tendered cannot be less than the total due. Tendered and change amounts are permanently recorded and printed on customer receipts. |
| **GCash (Static QR)** | The store's static GCash QR code, account name, and mobile number are displayed. The customer scans and transfers the exact amount. The cashier verifies the SMS/app payment notification on the store device, then clicks **Mark as Verified**. A green verified badge is displayed. |

3. Tap **Place Order** to finalize the sale, trigger stock deductions, dispatch kitchen tickets to KDS, and generate the receipt.

> [!WARNING]
> **Verified Payment Protection Safeguard:**  
> Once a GCash payment is marked as verified:
> - The payment modal **cannot be dismissed or closed** (modal close and escape triggers are locked).
> - The cart **cannot be cleared** (`clearCart` is blocked).
> - Line items, quantities, discounts, and order details **cannot be modified**.
> 
> This safeguard ensures that money received via QR transfer cannot be accidentally lost or unrecorded. If an order must be cancelled after GCash verification, the cashier must finalize the order and then void it through Order Management (see Section 7.8).

---

### 6.7 Thermal Receipt Printing

- Finalizing an order dispatches print commands directly to connected printers.
- **Web Bluetooth ESC/POS Support:** Connects directly to Bluetooth thermal receipt printers from Chrome/Edge browsers without triggering default browser print dialogs.
- **Multi-Slip Separation Alert:** When printing multiple slips (e.g., customer receipt followed by kitchen order slip or barista slip), an on-screen prompt alerts the cashier to tear off the first slip before printing continues, complete with a 15-second automatic progression timer.

---

### 6.8 Menu and Category Layout Customization

- **Authorized Roles:** Super Admin, Branch Administrator.
1. Click **Customize Layout** in the top bar to toggle into Layout Edit Mode.
2. **Category Tabs Reordering:** Drag and drop category tabs horizontally. The *"All"* category is locked in the first position.
3. **Product Cards Reordering:** Drag product cards using the drag handle icon to set the preferred visual sequence.
4. Click **Save Layout**. Changes are saved per branch to `branch_product.sort_order` and `BranchCategorySort.sort_order` and do not overwrite the layout of other stores.

---

## 7. Order Management

### 7.1 Overview

Order Management is the centralized operations hub for managing online mobile delivery orders, in-store POS transactions, and historical sales ledgers. It provides end-to-end fulfillment progression, delivery rider dispatching, refund and void processing, thermal receipt printing, and comprehensive audit trails.

- **Target Roles:** Super Admin (all branches), Branch Administrator (assigned branch), Cashier (assigned branch).
- **Reactive Synchronization:** Real-time background synchronization reflects mobile app order placements, KDS preparation milestones, and rider dispatch updates without requiring manual page refreshes.

---

### 7.2 Tab Navigation and the 1-Hour Transition Window

The module is structured into three dedicated operational views:

- **Delivery Orders (App):** Displays incoming and active orders placed through the customer delivery mobile application with statuses: *Pending*, *Preparing*, *Ready*, *Handed to Rider*, and *Out for Delivery*.
- **POS Orders (POS):** Displays in-store point-of-sale transactions and saved drafts with statuses: *Drafted*, *Pending*, and recently fulfilled walk-in orders.
- **Order History:** The permanent audit ledger for all *Completed*, *Cancelled*, *Voided*, *Refunded*, and *Partially Refunded* orders across all sales channels.

> [!NOTE]
> **1-Hour Operational Transition Window:**  
> When an order is completed, cancelled, voided, or refunded, it remains visible in its original operational tab (*Delivery Orders* or *POS Orders*) for **1 hour**. This grace period allows staff to easily reprint receipts, process immediate refunds, or void erroneous entries without navigating away. After 1 hour, the transaction automatically transitions to the permanent *Order History* tab.

---

### 7.3 Tab-Scoped Order Health Overview

Four operational KPI cards are displayed above the order ledger:

| Card | Data Displayed & Operational Behavior |
|---|---|
| **Total Orders** | Total transaction volume within the active tab and date filter scope. |
| **Active Orders** | Dynamically scoped to the active tab via Alpine.js: <br>• In **Delivery Orders:** Counts pending and in-progress app orders. If any order has been sitting unresolved for 24 hours or more, an amber/red pulsing alert badge appears (`X unresolved 24h+`).<br>• In **POS Orders:** Counts unresolved in-store orders and active held drafts.<br>• In **Order History:** Displays zero by definition. |
| **Completed Today** | Count of all orders successfully served, completed, or delivered during the current calendar day. |
| **Today's Revenue** | Net income collected today (Gross Sales minus Refunds, excluding delivery fees and voided orders). |

---

### 7.4 Search and Filtering Controls

- **Live Search Bar:** Instant filtering across Order Reference Number, Customer Name, or Customer Contact Phone Number.
- **Status Filter:** Context-sensitive dropdown displaying only the status options applicable to the active tab.
- **Date Range Presets:** Quick date filters (*Today*, *Last 7 Days*, *Last 30 Days*, *All Time*) or a custom calendar start and end date selector.

---

### 7.5 Managing Delivery App Orders

#### 1. Accepting an Incoming Order
- When a customer places an order via the mobile delivery application, it appears under the *Delivery Orders* tab with status **Pending**.
- Click **Accept Order**. The order status advances to **Preparing**, the food ticket is instantly pushed to the Kitchen Display System (KDS), and the Web Bluetooth thermal receipt printer pre-warms its print connection to issue the kitchen slip.

> [!IMPORTANT]
> **1-Hour Auto-Rejection Safeguard:**  
> If an incoming mobile delivery order remains in **Pending** status for more than **1 hour** without being accepted by a cashier or branch manager, the system's automated scheduler marks the order as **Rejected** with the reason *"Auto-cancelled: Order not accepted within 1 hour"*. This protects customers from indefinite waiting times during peak periods.

#### 2. Rejecting an Order
- If the store cannot fulfill the order (e.g., due to ingredient shortages or closing hours), click **Reject Order**.
- The **Reject Order Modal** opens, allowing staff to enter an explanation reason (e.g., *"Out of takoyaki batter"*, *"Kitchen closed"*).
- Confirming marks the order as **Cancelled / Rejected** and immediately notifies the customer via the mobile app.

#### 3. Dispatching to Rider
- Once food preparation is complete and the order is marked *Ready* on the KDS, click **Hand to Rider**.
- The **Assign Rider Modal** opens, displaying all active branch-assigned riders with their contact numbers and current active deliveries.
- Select an available rider and click **Assign**. Status advances to **Handed to Rider**, notifying the rider's mobile delivery application.

#### 4. Completing an Order
- Once the rider drops off the food and uploads the digital Proof of Delivery photo, click **Complete Order** to finalize the order ledger.

---

### 7.6 Managing POS Draft Orders

- **Resume Draft:** Select any held order in *Drafted* status and click **Resume Order**. The system transfers all items, selected options, special instructions, discount flags, order type, and table references directly into the active POS cart and redirects the user to the POS console (`/pos`).
- **Delete Draft:** Click **Delete Draft** with confirmation to permanently remove an abandoned cart.

---

### 7.7 Processing Refunds

- **Authorized Roles:** Super Admin, Branch Administrator.
- Available for completed orders that maintain an eligible refundable balance.

1. Open the order row to open the order inspector and click **Refund**.
2. Enter the **Refund Amount** (validated to never exceed the remaining refundable balance) and provide a mandatory **Refund Reason**.
3. Click **Submit Refund**. The refunded amount is deducted from financial revenue totals, logged in the financial ledger, and permanently stamped onto the order timeline.

---

### 7.8 Voiding an Order

- **Authorized Roles:** Super Admin, Branch Administrator.

1. Open the order in the inspector drawer and click **Void Order**.
2. Review the confirmation alert and confirm.
3. Voiding is permanent and irreversible:
   - The transaction is completely zeroed out and excluded from financial revenue reports.
   - Any raw material stock deducted during order creation is restored to branch inventory balances via automated reverse movements.
   - This applies even to orders where a GCash payment was previously verified and locked at the POS terminal.

---

### 7.9 Order Detail Panel (Slide-Out Inspector)

Clicking any order row opens a comprehensive slide-out inspector with two dedicated tabs:

#### General Information Tab
- **Metadata:** Transaction source (App or POS), assigned branch, current status, cashier or processor name, and exact placement timestamp.
- **Customer Information:** Customer name, phone number, delivery address, and GPS coordinates for app orders.
- **Proof of Delivery (POD):** For completed delivery orders, displays the physical delivery photo taken by the rider (click to expand full-screen), rider name, completion timestamp, and a map view link.
- **Itemized Breakdown:** Detailed list of products, chosen options, modifiers, item-level special instructions, quantities, unit prices, and line subtotals.
- **Financial Summary:** Subtotal, regular discounts, senior/PWD discounts, service charges, delivery fees, grand total, and any recorded refund amounts.

#### Activity Timeline Tab
- A chronological milestone log detailing every state transition (e.g., *Placed → Accepted → Preparing → Ready → Handed to Rider → Delivered/Completed*), complete with exact timestamps and the authenticated user who initiated each action.

---

### 7.10 Viewing and Printing Receipts

1. Click **Print Receipt** from the order inspector to open the thermal receipt preview.
2. The preview displays the store header, business logo, TIN, address, order reference barcode, itemized product list with customizations, discount lines, VAT calculations, and the customer feedback QR code.
3. Click **Print** to send the ESC/POS print job directly to the paired Web Bluetooth thermal printer.

---

## 8. Kitchen Display System (KDS)

### 8.1 Overview

The KDS is a real-time digital kitchen operations board that coordinates food preparation, monitors cooking elapsed times, manages ready-order pickups, and handles delivery rider dispatch.

- **Target Roles:** Super Admin, Branch Administrator, Cashier (scoped to their active branch).
- **Refresh Interval:** Live polling every 5 seconds.

### 8.2 Kitchen Health Metrics Bar

Four metric cards are displayed at the top of the KDS:

| Metric | Definition |
|---|---|
| Queue | Active orders currently in food preparation (status: Preparing). |
| Delayed | Orders exceeding the configured delay threshold (default: 10 minutes). Displayed in a high-contrast alert style. |
| Ready | Prepared orders awaiting customer pickup or rider dispatch. |
| Served | Total completed transactions fulfilled today. |

### 8.3 Elapsed-Time Timer and Visual Urgency Alerts

Every active ticket features an automated stopwatch displaying exact elapsed preparation time. Color coding adapts dynamically:

| Status | Condition |
|---|---|
| Normal (gray) | Less than 5 minutes elapsed. |
| Warning (amber) | Between 5 and 10 minutes elapsed. |
| Critical Delayed (red, pulsing) | More than 10 minutes elapsed. The ticket header pulses continuously to demand immediate attention. |

### 8.4 Processing Queue (Tab 1)

Displays all in-progress orders (status: Preparing) sorted in First-In, First-Out (FIFO) sequence.

Each ticket shows: order fulfillment type (Dine-in, Take-out, Delivery), live elapsed timer, reference number, placement timestamp, table number, and an itemized list of items being prepared.

Tap **Mark as Ready** once food preparation is complete. The ticket instantly moves to the Ready Board.

### 8.5 Ready Board (Tab 2)

Displays all completed food orders awaiting service or rider dispatch.

- For Dine-in, Take-out, and Pick-up orders: Tap **Mark as Served** to complete the order immediately.
- For Delivery orders: Tap **Assign Rider** to open the Assign Rider window.

### 8.6 Assigning a Rider from the Ready Board

1. Tap **Assign Rider** on a delivery order in the Ready Board.
2. The Assign Rider window opens, listing all active branch-assigned riders with their name, contact number, and current active delivery count.
3. Tap the selected rider's card. The system assigns the rider, updates the order status to Handed to Rider, clears the ticket from the Ready Board, and pushes a real-time dispatch notification to the rider's mobile app.

### 8.7 Historical Record (Tab 3)

An audit log of all completed and dispatched orders.

- Use the date filter (Today, Last 7 Days, Last 30 Days, All Time, or Custom Date Range) to review past tickets.
- Each record shows the reference number, order type, sales channel (POS or App), items, placed time, served time, and final status.
- Completed tickets remain in the recent operational feed for 1 hour before archiving permanently to the Historical Record.

---

## 9. Menu Management

### 9.1 Overview

The Menu Management module provides centralized administration of the food and beverage catalog. Products are globally defined and can be selectively overridden per branch.

- **Menu Creation & Deletion:** Super Admin only.
- **Branch Availability Toggling:** Super Admin and Admin.

All products are global catalog items. Branch-specific availability is controlled separately via the availability toggle.

### 9.2 Catalog Navigation and Filtering

- **Product Search:** Real-time search by product name, SKU, or description.
- **Category Filter:** Filter products by category.
- **Status Filter:** Filter by All, Available, or Hidden.
- **Branch Scope Filter:** Filter catalog visibility by branch.
- **Display Toggle:** Switch between Table View (dense list) and Board View (visual cards).

### 9.3 The 4-Tab Product Workbench

When creating or editing a product, configuration is divided across four tabs:

#### Tab 1 — Information

| Field | Details |
|---|---|
| Product Name | Must be unique across the catalog (2 to 255 characters). |
| Category | Select an existing category or click the plus button to create one inline. |
| Sale Price | Base retail selling price. |
| Status | Available (active in POS and mobile) or Hidden (archived). |
| Description | Optional; up to 500 characters. |
| Product Image | Upload PNG, JPG, or WEBP files up to 4 MB. |

#### Tab 2 — Options

Organize choices, flavors, add-ons, and sizes under Product Option Groups.

- **Group Name:** Label shown to customers (for example, Flavor, Size, Ice Level).
- **Additive Pricing:** Option prices are added on top of the base product price (for example, base price 120, plus Extra Sauce 15 = 135 total).
- **Fixed Pricing:** The selected option's price completely replaces the base price (for example, Regular 65, Large 120).
- **Mandatory Selection:** If toggled on, the customer must make a selection before the item can be added to the cart.
- **Non-Depleting Group:** Toggle on for service choices or packaging that do not consume raw inventory (for example, sweetness level, spoon and fork). The system disallows recipe mapping for these groups.
- **Default Choice:** Designate one choice as pre-selected by default.

#### Tab 3 — Recipe

Directly links menu items to inventory ingredients for automatic stock deductions upon order completion.

- **Base Recipe Ingredients:** Ingredients consumed every time the product is sold, regardless of options.
- **Option-Specific Recipe Ingredients:** Ingredients consumed only when a specific option is selected.
- An ingredient can only be added once per base or per option. The system blocks duplicates.

#### Tab 4 — Profitability

A live financial health engine that calculates product economics in real time.

| Metric | Formula |
|---|---|
| Total Recipe Cost (COGS) | Dynamically summed from the latest acquisition costs of all mapped ingredients. |
| Net Margin | Sale Price minus Recipe Cost (COGS). |
| Gross Profit Margin (%) | (Net Margin / Sale Price) x 100. |

Margin health badges: Green for 60% or above (healthy), Yellow for 35% to 59% (moderate), Red for below 35% or negative (low or deficit).

### 9.4 Creating a Product — Step by Step

1. Navigate to Menu Management.
2. Click **Add Product**.
3. On the Information tab: enter the product name, select a category, set the sale price, upload a photo, and set status to Available.
4. On the Options tab: click **Add Option Group** or **Import from Library**. Set the group name, pricing mode, and add choices.
5. On the Recipe tab: select each ingredient, enter the quantity and unit, specify whether it belongs to the Base or a specific Option, and click **Add Ingredient**.
6. On the Profitability tab: verify the calculated recipe cost, net margin, and profit margin.
7. Click **Save Product**.

### 9.5 Deleting a Product

- **Authorized Role:** Super Admin only.
- Deleting a product permanently removes its image, branch availability records, recipe, and option groups. This action cannot be undone.

### 9.6 Per-Branch Availability Toggle

From the product list, Super Admins and Admins can click the Availability badge to toggle whether a product is sold at their currently active branch. This does not change the product's global default or affect other branches.

### 9.7 Quick-Adding a Product Category

From the Category field on the Information tab, Super Admins can click the plus button to create a new product category inline (name and production station: Kitchen or Barista) without leaving the product form.

For full category management — editing, deleting, and managing ingredient categories — use Category Management (Section 11), which is restricted to Super Admins only.

---

## 10. Options Library Management

### 10.1 Overview

The Options Library stores reusable Option Templates — sets of customization choices (for example, "Sugar Level" or "Choice of Sauce") that can be imported into any number of products. Each template also stores ingredient deduction rules per option item.

- **Authorized Roles:** Super Admin and Admin — both have full Create, Edit, and Delete rights.

### 10.2 Template Structure

Each library template defines the following:

- **Template Name:** Name of the reusable option group. Must be unique across the library.
- **Pricing Logic:** Additive (choices add an extra fee) or Fixed (choices set the final price).
- **Mandatory Requirement:** Whether selection is mandatory or optional upon import.
- **Non-Depleting Toggle:** For non-depleting options such as ice level or sweetness percentage.
- **Recipe Mapping:** Attach raw inventory items directly to template choices so imported options inherit recipes automatically.

### 10.3 Creating a Template

1. Navigate to Options Library and click **Add Template**.
2. Enter a Template Name (must be unique).
3. Choose a Pricing Logic: Additive or Fixed.
4. Optionally mark the group as a Mandatory Field.
5. Click **Add Choice** for each choice. Enter the option name and extra price.
6. Optionally expand each option's Recipe Panel to map ingredients consumed when that option is selected.
7. Use the checkmark icon to designate one option as the system default.
8. Click **Save Template** and confirm.

### 10.4 Editing and Deleting a Template

- Open a template to edit its options, pricing logic, mandatory flag, and ingredient mappings.
- Deleting a template removes it from the library. Products that previously imported it keep their own copy and are not affected.

### 10.5 Importing a Template into a Product

1. On a product's Options tab, click **Import from Library**.
2. Select a template from the library grid.
3. The system copies the group and all its options to the product, including any ingredient recipes mapped in the template.

If a group with the same name already exists on the product, the import is blocked to prevent duplicates.

### 10.6 Syncing a Group from the Library

1. On the product's Options tab, click the sync icon on an existing group.
2. The system matches the group to a same-named Library template.
3. Any ingredients or option items present in the library template but missing on the product's group are added.

Sync only fills in what is missing. It never removes or overwrites prices, defaults, or ingredients already present on the product.

### 10.7 Saving a Product's Group Back to the Library

1. On the product's Options tab, click the save icon on a group.
2. If no template with that name exists, a new one is created from the group's current configuration.
3. If a matching template exists, it is updated in place: matching options are refreshed and each option's ingredient list is rebuilt to match the product's current state.

Template items whose names no longer appear on the product are left untouched in the library, as other products may still rely on them.

---

## 11. Category Management

### 11.1 Overview

The Category Management hub provides a unified workspace for managing classification taxonomies across both the sales catalog and the raw inventory.

1. **Product Categories:** Organize customer-facing food and beverage items on POS terminals and mobile applications. Each Product Category must be assigned to a Production Station — Kitchen (for cooked items) or Barista (for drinks and beverages). This assignment governs how the POS print engine routes items to the correct kitchen or barista slip.
2. **Ingredient Categories:** Group raw materials, dry goods, seasonings, and packaging materials in the stock and inventory ledger.

- **Authorized Role:** Super Admin only. Admins and Cashiers receive an Unauthorized error if they attempt to access this module directly.

### 11.2 Category Reference IDs

Upon creation, the system assigns a formatted alphanumeric reference code:

- Product Category: PRD-CAT-0001, PRD-CAT-0002, and so on.
- Ingredient Category: ING-CAT-0001, ING-CAT-0002, and so on.

### 11.3 Creating a Category

1. Navigate to Category Management and click **Add Category**.
2. Choose the type: Product Category or Ingredient Category.
3. Enter a Display Name and an optional Operational Summary.
4. For Product Categories only, select a Production Station: Kitchen or Barista.
5. Click **Save Category** and confirm.

### 11.4 Editing a Category

Select any category from the table or board view to update its name, description, or production station (Product Categories only).

### 11.5 Deleting a Category

Deletion is blocked if the category still has products or ingredients assigned to it. The system displays the exact count of assigned items and prompts you to re-categorize them first. Once a category has zero associated items, it can be permanently deleted from the Danger Zone.

### 11.6 Production Station Routing

When an order containing both food and beverages is processed at the POS, the print engine checks the production station of each item's parent category and prints a separate Kitchen Slip for kitchen-designated items and a Barista Slip for barista-designated items. This ensures that kitchen and drink preparation staff receive separate, non-conflicting slips simultaneously.

---

## 12. Stock and Inventory Management

### 12.1 Overview

The Stock and Inventory Management module governs the procurement, packaging conversions, batch tracking, and raw material stock levels across all branches.

- **Catalog Definition:** Super Admin only.
- **Inventory Adjustments & Stock-in:** Super Admin and Admin.
- **View-Only Inventory Alerts:** Cashier.

All ingredients are created once in a global catalog. Every branch maintains an independent stock balance and distinct physical batches.

### 12.2 Inventory KPI Dashboard

Four live metric cards are displayed at the top of the module:

| Metric | Description |
|---|---|
| Total Catalog | Total number of global raw material assets in the system. |
| Low Stock | Count of ingredients at or below the configured minimum stock threshold. Displayed in amber. |
| Expiring Batches | Number of batches expiring within the configured alert window (default: 7 days or fewer). |
| Monthly Procurement | Total monetary value of stock-in receipts recorded for the active branch during the current calendar month. |

### 12.3 Ingredient Configuration and Bulk Conversions

1. Navigate to Stock Management.
2. Click **Add Ingredient** or edit an existing one.
3. Enter the Ingredient Name and select its category.
4. Set the Base Unit (for example, "g" for grams, "ml" for milliliters, "pcs" for pieces). This is the unit used in all recipes.
5. Set the Low Stock Alert Threshold (triggers an amber badge and a stock alert notification).
6. Under Bulk Packaging, click **Add Packaging Unit** to support bulk purchasing.

#### Multi-Tier Packaging Conversion Hierarchy

- 1 Bottle = 500 ml (linked directly to base unit)
- 1 Box = 12 Bottles (linked to the Bottle tier, which the system cascades to 6,000 ml)

For each packaging tier, enter the purchase price. The system computes the cost per base unit and automatically sets the lowest calculated cost as the ingredient master cost, flagged with an Optimal Value recommendation.

7. Click **Save Ingredient** and confirm.

### 12.4 Inventory List and Batch Detail

The main grid displays all tracked ingredients with the following information:

| Column | Description |
|---|---|
| Ingredient Name | The ingredient and its assigned category. |
| Current Stock | Total on-hand quantity in base units. |
| Total Value | Calculated from bulk cost and quantity on hand. |
| Health Badge | Green (Healthy), Amber (Low), or Red (Critical/Out of Stock). |
| Batches | Number of active stock batches and the nearest expiry date. |

Click any ingredient row to expand its Batch Detail View, which shows each batch's quantity, cost per unit, expiry date, and source.

### 12.5 Expiry Tracking and Batch Disposal

Switching to the **Expiry Tracking** tab displays all tracked physical stock batches across the branch.

| Status | Condition & Visual Indicator |
|---|---|
| **Expired** | Batch expiry date is prior to today. Flagged with a prominent red badge. |
| **Expiring Soon** | Batch expires within the configured alert window (7 days or fewer). Flagged with an amber badge. |
| **Fresh** | Batch expires beyond the alert window. Flagged with a green badge. |
| **Non-Perishable** | No expiration date specified (e.g., packaging supplies). |

#### Batch Disposal Workflow
1. Locate the expired or damaged batch in the Expiry Tracking table.
2. Click the **Dispose Batch** button (trash can icon).
3. Review the **Batch Disposal Confirmation Modal**, which details the batch ID, ingredient name, unit cost, and exact quantity to be discarded.
4. Click **Confirm Disposal**. The batch quantity is immediately zeroed out, the branch stock balance is decremented, and a permanent **Waste Movement** is recorded in the ledger with the user's ID, branch ID, and timestamp.

---

### 12.6 Automated Daily Email Alerts

The system features an automated notification service that executes daily at 6:00 AM:
- **Super Admins:** Receive a consolidated enterprise report summarizing low-stock deficits, soon-to-expire batches, and critical stockouts across all branches.
- **Branch Administrators:** Receive a localized digest specific to their branch.
- **Deduplication Lock:** A background concurrency mutex ensures the digest email is dispatched only once per day, preventing duplicate emails when multiple staff members log in concurrently.

---

## 13. Stock Adjustments and Physical Count Reconciliation

### 13.1 Overview

The Stock Adjustment and Inventory Logistics module manages supplier procurement, kitchen waste logging, manual inventory adjustments, and physical stocktaking reconciliations. It serves as the immutable audit engine for all raw material movements.

- **Authorized Roles:** Super Admin and Branch Administrator.
- **View-Only Access:** Cashiers have read-only access to view ledger balances.
- **Central Commissary Rule:** Only the designated **Main Branch** is authorized to record supplier **Procurement (Stock In)**. Satellite branches receive inventory through **Branch Stock Transfers** (Section 14) and are limited to logging **Waste**, **Stock Out**, or physical count reconciliations.
- **FEFO Guarantee (First-Expired, First-Out):** Any stock reduction (waste, stock out, or negative reconciliation deficit) automatically deducts inventory from the earliest-expiring batch first.

---

### 13.2 Audit Ledger and Period KPI Cards

The top of the adjustment console features four live operational counters based on the active date filter:

| Metric | Description |
|---|---|
| **Period Logs** | Total number of inventory change movements recorded within the filtered date range. |
| **Waste Count** | Total volume of spoiled, contaminated, or expired ingredients logged as waste. |
| **Restock Value** | Total monetary acquisition cost of procurement receipts recorded at the branch. |
| **Out Count** | Total volume of raw materials manually checked out for non-sales purposes. |

#### Filtering and Audit Export
- Filter movements by Reference ID, ingredient name, movement type, or attendant.
- Quick date presets (*Today*, *This Week*, *This Month*, *All Time*) or custom calendar date ranges.
- Click **Export Ledger** to download an immutable audit spreadsheet (CSV or PDF) formatted with Date, Branch, Ingredient, Type, Quantity, Reference ID, and Responsible Attendant.

---

### 13.3 Adjustment Queue Engine

To prevent incomplete transaction commits, the system uses an **Adjustment Queue** that stages multiple inventory entries for review before writing them atomically to the database.

| Movement Type | Operational Purpose |
|---|---|
| **Procurement / Stock In** | Used when receiving fresh deliveries from suppliers. Creates a new stock batch with batch number, quantity, acquisition cost, and supplier expiration date. (*Main Branch only*). |
| **Waste** | Logs spoiled, contaminated, dropped, or expired ingredients. Deducts stock via FEFO and flags the monetary value as kitchen loss. |
| **Stock Out** | Logs inventory consumed for non-sales purposes (e.g., taste testing, staff training, marketing demonstrations). Deducts stock via FEFO. |
| **Return to Supplier** | Reverses defective stock deliveries and logs supplier return credits. |

#### Staging Adjustments Step by Step
1. Navigate to **Stock Adjustment** from the sidebar.
2. Click **New Adjustment** to open the adjustment staging drawer.
3. Select an ingredient from the dropdown.
4. Select the **Movement Type** (Procurement, Waste, Stock Out, or Return).
5. Enter the quantity. For procurement, select the packaging unit (e.g., Box, Sack), enter the supplier purchase price, and set the expiration date.
6. Click **Add to Queue**. The item is added to the staged list. Repeat for any additional ingredients.
7. Enter optional operational remarks or supplier invoice reference numbers.
8. Click **Commit Adjustments** and confirm. All queued entries are committed in a single atomic database transaction.

> [!TIP]
> **Automatic Unit Cost Re-indexing:** When procurement is logged in bulk packaging units (e.g., Box of 12 Bottles), the system automatically divides the invoice cost down to the base recipe unit (e.g., per ml) and updates the ingredient's active master cost. This ensures subsequent COGS calculations reflect current supplier pricing.

---

### 13.4 Physical Count Reconciliation

The **Stock Reconcile** mode streamlines daily, weekly, or monthly physical inventory audits:

1. Click **Stock Reconcile** from the main adjustment toolbar.
2. The reconciliation table displays every tracked ingredient for the active branch alongside its current ledger balance (**System Quantity**).
3. Staff conduct a physical count and type the verified count into the **Actual Quantity** column.
4. The system calculates the variance in real time: `Variance = Actual Quantity - System Quantity`.
   - **Negative Variance (Deficit):** The system automatically executes a FEFO deduction to retire the missing stock and logs a reconciliation shortage movement.
   - **Positive Variance (Surplus):** The system creates a new untracked adjustment batch to increment the branch balance to match reality.
5. Click **Reconcile Inventory** and confirm. Only rows with entered actual counts are updated; untouched rows remain unchanged.

---

## 14. Branch Stock Ordering and Inter-Branch Transfers

### 14.1 Overview

The Branch Stock Ordering and Inter-Branch Transfer System manages internal supply chain replenishment between the central commissary (**Main Branch**) and satellite retail store locations.

The system operates across two specialized consoles:

1. **Satellite Branch Console (`BranchStockOrder`):** Used by branch managers to audit inventory deficits, assemble replenishment carts, submit supply orders, track transit progress, and confirm delivery receipts.
2. **HQ Commissary Console (`BranchStockOrderAdmin`):** Used by Super Admins and commissary managers to review pending store requests, adjust approved quantities, set logistics delivery fees, approve or reject orders, and dispatch physical stock transfers.

> [!NOTE]
> **Central Commissary Exclusive Supplier Rule:**  
> The designated central Main Branch acts exclusively as the upstream supplier and warehouse. It cannot initiate replenishment requests to itself. If a user's operating context is set to the Main Branch, the satellite ordering interface is automatically disabled.

---

### 14.2 Satellite Branch Ordering Console

Satellite branch managers manage replenishment through three tabs:

| Panel | Purpose & Capabilities |
|---|---|
| **Active Requests** | Monitor pending, approved, packing, and in-transit orders with live timeline updates. |
| **New Stock Request** | Interactive ordering workbench with packaging unit selectors, deficit alerts, and cost calculations. |
| **Transfer History** | Historical archive of all delivered, rejected, and cancelled internal shipments. |

#### Low-Stock Deficit Assistant
- The ordering console continuously monitors local ingredient balances against configured reorder thresholds.
- Depleted or deficit items display an amber warning badge with an **Add to Cart** shortcut.
- Clicking the shortcut automatically calculates the deficit quantity needed to restore optimal stock levels and stages it directly into the order cart.

#### Real-Time Commissary Stock Verification
- When entering a requested quantity, the system performs a real-time inventory check against live balances at the Main Branch.
- If the requested quantity exceeds available commissary stock, an on-screen warning is displayed to prevent requesting unavailable supplies.

#### Request Priority Levels

| Level | Operational Urgency |
|---|---|
| **Normal** | Standard weekly or scheduled restocking cycle. |
| **Urgent** | Rapid depletion; stock projected to run out within 24 to 48 hours. |
| **Critical** | Zero stock or immediate risk of halting store operations. High-visibility red banner displayed on the HQ admin console. |

---

### 14.3 Logistics and Delivery Fee Calculation

Inter-branch shipments use an automated geodesic distance calculation engine based on stored GPS coordinates:

$$\text{Logistics Fee} = \text{Base Fee} + (\text{Distance in km} \times \text{Rate per km})$$

- **Clamping:** The calculated fee is clamped between a configured Minimum Fee and Maximum Fee Cap.
- **Free Delivery Threshold:** If the order subtotal exceeds the configured threshold, the delivery fee is automatically waived (`₱0.00`).
- **HQ Manual Override:** Super Admins can manually adjust or waive the suggested logistics fee during fulfillment review.

---

### 14.4 The 5-Stage Order Fulfillment Lifecycle

```
[1. Pending] ──> [2. Approved] ──> [3. Preparing] ──> [4. In Transit] ──> [5. Delivered]
      │               │
      └──> Cancelled  └──> Rejected
```

1. **Stage 1 — Pending:** The satellite store submits the stock request. Branch managers can modify or cancel the request at any time prior to approval. Only one active pending request is permitted per branch at a time.
2. **Stage 2 — Approved:** The Super Admin reviews the request at HQ, adjusts approved quantities if needed, verifies stock, confirms or modifies the logistics fee, and clicks **Approve Request**.
3. **Stage 3 — Preparing:** Commissary warehouse staff pull items from physical storage and pack the crates.
4. **Stage 4 — In Transit (Dispatched):** The Super Admin clicks **Dispatch Order**. The system generates an official Stock Transfer document, deducts inventory from Main Branch batches using **FEFO**, and creates transit staging records. Paired audit logs are written for both branches.
5. **Stage 5 — Delivered (Completed):** The physical shipment arrives at the satellite branch. The branch manager inspects the crates, verifies quantities, and clicks **Confirm Delivery**. The transferred batches are committed to the branch ledger (preserving original supplier expiry dates and acquisition unit costs), and the order is archived to Transfer History.

---

### 14.5 Submitting a Stock Request — Step by Step

1. Navigate to **Stock Ordering** (`/stock-orders`).
2. Switch to the **New Stock Request** tab.
3. Review low-stock deficit alerts and click **Add to Cart** for depleted ingredients.
4. To add custom items: select an ingredient, choose a packaging unit (e.g., *Box*, *Sack*, *Bottle*), enter the requested quantity, and click **Add to Cart**.
5. Select the **Priority Level** (*Normal*, *Urgent*, or *Critical*).
6. Enter optional delivery instructions or remarks for HQ.
7. Review the estimated delivery fee, subtotal, and total cost.
8. Click **Submit Order** and confirm.

---

### 14.6 Approving and Dispatching an Order — Step by Step (HQ Admin)

1. Navigate to **Stock Orders Admin** (`/stock-orders-admin`).
2. In the Inbox panel, locate the pending request and click **Review**.
3. Verify ingredient availability against Main Branch inventory.
4. Adjust the **Approved Quantity** per line item if stock is limited.
5. Review the calculated distance and logistics fee; apply fee overrides if necessary.
6. Enter optional administrative remarks.
7. Click **Approve Request**.
8. Once items are pulled from storage and packed, click **Mark as Preparing**.
9. When the delivery vehicle departs, click **Dispatch Order**. Main Branch stock is deducted immediately via FEFO.

---

### 14.7 Confirming Delivery Receipt — Step by Step

1. In the satellite store's **Stock Ordering** console, locate the order under **Active Requests** (status: *In Transit*).
2. Physically inspect all delivered items against the digital dispatch manifest.
3. Click **Confirm Delivery**.
4. The system increments the local branch inventory, writes matching batch records, and archives the transfer to **Transfer History**.

---

## 15. Branch Management

### 15.1 Overview

The Branch Management module governs the multi-store physical network. It provides centralized provisioning of store locations, geographic mapping, manager assignments, and cross-branch operational auditing.

- **Authorized Role:** Super Admin only. Branch Admins and Cashiers are restricted from this module.

---

### 15.2 Key Capabilities

- **Automated Branch Code Generation:** When typing a new branch name, the system automatically generates a standardized code prefixed with `MTC-` (e.g., *"SM City Santa Rosa"* produces `MTC-SMSR`), which can be accepted or customized.
- **PSGC Address Integration:** Structured Philippine Standard Geographic Code hierarchy: Region, Province, City or Municipality, Barangay, and Street address.
- **Interactive Leaflet Geo-Mapping:** An interactive map allows administrators to drop or drag a pin to automatically capture exact latitude and longitude GPS coordinates.
- **Geodesic Distance Matrix:** Uses captured coordinates to calculate road and transit distances from the Main Branch, driving automated logistics fee calculations.
- **Accountable Manager Assignment:** Every active branch must have an assigned Branch Manager. A manager can only be assigned to one store at a time. Assigning a manager to a new branch automatically releases them from their previous store.

---

### 15.3 Adding and Editing a Branch

1. Navigate to **Branch Management** from the sidebar.
2. Click **Add Branch** (or click **Edit** on an existing branch card).
3. Complete the branch form:

| Field | Description |
|---|---|
| **Branch Name & Identifier** | The public-facing store name and unique `MTC-` branch identifier code. |
| **Contact Information** | Official branch phone number and email address. |
| **PSGC Address** | Full Philippine address hierarchy: Region, Province, City, Barangay, and Street. |
| **Geo-Coordinates** | Latitude and Longitude coordinates captured by clicking on the interactive map canvas. |
| **Operating Hours** | Official opening and closing hours for system operations. |
| **Branch Manager** | Select an active, registered user with Role 2 (Admin) to serve as the branch manager. |
| **Status** | Active or Inactive. |

4. Click **Save Branch** and confirm.

---

### 15.4 Managing Branch Operational Status

- **Active:** The store is fully operational. Its POS terminals are unlocked, KDS is enabled, and the location is visible for order placement in the customer mobile delivery app.
- **Inactive:** Suspends all POS operations for the branch immediately. The branch is hidden from the mobile delivery app. All historical financial, order, and stock records remain intact.

---

### 15.5 Safe Branch Decommissioning

Decommissioning a store is protected by database integrity checks:
- **Staff Transfer Safeguard:** If a branch has registered staff members (Cashiers or Riders), deletion is blocked. The Super Admin must transfer or reassign all personnel in User Management first.
- **Manager Decoupling:** If an active manager is assigned, confirming branch deletion safely resets the user's branch assignment to null within an atomic database transaction.

---

### 15.6 Multi-Branch Comparative Insights

Switching to the **Insights** panel allows Super Admins to select multiple stores and run comparative performance audits across custom date ranges, analyzing:
- Comparative Net Revenue and Gross Sales.
- Order volume distribution and ticket size averages.
- Active personnel ratios and operational fulfillment efficiency.

---

## 16. User and Staff Management

### 16.1 Overview

The User and Staff Management module provides centralized role-based identity management, access provisioning, and staff accountability.

- **Super Admin:** Enterprise-wide access. Can create, edit, deactivate, and delete accounts across all branches.
- **Branch Administrator:** Scoped strictly to their assigned branch. Can provision and manage branch Staff (Cashiers and Riders).
- **Cashier & Rider:** Restricted to viewing and editing their own user profile.

#### Directory Filtering & Search
- Filter by **Role** (*Admin*, *Cashier*, *Rider*).
- Filter by **Branch** (*Super Admin only*).
- Filter by **Status** (*Active* or *Inactive*).
- Switch between **Table View** (dense list) and **Card View** (visual user cards).
- Instant search by Name, Email, Phone Number, or Employee ID.

---

### 16.2 Standardized Staff Roles & Positions

| Position | Operational Responsibilities |
|---|---|
| **Branch Administrator** | Branch operational oversight, staff scheduling, local inventory adjustments, branch stock ordering, and daily reporting. |
| **Cashier** | Front-of-house register operations, POS order entry, discount toggling, Cash/GCash tender verification, and receipt printing. |
| **Delivery Rider** | Mobile app order acceptance, GPS customer navigation, transit status updates, and digital Proof of Delivery photo capture. |

---

### 16.3 Manager Conflict Resolution Modal

When assigning a user as Branch Administrator to a store that already has an active manager, the system halts execution and displays the **Manager Conflict Modal**:
> *"Branch [Store Name] is currently managed by [Current Manager]. Do you want to replace them?"*

Confirming safely decouples the prior manager, unsets their branch assignment, and assigns the new administrator within a database transaction.

---

### 16.4 Provisioning a New User — Step by Step

1. Navigate to **User Management** and click **Add User**.
2. Complete the user profile:

| Field | Description |
|---|---|
| **Full Legal Name** | First Name, Middle Name, and Last Name (auto-formatted). |
| **Email Address** | Used as the login credential. Must be unique system-wide. |
| **Phone Number** | 10-digit Philippine mobile number (+63 format). |
| **Password** | Minimum 8 characters; requires at least one uppercase letter, one lowercase letter, and one number. |
| **Role & Position** | Select Role (Admin, Cashier, or Rider) and functional position. |
| **Employee ID** | Optional corporate employee reference tag. |
| **Date Hired** | Official employment start date. |
| **Branch Assignment** | Assigned store (Super Admin can assign any branch; Branch Admins are locked to their own store). |
| **Address** | Full PSGC address and optional GPS home coordinates. |

3. Click **Create User** and confirm.

---

### 16.5 Editing Profiles & Password Resets

1. Select any user from the directory and click **Edit**.
2. Update role, branch assignment, contact information, or status.
3. **Password Reset:** Type a new password into the password fields. Leave both fields blank to preserve the existing password.
4. Click **Save Changes**.

---

### 16.6 Staff Performance Dashboard

Clicking **View Performance** on any staff member opens their operational performance dossier:

- **Performance Overview Tab:** Total orders processed, total revenue facilitated, average ticket value, completion rate percentage, and 7-day activity velocity.
- **Order Audit Log Tab:** Paginated, filterable log of every transaction processed or delivered by the staff member, with quick date presets and direct slide-out inspection.
- **Profile Details Tab:** Complete demographic, role, branch assignment, and contact records.

---

### 16.7 Account Deactivation vs. Deletion

- **Deactivation (Toggle):** Setting an account to *Inactive* invalidates all active login sessions immediately. The user cannot log in. All historical orders, KDS records, and inventory audit trails tied to that user remain fully intact. An account can be reactivated at any time.
- **Deletion (Super Admin Only):** Available exclusively from the user profile's **Danger Zone** with mandatory confirmation. Deletes the user identity record while safely preserving all historical order foreign keys (referencing them as `"(Deleted User)"`).

---

### 16.8 Built-In Avatar Customization Picker

Users can personalize their profile avatar via the built-in modal picker:
- **Two Curated Collections:** **Foods** (e.g., Takoyaki, Ramen, Bento) and **Animals** (e.g., Shiba Inu, Cat, Panda).
- **Dynamic Gradient Tiles:** Each avatar is rendered over a sleek gradient tile.
- Selecting an avatar updates the user interface and navigation bar instantly without page reloads.
- Clicking **Reset Avatar** reverts the profile to default initial-based letter avatars.

---

## 17. Customer Feedback and Reviews

### 17.1 Public Customer Review Portal

Customers can submit feedback without creating an account or logging in:
- **Receipt QR Code:** A dynamic QR code printed at the footer of customer thermal receipts encodes the store's unique review URL.
- **Direct Web URL:** Accessible via the path `/review/{branch_slug}`.

#### Submitted Feedback Data
- **Star Rating Questions (1 to 5 Stars):** Evaluates food taste, service speed, cleanliness, and order accuracy.
- **Open-Ended Text Questions:** Collects qualitative suggestions and customer comments.
- **Customer Identity (Optional):** Customer name and contact number for management follow-ups.
- **Order Reference (Optional):** Associates the review directly with a specific POS transaction.

---

### 17.2 Review Management Console

- **Authorized Roles:** Super Admin (all branches), Branch Administrator (assigned branch).

1. Navigate to **Customer Reviews** (`/reviews`) from the sidebar.
2. **Review Metrics Bar:** Displays Total Reviews count, Network Average Rating (out of 5.0 stars), Branch Count, and Latest Review submission timestamp.
3. **Filtering & Search:** Filter by Branch (Super Admin only), Date Range presets, or search by customer name, phone number, or branch.
4. **Slide-Out Review Inspector:** Click any review row to view the full questionnaire submission, customer comments, submission timestamp, and associated order reference.

---

## 18. Business Reports and Intelligence

### 18.1 Overview

The Business Intelligence module is the primary reporting and financial analytics hub of the Mister Takoyaki system. It operates on an immutable Financial Ledger where all figures are derived directly from permanent transaction, recipe deduction, and inventory waste records.

- **Authorized Roles:** Super Admin (all branches and global network view), Branch Administrator (assigned branch only). Cashiers are restricted from this module entirely.
- **Global Scoping & Filter Sync:** The Branch Selector and Date Filter scope all analytical calculations, predictive regression models, and ledgers across all tabs simultaneously.
- **Executive Report Generation:** Click the **Generate Report** button to export data in **PDF** (executive printable layout), **CSV** (raw spreadsheet data), or **Excel** formats.

---

### 18.2 Tab 1 — Performance (Financial Summary)

The default tab summarizes the business's core financial health and revenue generation for the selected period.

#### Core Financial Formulas

| Metric | Accounting Formula |
|---|---|
| **Gross Revenue** | $\text{Net Sales} + \text{Total Discounts}$ |
| **Net Sales** | $\text{Total Cash and GCash Collected} - \text{Delivery Fees} - \text{Total Refunds}$ |
| **Gross Profit** | $\text{Net Sales} - \text{Total COGS} - \text{Wastage Loss}$ |
| **Gross Profit Margin (%)** | $(\text{Gross Profit} \div \text{Net Sales}) \times 100$ |
| **Average Order Value (AOV)** | $\text{Net Sales} \div \text{Completed Order Count}$ |

#### 3-Tier COGS Cost Resolution Hierarchy
Total Cost of Goods Sold (COGS) is calculated for every product sold using an automated 3-tier fallback lookup:
1. **Branch Standard Cost:** Specific unit cost configured in the branch's local ingredient inventory.
2. **Latest Purchase Price:** Most recent procurement intake price recorded at the branch.
3. **Global Master Cost:** Global fallback unit cost from the master ingredient catalog.

#### Key Financial Metric Indicators

| Metric Card | Description & Operational Value |
|---|---|
| **Gross Revenue** | Total gross intake before deductions, with discount impact shown. |
| **Net Sales** | Total money collected from product sales, excluding delivery fees and refunds. |
| **Order Count** | Total count of successfully completed transactions. |
| **Average Order Value (AOV)** | Average monetary spend per completed order ticket. |
| **Total Discounts** | Aggregate value of regular discounts and statutory Senior Citizen / PWD discounts applied. |
| **Delivery Fees Collected** | Total delivery surcharge collected on mobile delivery orders. |
| **Refunds Issued** | Total monetary value returned to customers through authorized refunds. |
| **Ingredient Costs (COGS)** | Direct raw material costs consumed during food preparation, resolved via the 3-tier cost hierarchy. |
| **Waste Costs** | Financial value of all raw materials logged as spoilage, contamination, or physical stocktake deficit. |
| **Gross Profit** | Net operational margin after subtracting ingredient consumption and waste from net sales. |

> [!TIP]
> **Metric Slide-Over Analysis:** Click any financial card or metric name to open the **Metric Breakdown Panel**, displaying its calculation formula and a line-by-line category contribution breakdown.

---

### 18.3 Tab 2 — Forecasting & Prescriptive Restock Intelligence

The Forecasting engine combines predictive machine learning with an actionable prescriptive procurement assistant.

#### Predictive Modeling Engine (Weighted Linear Regression)
- **Outlier Cleansing:** Automatically strips promotional anomalies and irregular sales spikes outside the $1.5 \times \text{IQR}$ (Interquartile Range) boundary before training.
- **Exponential Recency Weighting:** More recent sales days are weighted exponentially higher than older days to capture emerging trends.
- **Day-of-Week Seasonality:** Computes cyclical multipliers for each day of the week (Monday through Sunday) to account for weekend surges and weekday slowdowns.
- **Damped-Trend Projection:** Damps trend slope extrapolation per future step (0.98 short-term factor; 0.85 long-term factor) to prevent runaway projections.
- **Baseline Floor Guarantee:** Predictions are floored at a minimum of 30% of the recent rolling mean to prevent unrealistically zeroed predictions during brief operational dips.

| Model Horizon | Training Window | Prediction Horizon | Tracked Accuracy Metrics |
|---|---|---|---|
| **Short-Term (Daily)** | Last 60 days of daily sales | Next 7 days | MAE, RMSE, MAPE |
| **Long-Term (Monthly)** | Last 12 months of monthly sales | Next 6 months | Direction (Growing / Stable / Declining) |

#### Prescriptive Restock Recommendations Engine (Actionable Procurement)
The system translates the 7-day and 14-day sales projections directly into ingredient-level supply recommendations:

$$\text{Recommended Quantity} = \text{Projected 14-Day Demand} + \text{Safety Stock} - \text{Current Stock}$$

1. **Stockout-Suppressed Demand Adjustment (+30%):** If an ingredient's current stock is zero, historical sales figures artificially understate true customer demand (you cannot sell what is not in stock). The engine automatically applies a **+30% upward correction** (`projectedDemand * 1.3`) to prevent continuous under-ordering.
2. **Spoilage & Waste-Aware Safety Stock:** High-waste ingredients do not receive a flat safety buffer (which would only lead to further spoilage). The engine scales the safety stock buffer down from 15% to as low as 5% based on the ingredient's historical waste rate:
   $$\text{Safety Stock \%} = \max(0.05, 0.15 - (\text{Waste Rate} \times 0.5))$$
3. **Coverage Days:** Calculates how many days current inventory will last at projected consumption rates:
   $$\text{Coverage Days} = \frac{\text{Current Stock}}{\text{Daily Projected Demand}}$$
4. **Urgency Priority Classification:**
   - **Critical (Red):** Current stock is zero (immediate stockout).
   - **High (Amber):** Current stock covers 3 days or fewer.
   - **Medium (Yellow):** Current stock covers 4 to 7 days.
   - **Low (Green):** Stock comfortably covers the projected 14-day demand plus safety buffer.
5. **Direct Workflow Integration:** Each recommendation card features a direct action button (**Open Stock Workflow** or **Review Stock**) that navigates directly to Stock Ordering (for satellite stores) or Stock Adjustment (for commissary managers).
6. **Network-Wide Restock Summary (`loadNetworkRestockSummary`):** When viewing "All Locations (Global)", Super Admins can click **View Network Restock Summary** to aggregate demand forecasts across all network stores into a single procurement manifest for central commissary purchasing.
7. **Prescriptive Restock CSV Export:** Click **Export Restock List (CSV)** to generate a standardized supplier reorder spreadsheet containing Ingredient Name, Unit, Current Stock, Projected Demand, Safety Stock, Recommended Order Quantity, Coverage Days, and Priority.

---

### 18.4 Tab 3 — Products

Provides granular sales performance analytics for the entire product catalog:

- **Volume & Revenue Ranking:** Best-selling products ranked by units sold, total revenue, average unit price, and percentage contribution to gross sales.
- **Category Performance:** Revenue, volume, and order counts aggregated by product category.
- **Seasonality Pattern Analysis:** Plots the top 3 best-selling products across time. Toggle between:
  - **Weekly Mode:** Displays demand patterns across days of the week (Monday through Sunday).
  - **Monthly Mode:** Displays annual demand patterns across months (January through December).

---

### 18.5 Tab 4 — Operations

Monitors operational efficiency, peak trading windows, and sales channels:

- **24-Hour Hourly Trading Heatmap:** Plots order count and revenue by hour (00:00 to 23:00) to identify rush hours and optimize staff shift scheduling.
- **Branch Performance Leaderboard:** Ranks every branch by total revenue, order volume, and percentage share of network sales. Fully searchable and paginated.
- **Fulfillment Channel Split:** Compares Walk-in POS transactions against online mobile delivery app orders.
- **Payment Method Proportions:** Ratio of Cash transactions versus GCash (Static QR) digital payments.

---

### 18.6 Tab 5 — Sales (Order Ledger)

A raw, paginated Consolidated Ledger of all individual orders within the selected branch and date scope:

- **Search & Filter:** Search by Order Reference Number, Customer Name, or Payment Method.
- **Ledger Columns:** Reference Number, Branch, Order Type, Payment Method, Order Status, Attending Cashier, and Total Amount.
- **Detail Slide-Over:** Click any order row to open the complete Order Detail view, displaying itemized customizations, discount breakdowns, and audit timelines.

---

## 19. System Settings and Platform Configuration

### 19.1 Overview

The System Settings module provides centralized administrative control over business identity, POS behavior, receipt printing, inventory thresholds, and customer feedback.

- **Super Admin:** Full access to all 8 configuration tabs.
- **Branch Administrator:** Scoped access to operational tabs (*Inventory*, *POS Configuration*, *Reviews*, *Thermal Printer*).
- **Cashier:** Access is restricted.

---

### 19.2 Tab 1 — General (Business Identity)

- **Authorized Role:** Super Admin only.

| Setting | Description |
|---|---|
| **Business Name** | Official trading and brand name (appears on receipts, invoices, and app headers). |
| **Business Email** | Primary administrative contact email. |
| **Business Phone** | Official customer service contact number (+63 format). |
| **Business TIN** | Official Tax Identification Number (format: `000-000-000-000`). |
| **Business Address** | Full physical headquarters address with PSGC hierarchy and GPS coordinates. |
| **Business Logo** | Upload official brand logo (PNG, JPG, or WEBP up to 1 MB). Appears on printed receipts. |

---

### 19.3 Tab 2 — Receipts

- **Authorized Role:** Super Admin only.

| Setting | Description |
|---|---|
| **Station Slips** | Configurable titles and subtitles for Kitchen Order Slips and Barista Drink Slips. |
| **Show Logo on Receipt** | Toggle to print the uploaded brand logo at the top of customer receipts. |
| **Show VAT on Receipt** | Toggle to include statutory 12% VAT calculations and VATable sales lines. |
| **Footer Message** | Custom promotional or appreciation message printed at the bottom of customer receipts. |
| **Return Policy Text** | Return, exchange, or refund policies printed on receipts. |
| **Number of Copies** | Default number of receipt copies to print per transaction (1 to 3 copies). |
| **Review QR Code** | Toggle to print an automated feedback survey QR code linked to the branch's review portal. Includes an on-screen live receipt preview. |

---

### 19.4 Tab 3 — Inventory (Thresholds & Automated Alerts)

- **Authorized Roles:** Super Admin, Branch Administrator.

| Setting | Description |
|---|---|
| **Low Stock Threshold** | Stock balance that triggers an amber alert badge and warns staff to reorder. |
| **Critical Stock Threshold** | Severe deficit level that triggers a red pulsing badge. POS blocks sales when ingredients hit zero. |
| **Expiry Alert Days** | Number of days prior to expiration that triggers an *Expiring Soon* amber alert (1 to 365 days). |
| **Auto-Notifications** | When enabled, dispatches the automated daily 6:00 AM stock deficit email to branch managers. |

---

### 19.5 Tab 4 — POS Configuration

- **Authorized Roles:** Super Admin, Branch Administrator.

| Setting | Description |
|---|---|
| **Service Charge Rate** | Percentage applied automatically to Dine-in orders (e.g., `0.10` for 10%). |
| **Regular Discount Rate** | Default percentage applied when applying a regular discount in POS (e.g., `0.10` for 10%). |
| **Senior/PWD Discount Rate** | Statutory discount rate for Senior Citizens and PWDs (e.g., `0.20` for 20% VAT-exempt). |
| **POS Terminal Title** | Store header label displayed on the POS interface. |
| **Enabled Order Types** | Toggle fulfillment types available at the terminal (*Dine-in*, *Take-out*, *Delivery*, *Pick-up*). |
| **Enabled Payment Methods** | Toggle payment options accepted at the terminal (*Cash*, *GCash*). |
| **GCash Merchant Name** | Official merchant account name displayed on the Static QR payment screen. |
| **GCash Mobile Number** | Registered 10-digit GCash mobile number. |
| **GCash Static QR Image** | Upload the store's static GCash QR code image scanned by customers at the register. |

---

### 19.6 Tab 5 — Reviews (Customer Survey Configuration)

- **Authorized Roles:** Super Admin, Branch Administrator.

| Setting | Description |
|---|---|
| **Form Title** | Headline displayed on the public customer feedback page (`/review/{branch}`). |
| **Form Subtitle** | Descriptive subheading text guiding customer responses. |
| **Questionnaire Builder** | Create, reorder, or delete survey questions. Configure Question Type (**Rating: 1 to 5 Stars** or **Open Text**) and mark questions as Mandatory or Optional. |

> [!NOTE]
> **Live Interactive Simulator:** Changes to survey questions are rendered instantly in a side-by-side mobile device frame preview.

---

### 19.7 Tab 6 — System (Operating Context & Sidebar Toggling)

- **Authorized Role:** Super Admin only.

| Setting | Description |
|---|---|
| **Operating Branch Context** | Assigns the Super Admin to a specific branch environment, allowing direct operational use of POS, KDS, and stock ordering as a branch member. Selecting *"General Headquarters (No Branch)"* restores enterprise global overview. |
| **Hide Operational Modules** | When enabled (or when operating from General Headquarters), hides branch-specific operational modules (POS, KDS) from the sidebar for a distraction-free executive view. |

---

### 19.8 Tab 7 — Thermal Printer

- **Authorized Roles:** Super Admin, Branch Administrator.

| Setting | Description |
|---|---|
| **Printer Activation** | Master toggle to enable or disable automatic ESC/POS printing upon transaction completion. |
| **Connection Protocol** | Select **Web Bluetooth** (direct pairing from Chrome/Edge) or **Wired/USB/Serial** (local Windows printer spooler). |
| **Auto-Cut Paper** | Sends an ESC/POS paper cutter command at the end of each printed ticket. |
| **Test Print Connection** | Prints a standardized alignment and hardware diagnostic voucher to verify connection. |

---

### 19.9 Tab 8 — Audit Logs

- **Authorized Role:** Super Admin only.

A consolidated, tamper-proof activity trail merging three critical operational streams:
- **Order Audit Logs:** Placed orders, status transitions, voids, refunds, and attending cashier IDs.
- **Stock Movement Logs:** Intake batches, wastage logs, reconciliation adjustments, and transfer dispatches.
- **User Authentication Logs:** Account creations, role modifications, and login sessions.

All log entries are indexed chronologically with exact timestamps, user names, and IP addresses.

---

## 20. Notifications and Alert Management

### 20.1 Overview

The Notification system delivers instant operational awareness across kitchen operations, raw material inventory thresholds, customer delivery requests, and inter-branch logistics.

- **Authorized Roles:** Super Admin, Branch Administrator, Cashier.

#### Dual Delivery Interfaces
1. **Header Navigation Notification Bell:**
   - Polls background updates every 5 seconds.
   - Displays a red badge counter of unread alerts.
   - Clicking the bell opens a quick flyout drawer displaying the 5 most recent alerts with relative timestamps and mark-as-read buttons.
2. **Dedicated Notification Center (`/notifications`):**
   - Full-page management console for searching, filtering, and bulk managing historical alerts.

---

### 20.2 Scope Isolation Rules

All notifications enforce strict role and branch isolation:
- **Cashiers & Kitchen Staff:** Receive alerts strictly for their assigned branch (e.g., new delivery orders, low ingredients).
- **Branch Administrators:** Receive branch-specific operational, inventory, and transfer alerts.
- **Super Admins:** Receive enterprise-wide alerts across all store locations, or filtered to a specific branch when one is selected.

---

### 20.3 Notification Types

| Alert Type | System Trigger Event |
|---|---|
| **Stock Alert** | An ingredient drops below the configured Low Stock or Critical threshold. |
| **Expiry Alert** | A physical stock batch enters the 7-day expiration window, or passes its expiration date. |
| **Stock Transfer** | A satellite store submits a supply request, or HQ approves and dispatches an order. |
| **Order Alert** | A new online mobile delivery order is placed by a customer and awaits acceptance. |
| **Customer Feedback** | A customer submits a review through the receipt QR code survey. |

---

### 20.4 Notification Archive Controls

- **Filter Tabs:** Filter across *All*, *Stock Alerts*, *Expiry Alerts*, *Order Updates*, or *Customer Reviews*.
- **Live Search:** Search alerts by headline, ingredient name, or reference ID.
- **Mark as Read:** Click individual alerts to mark them as read, or click **Mark All as Read** to clear badge counters.
- **Intelligent Deep-Link Navigation:** Clicking any notification marks it as read and navigates directly to the relevant operational console (e.g., clicking an Expiry Alert opens Stock Management, switches to the Expiry Tracking tab, and filters to that specific batch).
- **Bulk Cleanup:** Click **Clear All Notifications** with confirmation to permanently delete alerts for the active branch.
- **Automated 60-Day Purge:** A scheduled command (`php artisan notifications:cleanup`) automatically purges notifications older than 60 days without affecting order or ledger histories.

---

## 21. Profile Settings

### 21.1 Overview

The Profile Settings module allows every authenticated employee to manage their personal account identity, contact information, password security, and custom avatar.

- **Target Roles:** Super Admin, Branch Administrator, Cashier, Delivery Rider.
- **Access:** Click your profile avatar in the top-right corner of the navigation bar and select **Profile Settings** (or `/profile`).

---

### 21.2 Managing Profile Details

1. Navigate to the **Personal Information** section.
2. Update the profile fields:
   - **First Name, Middle Name, Last Name:** Auto-capitalized legal employee name.
   - **Email Address:** Primary login email. Changing this requires password verification.
   - **Phone Number:** 10-digit Philippine mobile contact number.
3. Click **Save Changes** to commit updates.

---

### 21.3 Built-in Avatar Selection

Personalize your account identity across the system interface:
- Click **Change Avatar** to open the modal picker.
- Choose between two illustrated collections: **Foods** (Takoyaki, Ramen, Bento, Drinks) and **Animals** (Shiba Inu, Cat, Panda, Fox).
- Each avatar features a modern gradient tile background.
- Select any avatar to apply it immediately across all navigation headers.
- Click **Reset Avatar** to revert to standard initial-based letter avatars.

---

### 21.4 Changing Account Password

1. Navigate to the **Update Password** section.
2. Enter your **Current Password** to verify account ownership.
3. Enter your **New Password** (minimum 8 characters; requires uppercase, lowercase, and numeric characters).
4. Re-enter the password in **Confirm New Password**.
5. Observe the live **Password Strength Meter** (*Weak*, *Fair*, *Strong*). Use the show/hide eye icon to unmask text if needed.
6. Click **Update Password**. On success, the password fields are cleared and security audit logs are updated.

---

## Appendix A — Mobile Ecosystem

### A.1 Customer Delivery Mobile Application

The system exposes a secured REST API supporting a dedicated customer mobile application:
- **Account Registration & OTP:** Customers sign up with email and mobile number, verified via single-use 6-digit OTP codes.
- **Branch Geolocation:** The app detects customer GPS coordinates and lists the nearest active branches.
- **Menu Browsing & Customization:** Real-time catalog browsing respecting branch-specific active/hidden product toggles and ingredient availability.
- **Order Placement:** Customers place orders for Delivery or Pick-up. Placed orders push instant notifications to the store's Order Management console.
- **Live Order Tracking:** Customers receive live status notifications (*Accepted → Preparing → Handed to Rider → Delivered*).

---

### A.2 Rider Delivery Mobile Application

The Rider API powers a purpose-built logistics mobile app for store delivery personnel:
- **Real-Time Dispatch:** When a KDS or Order Management operator assigns a rider, the delivery ticket is dispatched to the rider's mobile device with sound and haptic notifications.
- **Customer Navigation:** Provides GPS routing and map directions directly to the customer's delivery coordinates.
- **Digital Proof of Delivery (POD):** Upon arrival, the rider captures a photo of the handed-over food parcel through the app camera.
- **Automatic Order Completion:** Uploading the POD photo automatically marks the order as *Delivered*, embeds the photo in the store's Order Management inspector, and completes the transaction ledger.

---

## Appendix B — Troubleshooting

| Symptom | Probable Cause | Corrective Action |
|---|---|---|
| **Cannot log in to the web console** | Incorrect credentials or account deactivated | Verify email and password. Use the 3-step Forgot Password OTP flow. If still failing, contact your Administrator to verify account active status. |
| **Password reset OTP email not received** | Email delay or spam filter | Wait 60 seconds, then click **Resend Code**. Inspect Spam/Junk folders. Verify email server configuration. |
| **Product is grayed out / disabled on POS** | Required recipe ingredient is depleted | The POS verifies ingredient stock in real time. Perform a Stock Adjustment or receive a Branch Stock Transfer to replenish inventory. |
| **GCash payment modal cannot be closed** | GCash payment was marked as verified | By design, verified GCash transactions are locked to prevent unrecorded cashflow. Complete the order, then void it from Order Management if necessary. |
| **Receipt not printing on thermal printer** | Bluetooth pairing lost or paper out | Verify the printer is powered on and paired in Chrome/Edge via Web Bluetooth. Use **Test Print Connection** in System Settings (Thermal Printer tab). |
| **Cannot assign rider in KDS or Order Management** | No active riders provisioned at the branch | Provision a new user with the **Delivery Rider** position in User Management and assign them to the branch. |
| **Options Library import is blocked** | An option group with the same name already exists on the product | Rename or remove the existing option group on the product before importing the library template. |
| **Category cannot be deleted** | Products or ingredients are currently assigned | Re-assign all associated items to another category. The system displays the blocking item count. |
| **Forecasting shows "Insufficient Data"** | Not enough historical data points | The regression model requires at least 5 clean daily sales points (or 3 monthly points). Accumulate transaction history. |
| **Admin receives 403 Forbidden on Category Management** | Access restricted to Super Admin | Category taxonomies are restricted to Super Admins. Branch Admins should request changes through headquarters. |

---

## Appendix C — Frequently Asked Questions

#### Can a Cashier perform stock adjustments or waste logging?
No. Cashiers have front-of-house operational access (POS, KDS, Order Management) and view-only access to inventory alerts. Stock Adjustments, Waste Logging, and Branch Transfers require Administrator or Super Admin permissions.

#### Can a Branch Administrator change product selling prices?
No. Product selling prices and recipe definitions are globally defined by the Super Admin. Branch Administrators can only toggle whether a product is Active or Hidden for their assigned store.

#### What happens if a branch is marked as Inactive in Branch Management?
POS operations for that branch are immediately suspended, and the store is hidden from the customer delivery mobile app. All historical order ledgers, staff records, and inventory logs are safely preserved.

#### What is the operational difference between a Refund and a Void?
- **Refund:** Returns a specified monetary sum to the customer while preserving the original order ledger as *Refunded* or *Partially Refunded*.
- **Void:** Completely nullifies a transaction that should never have existed (e.g., test orders, cashier mistakes). Voiding reverses all recipe stock deductions back into branch inventory balances.

#### What is the difference between an Option Group and an Option Template?
- **Option Group:** A customization group attached directly to one specific product.
- **Option Template:** A reusable master template stored in the centralized Options Library that can be imported into and synchronized across multiple products.

#### Can a Super Admin use the POS or KDS terminals?
Yes. Super Admins can open System Settings, navigate to the **System** tab, and select an **Operating Branch**. This scopes their active session to that specific store, enabling all operational modules.

---

## Appendix D — Glossary

| Term | Operational Definition |
|---|---|
| **AOV (Average Order Value)** | Total net sales revenue divided by the total number of completed order transactions. |
| **COGS (Cost of Goods Sold)** | The total acquisition cost of raw materials and ingredients consumed during order preparation, calculated via recipes. |
| **FEFO (First-Expired, First-Out)** | The inventory deduction rule that automatically retires stock batches with the earliest expiration dates first. |
| **Financial Ledger** | An immutable database record storing every Sale, Refund, and Void event to guarantee financial audit integrity. |
| **IQR (Interquartile Range)** | A statistical distribution method used by the predictive analytics engine to cleanse anomalous sales spikes before running regression. |
| **KDS (Kitchen Display System)** | Digital kitchen operations display used by cooks and kitchen staff to track ticket preparation times and mark orders ready. |
| **Modifier** | An optional add-on or topping selected by a customer (e.g., *"Extra Bonito Flakes"*) that carries an additive price and ingredient deduction. |
| **Option Group** | A set of choices attached to a product defining flavors, sizes, or preparations (e.g., *"Takoyaki Size: 4 pcs, 8 pcs, 12 pcs"*). |
| **Option Template** | A standardized customization blueprint stored in the Options Library, complete with pre-mapped ingredient recipe deductions. |
| **OTP (One-Time Password)** | A cryptographically secure, 6-digit numeric token sent via email for password recovery and identity verification. |
| **POS (Point of Sale)** | The front-of-house checkout terminal used by cashiers to record orders, apply discounts, and process payments. |
| **PSGC** | Philippine Standard Geographic Code — the official national geographic hierarchy (Region, Province, City, Barangay) used for address records. |
| **RBAC** | Role-Based Access Control — the security framework governing user permissions across Super Admin, Administrator, Cashier, and Rider roles. |
| **Stock Batch** | A physical intake of raw materials tracked with its own lot reference, acquisition cost, and supplier expiration date. |
| **Weighted Linear Regression** | The mathematical algorithm used by Business Intelligence to forecast future demand by placing higher weight on recent sales days. |

---

## Document Control

| Field | Value |
|---|---|
| **Document** | Mister Takoyaki Centralized Sales and Management System User Manual |
| **System** | Web Application & Mobile Ecosystem |
| **Version** | 1.4 |
| **Status** | Approved & Released |
| **Prepared By** | Development & Architecture Team |
| **Approved By** | Executive Management |
