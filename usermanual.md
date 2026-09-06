# Mister Takoyaki Centralized Sales and Management System
## User Manual — Version 1.3 (August 2026)

| Field | Value |
|---|---|
| Document | Mister Takoyaki User Manual |
| System | Centralized Sales and Management System |
| Version | 1.3 |
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

The Dashboard is the operational command center. It combines real-time financial KPIs, multi-channel sales volume, predictive trends, inventory health, branch geographic mapping, and live terminal order activity in a single view.

- **Target Audience:** Super Admin (global and per-branch), Administrator (branch-scoped), Cashier (branch-scoped)
- **Refresh Interval:** Live background polling every 10 seconds. Recalculates dynamically upon order completion, refund, void, or stock adjustment.

### 5.2 Navigation Controls

- **Branch Selector:** Switch between All Locations (Global/Enterprise Overview) and specific branch views. For Branch Admins and Cashiers, the branch is fixed to their assigned store.
- **Date Preset Range:** Scopes all dashboard cards, charts, and metrics simultaneously. Available presets are: Today, Yesterday, This Week, Last 7 Days, This Month, Last 30 Days, This Year, All Time, or Custom Date Range.
- **Export Functionality:** Click the **Export** button in the top control bar to generate reports in PDF or CSV format.

### 5.3 Financial KPI Cards

| KPI Card | Description |
|---|---|
| Gross Revenue | Total revenue before deductions, including discounts added back in. |
| Gross Profit | Net sales minus COGS and wastage, displayed with a gross profit margin percentage. |
| Net Sales | Revenue after excluding delivery fees. |
| Average Order Value (AOV) | Average amount collected per completed transaction. |
| Ingredient Costs (COGS) | Total cost of ingredients consumed, auto-calculated from product recipes. |

Click any KPI card to open the **KPI Detail Slide-Over Breakdown**, which shows the calculation formula and a category-by-category breakdown. For Gross Profit, the panel shows a running deduction view: Total Revenue, minus Resource Consumption (COGS), minus Variance and Spoilage, resulting in the Net Earnings with margin percentage.

### 5.4 Operations Index

Four quick-count status badges are displayed:

- **Menu Items:** Count of currently active products in the catalog.
- **Tracked Ingredients:** Total tracked ingredients in the system.
- **Inventory Alerts:** Combined count of low-stock and soon-to-expire items. Highlighted in red when greater than zero.
- **Active Staff:** Ratio of active accounts to total registered accounts for the selected branch.

### 5.5 Spend and Revenue Activity Chart

An interactive chart visualizes financial performance over time. Use the selector to switch between three metric views:

- **Gross Sales vs. Ingredient Costs:** Overlays Gross Sales against Ingredient Costs (COGS).
- **Order Volume:** Displays total completed order counts alongside a predictive trend forecast line.
- **Gross Profit vs. COGS:** Compares Gross Profit against COGS with a predictive forecast projection.

When the selected date range is a single day, the chart automatically switches to a 24-hour hourly breakdown.

### 5.6 Branch Live Map (Super Admin and Multi-Branch Only)

An interactive map visualizes the geographic distribution and sales performance of all branch locations.

- Toggle between Satellite, Street View, and Vector View using the dropdown selector.
- Hover over any branch pin to view total sales and its percentage share of network-wide sales for the period. The top-performing branch is highlighted with a pulsing marker.
- Click the expand icon to open a full-screen version of the map.
- A leaderboard below the map ranks all branches by total revenue and percentage contribution.

### 5.7 Revenue Channels and Payment Methods

- **Fulfillment Split:** Percentage split and order count per order type — Dine-in, Take-out, Delivery, and Pick-up.
- **Payment Method Distribution:** Real-time breakdown of transactions processed via Cash or GCash (Static QR), showing transaction count and total monetary value.

### 5.8 Inventory Health and Usage Velocity

- **Usage Velocity:** Ranks the fastest-moving ingredients by daily consumption velocity with visual progress bars.
- **Efficiency Score:** A battery-style Efficiency Score (0 to 100%) comparing ingredient waste value against ingredient sales value.
  - Green (80% or above): Healthy inventory turnover and minimal waste.
  - Amber (50% to 80%): Moderate variance; monitor usage.
  - Red (below 50%): Critical waste or variance detected.

### 5.9 Top Products, Stock Alerts, and Live Order Feed

- **Top 5 Products:** Displays the 5 best-selling products by units sold and generated revenue for the period.
- **Stock Alerts:**
  - **Shortage tab:** Lists ingredients below their configured minimum threshold with current stock levels.
  - **Expiry tab:** Lists batches expiring within 7 days, or already expired (shown in red), with remaining days displayed.
- **Live Order Feed:** A real-time feed showing the 10 most recent transactions, displaying order reference, elapsed time, status, and amount.

---

## 6. POS and Orders

### 6.1 Opening the POS Terminal

- **Target Roles:** Super Admin, Administrator, Cashier.
- Navigate to **POS and Orders** (or `/pos`) from the sidebar.
- The top bar contains Category Tabs, a Search Bar, a Held Orders button, and a Layout Sort toggle.
- The main area displays an interactive Product Card Grid with instant live search and category filtering.
- The right panel (desktop) or bottom drawer (mobile) shows the real-time Order Summary, line items, discounts, fulfillment method, table reference, and payment triggers.

#### Stock Badges on Product Cards

| Status | Description |
|---|---|
| In Stock | Ample stock available. The card shows the available quantity. |
| Low Stock | 10 or fewer units remaining based on ingredient stock. |
| Out of Stock | One or more required recipe ingredients are depleted. The card is grayed out and Add to Cart is disabled. |

### 6.2 Adding Items to the Cart

#### Standard (Non-Customizable) Items

1. Tap the product card or click **Add to Cart**.
2. The item is instantly added to the cart with a quantity of 1.
3. Tapping again increases the quantity. When the cart quantity reaches the maximum physical stock limit, the button is disabled.

#### Customizable Items with Options and Modifiers

1. Tap the product card to open the Customization Modal.
2. For option groups, select the required choices. Required groups must have a valid in-stock selection before the item can be added.
3. Out-of-stock options are disabled and cannot be selected. A configured default option is pre-selected if in stock.
4. Select any optional add-ons or modifiers.
5. Tap **Add to Order** to confirm, or **Cancel** to close without adding.
6. Adding the same product with identical options increases the quantity of the existing cart line rather than creating a duplicate.

### 6.3 Managing Cart Items and Special Instructions

- Use the quantity stepper (+/-) to adjust line item quantities. Reducing to zero removes the item. You cannot exceed available stock.
- Click the trash icon on any cart row to remove it. A confirmation prompt appears.
- Click **Clear Cart** to remove all items from the cart. A confirmation prompt appears.
- Tap the note icon on any cart line item to open the **Item Customization & Discount Modal**:
  - Enter free-text kitchen notes for that item.
  - Apply a per-item Regular Discount or Senior/PWD Discount.
  - Tap **Apply** to save.

### 6.4 Fulfillment Method and Table Reference

- Use the **Order Type** dropdown to specify fulfillment type: Dine-in, Take-out, Delivery, or Pick-up.
- A configured Service Charge is applied automatically only when the order method is Dine-in.
- Enter a Table or Reference Number. When **Dine-in** is selected, entering a **Table Number** is mandatory to ensure orders reach the dining room accurately. For Take-out, Delivery, or Pick-up, customer reference information or phone number is entered.

### 6.5 Holding and Restoring Draft Orders

#### Holding a Cart

1. Click the hold icon in the Order Summary header.
2. The system generates a unique reference code and saves the order items, customizations, table number, and order type without deducting ingredient stock.
3. The cart is cleared for the next transaction.

#### Restoring or Deleting Held Orders

1. Click the Held Orders icon in the top bar. The badge shows the number of active held drafts.
2. The Saved Drafts panel displays all branch drafts with table numbers, item counts, creation timestamps, and order values.
3. Click **Restore** to restore the draft into the active POS cart and remove it from the draft ledger.
4. Click the trash icon to delete an abandoned draft permanently.

### 6.6 Processing Payments

1. Tap **Proceed to Payment** (or **Place Order**) to open the payment confirmation screen, which shows the itemized order breakdown on the left and payment method selection on the right.
2. Select a payment method:

| Payment Method | Process |
|---|---|
| Cash | Enter the amount tendered or tap a quick tender button (Exact, 100, 500, 1000). The system instantly computes and displays the change amount. The amount tendered cannot be less than the total due. The amount tendered and calculated change are permanently recorded in the order database and printed on customer receipts. |
| GCash (Static QR) | The store's static GCash QR code, account name, and account number are displayed. The customer scans and pays. The cashier verifies receipt on the store phone and clicks **Mark as Verified**. A confirmed payment badge appears. |

3. Tap **Place Order** to finalize the transaction.

> [!WARNING]
> **Verified Payment Protection:** Once a GCash payment is marked as verified, the payment modal cannot be cancelled and the cart cannot be cleared. This prevents unrecorded receipts. To cancel a verified transaction, complete the order first, then void it from Order Management (see Section 7.8).

### 6.7 Thermal Receipt Printing

- Placing an order automatically dispatches receipt generation to connected printers.
- The system supports Web Bluetooth thermal printers (compatible ESC/POS devices) connectable directly from the browser without print dialogs.
- When multiple slips are printed (for example, a customer receipt followed by kitchen or barista slips), a modal alerts the cashier to tear off the first slip before printing continues. A 15-second auto-continuation countdown is included.

### 6.8 Menu and Category Layout Customization

- **Authorized Roles:** Super Admin, Branch Administrator.
1. Tap **Customize Layout** in the top bar to activate Layout Edit Mode.
2. Drag and drop category tabs horizontally to reorder them. The "All" tab is permanently locked in the first position.
3. Drag product cards using the drag handle icon to reorder them.
4. Tap **Save Layout** to save the custom layout. The arrangement is saved per branch and does not affect other stores.

---

## 7. Order Management

### 7.1 Overview

Order Management is a centralized hub for managing online mobile delivery orders, in-store POS transactions, and historical order records. It provides fulfillment progression, rider assignment, refund and void processing, thermal receipt printing, and complete audit logging.

- **Target Roles:** Super Admin, Branch Administrator, Cashier (restricted to their assigned branch).
- The screen auto-refreshes in sync with mobile app order placements, KDS changes, and rider dispatch updates.

### 7.2 Tab Navigation and the 1-Hour Transition Window

The module is organized into three tabs:

- **Delivery Orders:** Displays incoming and active orders placed via the mobile delivery application with statuses: Pending, Preparing, Ready, Handed to Rider, and Out for Delivery.
- **POS Orders:** Displays in-store POS transactions and saved drafts with statuses: Drafted, Pending, and recently completed in-store sales.
- **Order History:** The permanent historical ledger for all completed, cancelled, voided, refunded, and partially refunded orders.

> [!NOTE]
> **1-Hour Operational Transition Window:** When an order is completed, cancelled, voided, or refunded, it remains visible in its original operational tab for 1 hour. This allows staff to quickly reprint receipts, process refunds, or void erroneous entries. After 1 hour, the order automatically moves to Order History.

### 7.3 Order Health Overview

Four live operational KPI cards are displayed above the order table:

| Card | Data Displayed |
|---|---|
| Total Orders | Total order volume within the active tab scope. |
| Active Orders | Unresolved orders currently in progress. A visual alert appears if any order has been sitting unresolved for 24 hours or more. |
| Completed Today | Count of all orders successfully served and cleared today. |
| Today's Revenue | Net income collected today (Gross Sales minus Refunds, excluding delivery fees and voided orders). |

### 7.4 Search and Filtering

- **Search Filter:** Real-time search by Reference Number, Customer Name, or Customer Phone Number.
- **Status Filter:** Shows only the status options valid for the active tab.
- **Date Range Filter:** Quick presets (Today, Last 7 Days, Last 30 Days, All Time) or custom calendar start and end dates.

### 7.5 Managing Delivery App Orders

1. **Accepting an Order:** Open a Pending delivery order and click **Accept Order**. Status updates to Preparing and the ticket is pushed to KDS.
   > [!IMPORTANT]
   > **1-Hour Auto-Rejection Safeguard:** If an incoming mobile delivery order remains in Pending status for more than 1 hour without being accepted by a cashier or manager, the system's background scheduler automatically cancels and marks the order as Rejected with reason "Auto-cancelled: Order not accepted within 1 hour". This protects customers from indefinite waiting times.
2. **Rejecting an Order:** Click **Reject Order**, enter an optional reason, and confirm. Orders can only be manually rejected within 24 hours of placement.
3. **Dispatching to Rider:** When food is ready, click **Assign Rider**, select an active rider from the dropdown, and click **Assign**. Status advances to Handed to Rider and a notification is sent to the rider's app.
4. **Completing an Order:** Once drop-off is verified, click **Complete Order** to finalize the order.

### 7.6 Managing POS Draft Orders

- **Resume Draft:** Open a Drafted order and click **Resume Order**. The items, options, order type, and table reference are loaded into the active POS session and the user is redirected to POS.
- **Delete Draft:** Click **Delete Draft** to permanently remove an abandoned held cart.

### 7.7 Processing Refunds

- **Authorized Roles:** Super Admin, Admin.
- Available for completed orders within the refund window that have an eligible refundable balance.

1. Open the completed order and click **Refund**.
2. Enter the Refund Amount (must not exceed the refundable balance) and a mandatory Refund Reason.
3. Click **Submit Refund**. The refunded amount is logged, deducted from revenue totals, and recorded on the order ledger.

### 7.8 Voiding an Order

- **Authorized Roles:** Super Admin, Admin.

1. Open the order and click **Void Order**.
2. Confirm the action.
3. Voiding is permanent and irreversible. The order is excluded from all revenue calculations. Inventory deductions are reversed where applicable. This applies even to orders where a GCash payment was already verified and locked at the POS.

### 7.9 Order Detail Panel

Click any order row to open the detailed slide-out inspector. It has two tabs:

#### General Information Tab

- Transaction source (App or POS), assigned branch, status, cashier or processor name, and creation timestamp.
- Customer details for app orders: name, phone number, and delivery address.
- Proof of Delivery section: rider photo captured upon delivery (click to view full-screen), rider name, capture timestamp, and a link to view the delivery location on a map.
- Itemized breakdown: products, chosen options, modifiers, quantities, unit prices, and line totals.
- Financial summary: subtotal, discount, delivery fee, grand total, and any refunded amounts.

#### Activity Timeline Tab

- A chronological activity timeline showing every status milestone with timestamps and the user who triggered each change.

### 7.10 Viewing and Printing Receipts

1. Click **Print Receipt** from the order inspector to open the thermal receipt preview, which includes the business name, logo, address, TIN, itemized product list, financial summary, footer message, and a customer review QR code.
2. Click **Print** to send the job to the paired thermal printer.

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

Switching to the Expiry Tracking tab displays all tracked physical stock batches.



| Status | Condition |
|---|---|
| Expired | Batch expiry date is earlier than today. Displayed with a red warning badge. |
| Expiring Soon | Batch expires within the configured alert window (7 days or fewer). |
| Fresh | Batch expires beyond the alert window. |
| Non-Perishable | No expiration date set. |



1. Locate the expired batch in the Expiry Tracking table.
2. Click .
3. Review the Batch Disposal confirmation showing the batch ID, ingredient name, and quantity to be discarded.
4. Click . The batch quantity is immediately zeroed out, the branch stock balance is decremented, and a permanent waste movement is logged with the responsible user's ID and timestamp.

### 12.6 Automated Daily Email Alerts

The system features an automated notification service that executes once per day. Super Admins receive a consolidated report for all branches; Branch Admins receive a localized report for their branch. The alert contents include all low-stock items needing reorder, batches expiring in the next 7 days, and batches requiring immediate disposal. A concurrency lock prevents duplicate emails when multiple staff log in simultaneously.

---

## 13. Stock Adjustments and Physical Count Reconciliation

### 13.1 Overview

The Stock Adjustment and Inventory Logistics module records supplier procurement, kitchen waste, manual inventory adjustments, and physical stock reconciliations. It is the immutable audit and reconciliation engine.

-  Super Admin and Branch Admin.
-  Cashier.

 Only the designated Main Branch can record supplier Procurement (Stock In). Satellite branches receive stock through Branch Stock Transfers (Section 14) and can only record Waste or Stock Out entries.

 Any stock reduction (waste, stock out, or negative reconciliation variance) is processed by automatically deducting from the earliest-expiring batch first.

### 13.2 Audit Ledger and Period KPI Cards

The top of the ledger displays live metrics based on the active date filter:

| Metric | Description |
|---|---|
| Period Logs | Total number of inventory change movements recorded in the period. |
| Waste Count | Total quantity of spoiled, damaged, or expired stock discarded. |
| Restock Value | Total monetary acquisition cost of procurement receipts recorded. |
| Out Count | Total count of items manually reduced or checked out. |


- Search by Reference ID, ingredient name, or remarks.
- Use the date filter presets (Today, This Week, This Month, All Time) or a custom date range.
- Click  to download an immutable audit spreadsheet formatted with Date, Branch, Ingredient, Type, Quantity, Reference ID, and Attendant.

### 13.3 Adjustment Queue Engine

Instead of saving adjustments one by one, the system uses an Adjustment Queue to stage and review multiple movements before committing them to the database.



| Type | Description |
|---|---|
| Procurement / Stock In | Used when receiving fresh deliveries from suppliers. Creates a new stock batch with reference, quantity, and expiry date. Main Branch only. |
| Waste | Logs spoiled, contaminated, or expired ingredients. Deducts stock via FEFO and flags the movement as kitchen loss. |
| Stock Out | Logs inventory consumed for non-sales purposes (for example, taste testing, promotions). Deducts stock via FEFO. |
| Return to Supplier | Reverses defective stock deliveries and logs supplier return credits. |



1. Navigate to Stock Adjustment.
2. Click .
3. In the sidebar, choose an ingredient, enter a quantity, select the movement type, and click .
4. Repeat for additional items.
5. Enter optional notes.
6. Click  and confirm. All movements are committed as a single atomic transaction.



1. Ensure the active branch is set to the Main Branch.
2. Click .
3. Select an ingredient with bulk packaging defined.
4. Select the packaging unit button (for example, Box) and enter the quantity received.
5. Enter the purchase price per unit and the supplier expiry date.
6. Click , review the queue, and click .

When a bulk unit is selected and a purchase price is entered, the system converts the cost to a per-base-unit rate and updates the ingredient master cost so future profitability calculations reflect the new purchase price.

### 13.4 Physical Count Reconciliation

The Stock Reconcile mode streamlines end-of-day or end-of-month physical stocktaking.



1. Click  from the main adjustment toolbar.
2. The system loads every ingredient for the selected branch alongside its current ledger balance (System Quantity).
3. Staff enter the physically counted inventory in the Actual Quantity column.
4. The system calculates the variance: Actual Quantity minus System Quantity.
   - Negative variance (deficit): The system runs a FEFO deduction to retire the missing stock and logs the movement.
   - Positive variance (surplus): The system creates a new untracked batch and increments the ledger balance.
5. Click  and confirm. Only rows with entered actual counts are updated; untouched rows remain unchanged.

---

## 14. Branch Stock Ordering and Inter-Branch Transfers

### 14.1 Overview

The Branch Stock Ordering and Transfer System manages internal supply chain replenishment between the central commissary (Main Branch) and satellite retail branches.

The system operates across two consoles:

1.  The satellite branch interface where branch managers audit deficits, assemble replenishment carts, submit orders, and confirm delivery receipts.
2.  The central commissary console where Super Admins review pending requests, adjust approved quantities, approve or reject orders, and dispatch physical transfers.

>  The central Main Branch acts exclusively as the internal supplier. It cannot initiate stock requests to itself. If the active branch context is set to the Main Branch, the ordering interface will display an access restriction.

### 14.2 Satellite Branch Ordering Console

Satellite branch managers manage replenishment through three panels:

| Panel | Purpose |
|---|---|
| Active Requests | Track pending, approved, and in-transit orders. |
| New Stock Request | Interactive cart with packaging selectors and low-stock helpers. |
| Transfer History | Historical archive of all delivered and cancelled internal shipments. |


- The ordering interface monitors branch ingredient balances against configured minimum thresholds.
- Deficit items display an amber warning with an Add to Cart shortcut.
- Clicking the shortcut automatically calculates the replenishment quantity and stages it into the order cart.


- When entering a requested quantity, the system checks the live inventory balance at the Main Branch.
- If the requested quantity exceeds available stock, an error is displayed and the order cannot be submitted.



| Level | Description |
|---|---|
| Normal | Standard weekly or bi-weekly restocking cycle. |
| Urgent | Rapid depletion; stock projected to run out within 24 to 48 hours. |
| Critical | Zero stock or immediate risk of halting operations. High-visibility indicator shown on the HQ dashboard. |

### 14.3 Logistics and Delivery Fee Calculation

Inter-branch shipments use an automated geographic distance calculation engine. The system calculates the straight-line distance in kilometers between the Main Branch and the requesting satellite branch using their stored GPS coordinates. The delivery fee is then computed as follows:

- Base Fee + (Distance in km x Rate per km)
- The result is clamped between a configured minimum and maximum fee.
- If the order subtotal exceeds a configured free-delivery threshold, the fee is waived.
- Super Admins can manually adjust or waive the suggested logistics fee during fulfillment review.

Default parameters: Base Fee 0.00, Rate 50.00 per km, Minimum Fee 0.00, Maximum Fee Cap 5,000.00, Free Delivery Threshold 10,000.00 or above.

### 14.4 The 5-Stage Order Fulfillment Lifecycle

 The satellite branch submits the order. The branch can cancel at any time prior to approval. Only one pending request is permitted per branch at a time.

 The Super Admin reviews the request, adjusts approved quantities if needed, confirms the logistics fee, and clicks Approve Request.

 Commissary staff pull items from the warehouse and prepare the shipment.

 The Super Admin clicks Dispatch Order. The system creates an official Stock Transfer document, deducts stock from Main Branch batches using FEFO, and clones those batches into the receiving branch's inventory ledger (preserving original expiry dates and unit costs). Paired audit logs are generated for both branches.

 The shipment arrives at the satellite store. The branch manager inspects the physical delivery, clicks , and the order is archived to Transfer History.

Orders may also end in Rejected or Cancelled status.

### 14.5 Submitting a Stock Request — Step by Step

1. Navigate to Stock Ordering.
2. Switch to the New Stock Request tab.
3. Review low-stock alerts and click  for deficit items.
4. To add custom items: select an ingredient, choose a packaging unit, enter the requested quantity, and click .
5. Select a Priority Level.
6. Enter optional notes for the HQ team.
7. Review the estimated delivery fee and order total.
8. Click  and confirm.

### 14.6 Approving and Dispatching an Order — Step by Step (HQ Admin)

1. Navigate to Stock Orders Admin.
2. In the Inbox panel, locate the pending request and click .
3. Verify stock availability at the Main Branch.
4. Adjust approved quantities if needed.
5. Review or adjust the calculated delivery fee.
6. Enter optional admin remarks.
7. Click .
8. Once items are packed, click .
9. When the shipment departs, click .

### 14.7 Confirming Delivery Receipt — Step by Step

1. In Stock Ordering, locate the order under Active Requests (status: In Transit).
2. Inspect the physical items received against the order summary.
3. Click . The shipment is archived to Transfer History and branch balances are permanently finalized.

---

## 15. Branch Management

### 15.1 Overview

The Branch Management module governs the multi-store operational network. It allows centralized provisioning of physical stores, geographic routing, manager assignments, and network performance auditing.

-  Super Admin only.

### 15.2 Key Capabilities

 When entering a new branch name, the system automatically generates a unique, standardized branch code prefixed with MTC- (for example, "SM City Novaliches" becomes "MTC-SMCN"). This can be accepted or customized.

 Branch addresses are structured using the Philippine Standard Geographic Code: Region, Province, City or Municipality, Barangay, and Street. An interactive map allows administrators to drag a pin or click a location to capture exact GPS coordinates.

 Once GPS coordinates are saved, the system calculates the geodesic distance from the Main Branch. This distance automatically determines the logistics delivery fee for stock replenishment requests.

 A branch cannot be set to Active status without an assigned Branch Manager. Each manager can only be assigned to one branch at a time. Assigning a manager to a new branch automatically decouples them from their previous store.

### 15.3 Adding and Editing a Branch

1. Navigate to Branch Management.
2. Click  or select an existing branch to edit.
3. Fill in the required details.

| Field | Description |
|---|---|
| Branch Name and Identifier | The public-facing branch name. The system auto-generates a branch code. |
| Contact Information | Branch phone number and email address. |
| PSGC Address | Full Philippine Standard Geographic Code address: Region, Province, City, Barangay, and Street. |
| Geo-Coordinates | Latitude and longitude, captured via the interactive map pin. |
| Operating Hours | When the branch is officially open for system operations. |
| Branch Manager | Select an active, unassigned user to serve as the accountable manager. |
| Status | Active or Inactive. |

4. Click  and confirm.

### 15.4 Managing Branch Active Status

-  The branch is fully operational. Its POS is live and it appears in the customer-facing delivery application.
-  All POS operations for that branch are suspended immediately. The branch is removed from the delivery app. All historical data is preserved.

### 15.5 Decommissioning a Branch

Decommissioning a store is protected by strict safety locks:

-  If the branch has registered staff members, deletion is blocked. The Super Admin must transfer staff to another branch in User Management first.
-  If a manager is assigned, the system prompts a confirmation dialog. Confirming safely sets the manager's branch assignment to null and decommissions the branch within a database transaction.

### 15.6 Multi-Branch Insights

Switching to the Insights panel allows Super Admins to select multiple branches and run comparative audits across custom date ranges, including comparative revenue, order volume, average ticket value, and active personnel per location.

---

## 16. User and Staff Management

### 16.1 Overview

The User and Staff Management module provides role-based identity management, access control, and staff accountability across all store locations.

-  Can create, edit, deactivate, and delete any account across all branches.
-  Strictly scoped to their own branch. Can only provision Staff (Role 3) for their assigned store.
-  View-only access to their own profile. Cannot create users.

Navigate to User Management. The main directory displays a filterable, searchable list of all staff accounts:

- Filter by Role: Admin, Cashier, Rider.
- Filter by Branch (Super Admin only).
- Filter by Status: Active or Inactive.
- Toggle View: Table view or Card view.
- Search by name, email, phone number, or Employee ID.

### 16.2 Standardized Staff Positions

| Position | Authorization |
|---|---|
| Cashier | Authorized for front-of-house POS order entry, manual discounts, Cash/GCash payments, and receipt printing. |
| Delivery Rider | Authorized for mobile order acceptance, GIS customer navigation, in-transit updates, and digital Proof of Delivery photo capture. |

### 16.3 Manager Conflict Resolution

When promoting a user to Branch Administrator and assigning them to a branch that already has an active manager, the system halts and displays the Manager Conflict Modal: "Branch [Name] is currently managed by [Current Manager]. Do you want to replace them?" Confirming the replacement safely unassigns the prior manager.

### 16.4 Adding a New User

1. Click .
2. Complete the user form.

| Field | Description |
|---|---|
| First Name, Middle Name, Last Name | Employee's full legal name (auto-capitalized). |
| Email Address | Used as the login credential. Must be unique system-wide. |
| Phone Number | 10-digit local mobile number (stored with the +63 prefix). |
| Password | At least 8 characters with one uppercase, one lowercase, and one number. |
| Role | Admin, Cashier, or Rider. |
| Position | Cashier or Delivery Rider. |
| Employee ID | Optional internal identification code. |
| Date Hired | Official employment start date. |
| Branch | Assigned branch. Super Admins can assign any branch; Admins are locked to their own branch. |
| Address | Full PSGC address and optional GPS coordinates for rider dispatch mapping. |

3. Click .

### 16.5 Editing a User Profile

1. Select any user from the directory and click .
2. All fields are editable, including role, branch assignment, and status.
3. To reset a user's password, enter a new password in the password fields. Leave both blank to keep the current password unchanged.
4. Click  to save.

### 16.6 Viewing Staff Performance History

Clicking  on any user opens their operational performance dashboard with three tabs:


- Total orders processed (as cashier or rider)
- Total sales facilitated (monetary value)
- Average order value
- Order completion rate (%)
- Recent activity (orders in the last 7 days)


- Paginated, filterable log of every order the staff member processed or delivered.
- Quick-filter presets: Today, Last 7 Days, Last 30 Days, All Time.
- Click any order row to open the full Order Detail without navigating away.


- Full profile, role, branch assignment, and address on record.

### 16.7 Deactivating a User Account

1. Locate the user in the directory.
2. Toggle their Active/Inactive status via the status control.
3. Deactivation takes effect immediately. All active sessions are invalidated and the user can no longer log in.
4. All historical order data tied to that account is preserved for auditing. A deactivation log entry is created with a timestamp.

Reactivation is done by toggling the status back to Active at any time.

### 16.8 Deleting a User Account

-  Super Admin only.
- Permanently deletes the user record, available only from the user's profile Danger Zone and requires confirmation.
- Historical orders referencing the deleted user are preserved. The user field will display as "(Deleted User)".

### 16.9 Avatar Customization

Users can personalize their profile avatar using the built-in avatar picker modal. Two collections are available: Foods and Animals. Each avatar features custom gradient backgrounds. Selecting or changing an avatar immediately updates the navigation bar without reloading the page. Clicking  reverts to a default initial-based avatar.

---

## 17. Customer Feedback and Reviews

### 17.1 Customer Review Form (Public-Facing)

The review form is publicly accessible with no login required. Customers access it via:

- A QR code printed on their thermal receipt (generated automatically by the system and encoded with the branch-specific review URL).
- A direct link shared via SMS or digital channels.
- The direct URL pattern: `/review/{branch}`.

The form allows customers to submit:

- Answers to configurable rating questions (1 to 5 stars per question, for example, "How would you rate our food quality?")
- Answers to open-text questions (for example, "Any suggestions for improvement?")
- Customer name and contact number (optional, for follow-up)
- Reference order number (optional, links the feedback to a specific POS transaction)

The review form title, subtitle, and all question text are fully configurable by Super Admins via System Settings (Reviews tab).

### 17.2 Review Management Portal

-  Super Admin (all branches), Admin (their own branch only).

1. Navigate to Customer Reviews.
2. The Stats Bar at the top shows: Total Reviews, Average Rating (computed across all rating-type questions), Branch Count, and Latest Review timestamp.
3. Use filters to narrow results: Branch (Super Admin only), Date Range, and Search (by customer name, contact number, or branch name).
4. The review list is paginated. Click any review row to open the Review Detail drawer, which shows the customer name, contact number, submission timestamp, branch, and each question with the customer's answer and star rating.
5. Use this data to identify underperforming areas, recognize top-rated branches, or address specific product or service feedback.

---

## 18. Business Reports and Intelligence

### 18.1 Overview

The Business Intelligence module is the primary reporting and analytics hub. It is a Consolidated Financial Ledger where every figure is derived directly from the immutable transaction record.

-  Super Admin (all branches and global network), Admin (own branch only). Cashiers are blocked from this module entirely.
- Navigate to Business Reports. Use the Branch Selector (Super Admin only) and the Date Filter to scope all data across all tabs simultaneously.
-  Click the Export button and choose PDF (formatted report for printing or sharing), CSV (raw data for spreadsheet software), or Excel (equivalent to CSV for spreadsheet use).

### 18.2 Tab 1 — Performance (Financial Summary)

The default tab displays core financial KPIs for the selected period.



| Formula | Calculation |
|---|---|
| Gross Revenue | Net Sales plus Total Discounts. |
| Net Sales | Total Cash and GCash Collected minus Delivery Fees minus Total Refunds. |
| Gross Profit | Net Sales minus Total COGS minus Waste Cost (Spoilage). |

 Total COGS is calculated for every product sold using a 3-tier fallback:
1. Branch Standard Cost (fixed unit cost in the branch's active inventory profile).
2. Latest Purchase Price (most recent procurement cost recorded in stock intake).
3. Global Master Cost (fallback master unit cost from the global ingredient catalog).

| Metric | Description |
|---|---|
| Gross Revenue | Total revenue before deductions. |
| Net Sales | Revenue after excluding delivery fees. |
| Order Count | Number of completed transactions. |
| Average Order Value | Net Sales divided by Order Count. |
| Total Discounts | Sum of all regular and Senior/PWD discounts applied. |
| Delivery Fees Collected | Total delivery charges added to orders. |
| Refunds Issued | Total monetary value of all refunded orders. |
| Ingredient Costs (COGS) | Auto-calculated from recipes using the 3-tier cost lookup. |
| Waste Costs | Financial value of all ingredients logged as Wastage or Spoilage. |
| Gross Profit | Net Sales minus COGS minus Waste Costs. |

Click any metric name to open a Breakdown Panel showing the formula and a line-by-line contribution breakdown. The Revenue Trend Chart plots Gross Sales vs. Net Sales over the period, or by hour if a single day is selected. Payment Method Breakdown and Order Source Breakdown are also available.

### 18.3 Tab 2 — Forecasting

Uses a Damped-Trend Weighted Linear Regression statistical model to predict future demand.



-  Removes abnormal revenue spikes or promotional anomalies outside the 1.5 x IQR fences before training.
-  More recent sales days are weighted exponentially higher than older days.
-  Analyzes cyclical weekend surges and weekday slowdowns, computing specific multipliers for each day of the week.
-  Trend slope contribution decreases exponentially per future step to prevent unrealistic extrapolation (short-term damping factor: 0.98; long-term: 0.85).
-  Forecast predictions are floored at a minimum of 30% of the recent mean to prevent zeroed predictions during brief dips.

| Forecast | Training Window | Prediction Horizon |
|---|---|---|
| Short-Term (Daily) | Last 60 days of daily sales | Next 7 days |
| Long-Term (Monthly) | Last 12 months of monthly sales | Next 6 months |

Displayed data per forecast includes: predicted revenue, trend direction (Growing, Stable, or Declining), growth rate (%), and confidence level based on the number of clean data points.

 The system continuously evaluates forecasting reliability by training on all history except the last 7 days, then comparing predictions against actual recorded sales. Metrics tracked include MAE (Mean Absolute Error), RMSE (Root Mean Square Error), and MAPE (Mean Absolute Percentage Error).

 Combines the 7-day sales forecast with product recipes to calculate how much of each ingredient will be consumed. Generates a ranked "Must Stock" list sorted by urgency. Clicking an item navigates directly to Branch Requests (satellite branches) or Stock Adjustment (main commissary).

>  The system requires at least 5 clean daily data points (or 3 monthly points) to produce a reliable trend line. Below that threshold, it forecasts from the recent rolling average and displays "Insufficient Data" for Trend and Confidence.

### 18.4 Tab 3 — Products

Provides a detailed performance breakdown of every product sold in the selected period.

-  Ranked by units sold, with total revenue, average price, and contribution percentage of total net sales.
-  Revenue and order count grouped by product category.
-  Shows the top 3 best-selling products plotted across the days of the week or months of the year. Toggle between Weekly Mode (Monday to Sunday pattern) and Monthly Mode (January to December pattern) to identify peak selling periods.

### 18.5 Tab 4 — Operations

Focuses on operational efficiency and multi-branch performance.

-  A 24-hour breakdown showing order count and revenue by hour (00:00 to 23:00), identifying peak and off-peak periods.
-  Ranks every branch by revenue and order count, showing each branch's percentage share of total network sales. Paginated and searchable.
-  Compares Walk-in POS orders against delivery app orders.
-  Proportions of Cash vs. GCash transactions.

### 18.6 Tab 5 — Sales (Order Ledger)

A raw, paginated Consolidated Ledger of all individual orders within the selected scope.

- Search by Reference Number.
- Each row shows: reference number, branch, order type, payment method, status, cashier, and total amount.
- Click any order row to open the full Order Detail with a complete itemized breakdown.

---

## 19. System Settings and Platform Configuration

### 19.1 Overview

The System Settings module is the administrative control center for the business.

-  Super Admin only.
-  Admin.
-  Access is completely prohibited.

Navigate to System Settings and use the tab navigation to access each configuration area.

### 19.2 Tab 1 — General (Business Identity)

-  Super Admin only.

| Setting | Description |
|---|---|
| Business Name | The official trading name. |
| Business Email | Primary contact email. |
| Business Phone | 10-digit mobile contact number. |
| Business TIN | Tax Identification Number (format: 000-000-000-000). |
| Business Address | Full PSGC address with geo-coordinates. |
| Business Logo | Upload a logo (JPG or PNG, maximum 1 MB). Displayed on receipts and the application header. |

### 19.3 Tab 2 — Receipts

-  Super Admin only.

| Setting | Description |
|---|---|
| Station Slips | Configurable title and subtitle for the Kitchen Slip and Barista Slip. |
| Show Logo on Receipt | Toggle to include or exclude the business logo on printed receipts. |
| Show VAT on Receipt | Toggle to include or exclude the VAT line on receipts. |
| Footer Message | Custom text printed at the bottom of every receipt. |
| Return Policy Text | Return or exchange policy printed on receipts. |
| Number of Copies | How many receipt copies to print per transaction (1 to 3). |
| Review QR Code | Toggle to include a customer feedback QR code on the receipt footer. A live preview is rendered on-screen. |

### 19.4 Tab 3 — Inventory (Thresholds)

-  Super Admin, Admin.

| Setting | Description |
|---|---|
| Low Stock Threshold | Stock level at which an ingredient triggers an amber warning and a low-stock notification. |
| Critical Stock Threshold | Must be lower than the Low Stock Threshold. At this level, the ingredient triggers a red badge. The POS will block sales of products that depend on this ingredient when it reaches zero. |
| Expiry Alert Days | Number of days before expiry that triggers an expiring-soon notification (1 to 365 days). |
| Auto-Notifications | When enabled, automatically flags low-stock items and sends morning email alerts to branch managers. |

### 19.5 Tab 4 — POS Configuration

-  Super Admin, Admin.

| Setting | Description |
|---|---|
| Service Charge Rate | Applied as a percentage to Dine-in orders (for example, 0.10 for 10%). |
| Regular Discount Rate | Applied when a cashier selects "Regular Discount" on a cart item. |
| Senior/PWD Discount Rate | Applied when a cashier selects "Senior/PWD Discount" on a cart item. |
| POS Terminal Name | The business name displayed on the POS terminal header. |
| Enabled Order Types | Toggle which order types appear in the POS Order Method dropdown: Dine-in, Take-out, Delivery, Pick-up. |
| Enabled Payment Methods | Toggle which payment methods appear in the POS: Cash, GCash. |
| GCash Account Name | The merchant account name displayed on the Static QR payment screen. |
| GCash Account Number | The 10-digit GCash mobile number. |
| GCash Static QR Image | Upload the store's static GCash QR code image for the Static QR payment flow. |

### 19.6 Tab 5 — Reviews (Customer Feedback Configuration)

-  Super Admin, Admin.

| Setting | Description |
|---|---|
| Form Title | The headline displayed at the top of the customer review form. |
| Form Subtitle | Subheading text displayed below the title. |
| Review Questions | A fully configurable list of questions. Each question has a Type (Rating: 1 to 5 stars, or Text: open-ended) and a Required toggle. Add or remove questions freely. |

Click  to append a new question. Click the delete icon on any existing question to remove it. A live mobile screen simulation displays the exact layout customers will see.

### 19.7 Tab 6 — System (Super Admin Operating Context)

-  Super Admin only.

| Setting | Description |
|---|---|
| Operating Branch | Assigns the Super Admin to a specific branch context, making operational modules (POS, KDS, Inventory) show data for that branch. Selecting "General Headquarters (No Branch)" enables the next setting. |
| Hide Operational Modules | When toggled on (or when no branch is selected), hides branch-operational modules (POS, KDS) from the Super Admin's sidebar, providing a cleaner analytics-only view. Each Super Admin's preference is saved independently. |

### 19.8 Tab 7 — Thermal Printer

-  Super Admin, Admin.

| Setting | Description |
|---|---|
| Printer Activation | Master toggle to enable or disable automatic printing upon transaction completion. |
| Connection Type | Select Bluetooth (pairs via Web Bluetooth directly from the browser) or Wired/USB (scans and lists local Windows printers and serial ports). |
| Auto-Cut Paper | Sends an ESC/POS cut command at the end of each print job. |
| Test Connection | Prints a formatted test voucher to verify communication, alignment, and paper feed. |

### 19.9 Tab 8 — Logs (Audit Trail)

-  Super Admin only.

A paginated, combined activity log sourced from three real event streams.

| Log Type | What It Records |
|---|---|
| Order Logs | Every order event: order status, reference number, and the staff member who processed it. |
| Stock Logs | Every stock movement: ingredient name, quantity change, movement type, and the user responsible. |
| User Logs | Account creation events: staff name and timestamp. |

All log types are merged and sorted by timestamp (most recent first). Each entry shows the event type with a color-coded status indicator.

---

## 20. Notifications and Alert Management

### 20.1 Overview

Notifications provide real-time operational awareness across store operations, kitchen inventory, customer orders, and supply chain transfers.

-  Super Admin, Admin, Cashier.

The system delivers notifications through two interfaces:

 Checks for updates every 5 seconds. Displays an unread badge count. Clicking the bell opens a flyout menu with the five most recent notifications and relative timestamps. Allows users to mark individual items or all alerts as read.

 A full-page management screen for reviewing historical alerts with search, type-based filtering, and paginated records.

### 20.2 Branch and User Scope Isolation

All notifications enforce dual scoping. Staff members only receive alerts relevant to their account or broadcast to all staff. Branch Admins, Cashiers, and Kitchen staff only receive alerts for their active branch. Super Admins receive alerts across the entire network (or filtered to a specific branch when one is selected).

### 20.3 Notification Types

| Type | Trigger |
|---|---|
| Stock Alert | An ingredient drops below the Low Stock or Critical threshold. |
| Expiry Alert | A stock batch is within the configured expiry alert window, or has already expired. |
| Stock Transfer | A branch submits a supply request (Super Admin receives it), or a request status changes (branch Admin is notified). |
| Order Alert | A new delivery app order arrives requiring action. |
| Customer Feedback | A customer submits a review through the digital receipt QR survey. |

### 20.4 Notification Archive — Advanced Management

Navigate to the full Notification History for:

-  Total notifications, Unread count, Critical unread alerts, and Recent activity (last 7 days).
-  All, Stock, Expiry, Order, or Review.
-  By notification title or message content.
-  Click a notification to mark it individually, or click  to clear all unread indicators.
-  Click the notification's action link to navigate directly to the relevant module (for example, an expiry alert navigates to the specific ingredient batch in Stock Management).
-  Remove a single notification permanently.
-  Permanently deletes all notifications for the current user and branch. A confirmation prompt is shown.

### 20.5 Intelligent Deep-Link Tracing

Clicking any notification automatically marks it as read and routes the user to the correct location in the system:

- Stock transfer notifications route to the correct tab based on the order's current status (Pending, In Transit, or Delivered).
- Expiry alert notifications redirect to Stock Management, switch to the Expiry Tracking panel, and pre-filter to the specific ingredient batch.

### 20.6 Automated Data Retention

The system includes an automated cleanup command (`php artisan notifications:cleanup`) that deletes notification logs older than 60 days. Active inventory records, sales ledgers, and order audit trails are not affected.

---

## 21. Profile Settings

### 21.1 Overview

Profile Settings allows every logged-in user to manage their own personal account details.

-  Super Admin, Admin, Cashier, Rider.
- Click the profile avatar in the top-right corner of the navigation bar and select .

The Profile Settings page has two tabs: Personal Information, and Security and Password.

### 21.2 Profile Information

Update your personal details:

| Field | Description |
|---|---|
| First Name | Required. Auto-capitalized as you type. |
| Middle Name | Optional. Auto-capitalized. |
| Last Name | Required. Auto-capitalized as you type. |
| Email Address | Your login email. Must be unique system-wide. Changing this resets the account verification timestamp. |
| Phone Number | 10-digit mobile number (stored with the +63 prefix). |

All fields are validated in real time. Click  to save changes.

### 21.3 Avatar Selection

Personalize your account with a themed avatar displayed in the navigation bar and across the system.

- Choose from two collections: Foods (for example, Takoyaki, Ramen, Sushi, Bento) and Animals (for example, Fox, Panda, Cat, Penguin).
- Each avatar has a unique gradient color style applied to its background.
- Click any avatar to instantly save and apply it. The navigation bar updates immediately without a page reload.
- Click  to revert to the default initial-based avatar.

### 21.4 Change Password

To update your account password:

1. Enter your current password (required to verify your identity).
2. Enter a new password. Requirements:
   - At least 8 characters, maximum 128.
   - Must contain at least one uppercase letter, one lowercase letter, and one number.
3. Confirm the new password.
4. Click . On success, the password fields are cleared automatically.

An interactive password strength meter evaluates complexity in real time, categorizing strength as Weak, Fair, or Strong. A show/hide toggle allows you to unmask the password fields before submitting.

---

## Appendix A — Mobile Ecosystem

### A.1 Customer Mobile Application

The system provides a REST API layer that supports a customer-facing mobile application for delivery ordering, operating as a parallel channel alongside the in-store POS.



- Registration and Login with name, email, and mobile number.
- OTP Verification for account creation and password recovery (same mechanism as the staff Forgot Password flow).
- Address Book for saving, managing, and setting a default delivery address.



1. The customer selects a branch from the list of active branches.
2. They browse the branch-specific product catalog.
3. They build a cart, select options, and place a delivery order.
4. The order appears immediately in the Order Management screen (Delivery Orders tab) for the cashier or manager to Accept or Reject.

### A.2 Rider Mobile Application

The system's Rider API supports a dedicated mobile application for delivery staff.



1. When a KDS operator assigns a rider to a ready delivery order, the assignment is pushed to the rider's app in real time.
2. The rider views the order details (customer name, address, items) and an integrated map route to the delivery address.
3. The rider app can generate a geographic directions route to the customer's GPS coordinates.
4. Upon delivery, the rider uploads a Proof of Delivery photo through the app.
5. The uploaded photo appears immediately in the Order Detail panel of Order Management under the Proof of Delivery section, along with a map location pin.
6. The order is automatically marked as Delivered, completing the order lifecycle.

---

## Appendix B — Troubleshooting

| Problem | Possible Cause | Solution |
|---|---|---|
| Cannot log in | Incorrect credentials or deactivated account | Verify email and password. Use Forgot Password (OTP flow). If still failing, contact your Admin — the account may have been deactivated. |
| OTP code not received | Email delivery delay | Wait 1 minute, then use the Resend Code button. Check your Spam or Junk folder. |
| GCash QR not displaying at checkout | GCash account details or QR image not uploaded | Ensure the GCash merchant account name, mobile number, and static QR image are uploaded in System Settings under the POS Platform tab. |
| Product is unavailable or grayed out on POS | Insufficient ingredient stock | The product's recipe ingredients are at zero. Perform a Stock Adjustment or submit a Branch Request. |
| Cannot assign rider in KDS | No active Rider accounts at the branch | Create a Rider user account in User Management and assign them to the correct branch. |
| Receipt not printing | Browser print permissions or printer not configured | Ensure the thermal printer is set as the default printer in the browser's print settings, or pair the Bluetooth printer via System Settings. |
| Importing an Options Library template does nothing | A group with the same name already exists on the product | Rename or remove the existing group from the product's Options tab first, then retry the import. |
| Category cannot be deleted | Products or ingredients still assigned to it | Re-categorize or remove all assigned items first. The system shows the exact count blocking deletion. |
| Gross Profit shows zero | Ingredients have no cost, or products have no recipe | Ensure every ingredient has a cost per unit set, and every product has a recipe attached. |
| Business Intelligence shows "Insufficient Data" | Not enough historical sales data | The forecasting model requires at least 5 daily data points. Accumulate more sales history. |
| Admin receives 403 on Category Management | Access is restricted to Super Admin | Category Management is Super Admin only. Admins should contact the Super Admin for category changes. |

---

## Appendix C — Frequently Asked Questions


No. Cashiers can view inventory alerts on the Dashboard but cannot perform Stock Adjustments, Waste Logging, or submit Supply Requests. These require Admin or Super Admin access.


No. Prices are global. An Admin can only toggle a product's Active or Hidden availability for their own branch. Pricing changes require Super Admin access.


The branch's POS is suspended immediately and it is removed from the delivery app. All existing order history and data are preserved. Reactivating the branch restores full operations.


A Refund returns a specified monetary amount to the customer while keeping the order record as Refunded or Partially Refunded. A Void nullifies the entire order — no revenue is recorded for it at all. Voids are appropriate for orders that should not have existed (for example, test orders or system errors). Both are excluded from revenue totals.


An Option Group lives on one product and only affects that product. An Option Template lives in the shared Options Library and can be imported into any number of products. Changes to a library template do not automatically update existing products — you must use the Sync function to pull in updates.


The system checks recipe ingredients in real time. If any Base ingredient linked to the product's recipe has zero stock, the product is automatically disabled and shown with a red indicator. Perform a Stock Adjustment or receive a supply delivery to re-enable it.


Yes. Go to System Settings, open the System tab, and set an Operating Branch. This scopes the Super Admin into that branch's context, allowing use of the POS, KDS, and other operational modules as if they were branch staff.

---

## Appendix D — Glossary

| Term | Definition |
|---|---|
| AOV | Average Order Value — total revenue divided by order count. |
| COGS | Cost of Goods Sold — the total ingredient cost consumed, calculated automatically from product recipes. |
| FEFO | First-Expiry-First-Out — the stock deduction method that always consumes the earliest-expiring batch first. |
| Financial Ledger | An immutable record of every Sale, Void, and Refund event. Cannot be edited or deleted, ensuring full audit integrity. |
| IQR | Interquartile Range — a statistical method used by the forecasting engine to remove outlier data points before running regression. |
| KDS | Kitchen Display System — the real-time screen used by kitchen staff to view, manage, and complete order tickets. |
| Modifier | A standalone add-on applied to a product (for example, "Extra Cheese") that may have its own ingredient deduction. |
| Option Group | A set of customization choices attached directly to one specific product (for example, a product's own "Size" options). |
| Option Template | A reusable option group stored in the Options Library that can be imported into and synced with multiple products. |
| OTP | One-Time Password — a cryptographically secure, single-use, time-limited numeric code used for password recovery. |
| POS | Point of Sale — the in-store transaction terminal used by cashiers. |
| PSGC | Philippine Standard Geographic Code — the official hierarchical address classification system (Region, Province, City, Barangay) used throughout the system. |
| RBAC | Role-Based Access Control — the permission model ensuring each user only sees and can operate their authorized modules. |
| Stock Batch | A specific delivery of an ingredient, tracked independently with its own quantity, unit cost, and expiry date. |
| Weighted Linear Regression | The statistical algorithm used by the Business Intelligence module — recent data points are assigned higher weight, producing more accurate near-term forecasts. |

---

## Document Control

| Field | Value |
|---|---|
| Version | 1.3 |
| Date | August 2026 |
| Status | Final |
| Prepared By | Development Team |
| Approved By | Management |
