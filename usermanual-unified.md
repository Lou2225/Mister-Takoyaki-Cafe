============================================================
  MISTER TAKOYAKI CENTRALIZED SALES AND MANAGEMENT SYSTEM
============================================================


Unified Role-Based User Manual  -  Version 1.5 (September 2026)
Unified Role-Based User Manual  -  Version 1.6 (September 2026)
===============================================================


| Field | Value |
|---|---|
| Document | Mister Takoyaki Centralized Sales and Management System  -  Role-Based User Manual |
| System | Web Application & Integrated Mobile Delivery Ecosystem |
| Version | 1.5 |
| Version | 1.6 |
| Status | Approved & Released |
| Prepared By | Development & Architecture Team |
| Approved By | Executive Management |


------------------------------------------------------------------------


Document Architecture & How to Use This Manual
==============================================


This User Manual is structured into three dedicated, role-specific operational parts, preceded by common authentication fundamentals and followed by technical appendices:

  - Common Fundamentals (Chapters 1 - 4): System overview, core architectural highlights, secure authentication (3-step OTP password recovery and Google OAuth API integration), operating branch context resolution, interactive branch required placeholders, and the central Role-Based Access Control (RBAC) permissions matrix.
  - Part I: Staff & Cashier Operations Manual (Chapters 5 - 11): Front-of-house shift operations, shift dashboard, complete Point of Sale (POS) checkout, bounded option selection limits (max_select), two-column item customization, Senior/PWD statutory discounts, GCash verified payment safeguard, Web Bluetooth thermal printing with multi-slip countdown, order fulfillment progression, Kitchen Display System (KDS) with station routing, view-only stock health monitoring, shift notifications, and personal profile settings.
  - Part II: Branch Administrator Manual (Chapters 12 - 21): Store management, branch-scoped operational dashboard, local menu availability toggles, drag-and-drop category & product sort persistence, local batch inventory tracking & FEFO, multi-tier packaging conversions (Box -> Bottle -> ml), staged adjustment queue with central commissary rules (Waste default on satellites), physical count reconciliation, requesting supplies from HQ (/stock/orders), branch staff oversight with PSGC address comboboxes and avatar customization, customer reviews with receipt QR order binding and device cooldown, branch business analytics, and branch-scoped operational settings.
  - Part III: Super Admin Enterprise Manual (Chapters 22 - 31): Global enterprise command, cross-branch telemetry, Leaflet interactive live map with 520px modal, master product catalog & 3-tier recipe costing, Options Library master templates with single/multi modes and selection caps, Category taxonomies (Product vs. Ingredient) with station routing, enterprise stock control & automated 6:00 AM email digest, HQ branch order dispatch (/stock/orders/admin), multi-branch network provisioning with PSGC and GPS geocoding, network-wide user administration, Prescriptive Analytics & Restock Recommendations Engine, and all 8 System Administration tabs with cascading branch overrides.
  - Common Fundamentals (Chapters 1 - 4): System overview, core architectural highlights, reactive state synchronization, adaptive collapsible navigation with dynamic viewport-aware tooltips, secure authentication (3-step OTP password recovery and Google OAuth API integration), operating branch context resolution, interactive branch required placeholders, and the central Role-Based Access Control (RBAC) permissions matrix.
  - Part I: Staff & Cashier Operations Manual (Chapters 5 - 11): Front-of-house shift operations, shift dashboard, complete Point of Sale (POS) checkout, bounded option selection limits (max_select), two-column item customization, Senior/PWD statutory discounts, GCash verified payment safeguard, Web Bluetooth thermal printing with multi-slip countdown, order fulfillment progression, mandatory order cancellation reasons, automated reverse FEFO stock restoration, Kitchen Display System (KDS) with station routing, view-only stock health monitoring, the 4-tier toast notification system with synthesized Web Audio chimes, centralized Notification Hub (/notifications) with smart routing, and personal profile settings.
  - Part II: Branch Administrator Manual (Chapters 12 - 21): Store management, branch-scoped operational dashboard, local menu availability toggles, drag-and-drop category & product sort persistence, local batch inventory tracking & FEFO, multi-tier packaging conversions (Box -> Bottle -> ml), staged adjustment queue with central commissary rules (Waste default on satellites), physical count reconciliation, requesting supplies from HQ (/stock/orders), automated stock notification dispatching, branch staff oversight with PSGC address comboboxes and avatar customization, customer reviews with receipt QR order binding and device cooldown, branch business analytics, and branch-scoped operational settings.
  - Part III: Super Admin Enterprise Manual (Chapters 22 - 31): Global enterprise command, cross-branch telemetry, Leaflet interactive live map with 520px modal, master product catalog & 3-tier recipe costing, Options Library master templates with single/multi modes and selection caps, Category taxonomies (Product vs. Ingredient) with station routing, enterprise stock control & automated 6:00 AM stock email & bell digest, HQ branch order dispatch (/stock/orders/admin), multi-branch network provisioning with PSGC and GPS geocoding, network-wide user administration, Prescriptive Analytics & Restock Recommendations Engine, and all 8 System Administration tabs with cascading branch overrides.
  - Shared Appendices (Appendices A - D): Customer & Rider mobile ecosystem (including Favorites and Directions API), role-specific troubleshooting matrix, frequently asked questions by role, system glossary, and document control.


------------------------------------------------------------------------


Role Quick-Reference Guide
==========================


| Your Role | Assigned Badge & Color | Primary Focus | Go Directly To |
|---|---|---|---|
| Cashier / Staff | CASHIER (Emerald) | Front-of-house checkout, cash/GCash transactions, kitchen queue, order packaging, customer handoff | Part I (Chapter 5) |
| Branch Admin | BRANCH ADMIN (Rose) | Store oversight, inventory adjustments, physical counts, supply ordering from HQ, branch staff, local reviews | Part II (Chapter 12) |
| Super Admin | SUPER ADMIN (Indigo) | Enterprise governance, global menu/recipes, multi-branch supply fulfillment, store creation, analytics, system settings | Part III (Chapter 22) |


------------------------------------------------------------------------


Table of Contents
=================


Common Fundamentals
-------------------

  - 1. Introduction
    - 1.1 Purpose
    - 1.2 Scope
    - 1.3 Intended Users
  - 2. System Overview
    - 2.1 Core Architectural Highlights
    - 2.2 Modernized Reactive State & Polling Architecture
    - 2.3 Adaptive Collapsible Navigation Sidebar & Viewport-Aware Tooltips
  - 3. Accessing the System
    - 3.1 Opening the System
    - 3.2 Logging In (Email/Password & Customer Google OAuth)
    - 3.3 Forgot Password  -  3-Step OTP Recovery & Rate Limiting
    - 3.4 Operating Branch Context & Interactive Branch Required Screen
  - 4. User Roles and Permissions
    - 4.1 Centralized Permissions Matrix
    - 4.2 Branch Scoping & Administrative Access Boundaries


Part I: Staff & Cashier Operations Manual (Front-of-House)
----------------------------------------------------------

  - 5. Cashier Shift Orientation & Welcome Banner
  - 6. Cashier Shift Dashboard & Live Terminal Operations
  - 7. Point of Sale (POS) Terminal & Complete Order Entry Guide
    - 7.1 Opening the POS Terminal & Stock Badges
    - 7.2 Adding Items to the Cart (Standard vs. Customizable with Bounded Selection Limits)
    - 7.3 Managing Cart Items: The Two-Column Edit Modal
    - 7.4 Fulfillment Method and Table References
    - 7.5 Holding and Restoring Draft Orders
    - 7.6 Processing Payments & GCash Lock Safeguard
    - 7.7 Thermal Receipt Printing & Multi-Slip Separation Alert
    - 7.8 Menu and Category Layout Customization (Sort Persistence)
  - 8. Shift Order Management & Lifecycle Progression
    - 8.1 Overview & Reactive Synchronization
    - 8.2 Tab Navigation & the 1-Hour Operational Transition Window
    - 8.3 Tab-Scoped Order Health Overview
    - 8.4 Search and Filtering Controls
    - 8.5 Managing Delivery App Orders (Accepting, 1-Hour Auto-Rejection, Rider Handoff, Full-Screen POD Photo)
    - 8.6 Managing POS Draft Orders
    - 8.7 Processing Refunds
    - 8.8 Voiding Orders (Automated FEFO Stock Reversal)
    - 8.8 Order Cancellation & Voiding Operations (Mandatory Reasons & Reverse FEFO Stock Reversal)
    - 8.9 Order Detail Panel (Slide-Out Inspector)
    - 8.10 Viewing and Printing Receipts
  - 9. Kitchen Display System (KDS) for Line Cooks & Baristas
    - 9.1 Overview & Production Station Routing (Kitchen vs. Barista)
    - 9.2 Kitchen Health Metrics Bar
    - 9.3 Elapsed-Time Timers and Visual Urgency Alerts (Pulsing Red)
    - 9.4 Processing Queue (Tab 1  -  FIFO Progression)
    - 9.5 Ready Board (Tab 2  -  Mark Served & Rider Handoff)
    - 9.6 Assigning a Rider from the Ready Board
    - 9.7 Historical Record (Tab 3  -  Archived Tickets)
  - 10. Stock & Ingredient Health Monitoring (View-Only)
  - 11. Shift Notifications & Personal Profile
    - 11.1 Topbar Notifications (Optimized Background Polling)
    - 11.2 Audio Chimes & Alert Settings
    - 11.1 Topbar Notifications & Central Notification Center (/notifications)
    - 11.2 Ephemeral Toast Notifications & Synthesized Web Audio Chimes (4-Tier Classification)
    - 11.3 Profile Avatar Customization (Foods & Animals with Gradients)
    - 11.4 Password Updates & Strength Validation


Part II: Branch Administrator Manual (Store Operations)
-------------------------------------------------------

  - 12. Branch Manager Orientation & Daily Responsibilities
  - 13. Branch Operational Dashboard & Local Performance Telemetry
    - 13.1 Overview & Scoped Store Telemetry
    - 13.2 RBAC Welcome Banner
    - 13.3 Scoped Control Panel (Locked Branch Indicator & 3-in-1 Date Filter)
    - 13.4 Financial Intelligence KPI Cards & ApexCharts Sparklines
    - 13.5 Intelligence Report Slide-Over Panel & Profit Waterfall
    - 13.6 Branch Operations Index (Staff Ratios & Local Deficits)
    - 13.7 Spend & Revenue Activity Chart (Hourly & Daily Resolutions)
    - 13.8 Secondary Analytical Grid (Fulfillment Split, GCash Split, Usage Velocity, Efficiency Battery)
    - 13.9 Operational Workstation Grid (Top Products, Sliding Shortage/Expiry Tabs, Live Order Feed)
  - 14. Local Menu Availability & Category Sorting Customization
    - 14.1 Toggling Product Availability per Branch (Active vs. Hidden)
    - 14.2 Drag-and-Drop Category and Product Sort Order Persistence
    - 14.3 Options Library (Branch Perspective: Viewing Master Templates)
  - 15. Local Stock & Batch Inventory Management
    - 15.1 Overview & Branch Ledger
    - 15.2 Inventory KPI Dashboard
    - 15.3 Ingredient Configuration and Bulk Conversions (Box -> Bottle -> ml)
    - 15.4 Inventory List and Batch Detail View
    - 15.5 Expiry Tracking & Batch Disposal Workflow
    - 15.6 Automated Daily 6:00 AM Stock Email Alerts
    - 15.6 Automated Daily 6:00 AM Stock Email & Bell Alerts
  - 16. Stock Adjustments, Spoilage & Physical Count Reconciliation
    - 16.1 Overview & Central Commissary Rules (Waste Default for Satellites)
    - 16.2 Audit Ledger & Period KPI Cards
    - 16.3 Staged Adjustment Queue Engine (Atomic Commits & Auto Cost Pre-Fill)
    - 16.4 Physical Count Reconciliation Sheet & Live Variance Calculation
  - 17. Branch Stock Ordering (Requesting Supplies from HQ)
    - 17.1 Satellite Branch Console (/stock/orders) & Exclusive Supplier Architecture
    - 17.2 Low-Stock Deficit Assistant & Real-Time Commissary Stock Verification
    - 17.3 Request Priority Levels (Normal, Urgent, Critical)
    - 17.4 Geodesic Logistics Fee Calculation Engine & Clamping Limits
    - 17.5 The 5-Stage Order Fulfillment Lifecycle
    - 17.6 Submitting a Stock Request  -  Step by Step
    - 17.7 Receiving Deliveries & Automatic Inventory Ingestion
  - 18. Branch Staff Oversight (Cashiers & Delivery Riders)
    - 18.1 Overview & Directory Filtering
    - 18.2 Standardized Staff Roles & Positions
    - 18.3 Provisioning Branch Staff with Searchable PSGC Address Comboboxes
    - 18.4 Manager Conflict Resolution Modal
    - 18.5 Staff Performance Dossiers & Order Audit Logs
    - 18.6 Account Deactivation vs. Deletion Safeguards
    - 18.7 Built-In Avatar Customization Picker
  - 19. Store Customer Reviews & Feedback Management
    - 19.1 Public Customer Review Portal (/review/{branch})
    - 19.2 Direct Receipt QR Order Binding & Review Limit Safeguards
    - 19.3 Device Anti-Spam Tracking & Dedicated Cooldown Screen
    - 19.4 Review Management Console (/reviews) & Slide-Out Inspector
  - 20. Branch Business Reports & Sales Analytics
    - 20.1 Overview & Immutable Financial Ledger
    - 20.2 Tab 1  -  Performance (Financial Summary & 3-Tier COGS Fallback)
    - 20.3 Tab 2  -  Forecasting & Prescriptive Restock Intelligence
    - 20.4 Tab 3  -  Products (Volume & Revenue Rankings)
    - 20.5 Tab 4  -  Operations (24-Hour Heatmap & Channel Split)
    - 20.6 Tab 5  -  Sales (Consolidated Order Ledger)
    - 20.7 Executive Report Generation (PDF Streaming, CSV, Excel)
  - 21. Branch Operational Settings
    - 21.1 Receipt Customization (Header, Phone, Promo Footer)
    - 21.2 Inventory Alert Thresholds
    - 21.3 POS Configuration & Branch Static GCash QR Code
    - 21.4 Thermal Printer Web Bluetooth Hardware Setup


Part III: Super Admin Enterprise Manual (Global Governance)
-----------------------------------------------------------

  - 22. Super Admin Enterprise Orientation & Global Scope
  - 23. Enterprise Command Dashboard & Network Telemetry
    - 23.1 Global vs. Branch View Selector (All Locations)
    - 23.2 Integrated 3-in-1 Date Filter with 9 Presets & Validation
    - 23.3 Financial Intelligence KPI Cards & Pulsing Gross Profit Bar
    - 23.4 Intelligence Report Slide-Over Drawer & Network Profit Waterfall
    - 23.5 Leaflet Interactive Branch Live Map & 520px Expanded Modal
    - 23.6 Multi-Branch Comparative Insights & Executive PDF Report Streaming
  - 24. Master Product Catalog & Recipe Engineering
    - 24.1 Catalog Navigation & Display Modes
    - 24.2 The 4-Tab Product Workbench (Information, Options, Recipe, Profitability)
    - 24.3 Single/Multi Option Selection Modes & Bounded Selection Caps (max_select)
    - 24.4 3-Tier Recipe Costing & Dynamic Margin Calculation
    - 24.5 Creating, Editing & Deleting Products Step by Step
    - 24.6 Per-Branch Availability Governance
    - 24.7 Quick-Adding a Product Category Inline
  - 25. Options Library Management
    - 25.1 Reusable Master Option Templates
    - 25.2 Template Architecture (Single/Multi Modes, max_select, Additive vs. Fixed)
    - 25.3 Instant 0ms Client-Side Option Operations
    - 25.4 Recipe Ingredient Mapping to Option Choices
    - 25.5 Importing, Bi-Directional Syncing & Saving Templates
  - 26. Category Taxonomies & Station Routing
    - 26.1 Dual Taxonomies: Product Categories vs. Ingredient Categories
    - 26.2 Alphanumeric Reference Identifiers (PRD-CAT- and ING-CAT-)
    - 26.3 Production Station Routing (Kitchen vs. Barista Slips)
    - 26.4 Safe Category Lifecycle & Integrity Deletion Guards
  - 27. Enterprise Stock Control & HQ Commissary Dispatch
    - 27.1 Global Ingredient Catalog Creation & Multi-Tier Conversions
    - 27.2 Enterprise Inventory Monitoring & Daily 6:00 AM Digest Concurrency Mutex
    - 27.3 Reviewing, Approving & Dispatching Branch Stock Requests (/stock/orders/admin)
    - 27.4 Central Procurement Ingestion (Stock In) at Main Branch
  - 28. Multi-Branch Network Administration
    - 28.1 Branch Onboarding with Automated Codes (MTC-) & PSGC Comboboxes
    - 28.2 Interactive Leaflet Geo-Pinning for GPS Coordinates
    - 28.3 Geodesic Distance Matrix & Delivery Fee Configurations
    - 28.4 Branch Manager Assignment & Conflict Decoupling
    - 28.5 Operational Status Toggles & Safe Decommissioning Safeguards
  - 29. Network-Wide User Management & Security Auditing
    - 29.1 Cross-Branch Staff Directory & Role Provisioning
    - 29.2 Searchable PSGC Address Hierarchy Comboboxes
    - 29.3 Password Resets, Account Suspension & Danger Zone Deletion
    - 29.4 Security Audit Trails & Failed Attempt Monitoring
  - 30. Prescriptive Analytics & Restock Recommendation Engine
    - 30.1 14-Day Weighted Linear Regression Forecast & IQR Outlier Cleansing
    - 30.2 Stockout-Suppressed Demand Multiplier (+30%)
    - 30.3 Waste-Scaled Safety Stock Buffers (5% to 15%)
    - 30.4 Urgency Classifications (Critical, High, Medium, Low) & Coverage Days
    - 30.5 Network Restock Summary (loadNetworkRestockSummary) & Prescriptive CSV Export
  - 31. System Administration & Platform Configuration (All 8 Tabs)
    - 31.1 Overview & Cascading Branch Override Hierarchy (branch_id)
    - 31.2 Tab 1  -  General (Business Identity, TIN, Logo, Headquarters PSGC)
    - 31.3 Tab 2  -  Receipts (Headers, Station Slips, Review QR Binding, Print Copies)
    - 31.4 Tab 3  -  Inventory (Thresholds, Expiry Alert Days, Automated 6:00 AM Digest)
    - 31.5 Tab 4  -  POS Configuration (Rates, Static GCash QR Upload, Merchant Details)
    - 31.6 Tab 5  -  Reviews (Questionnaire Builder, Mobile Simulator, QR Limits, Device Cooldown)
    - 31.7 Tab 6  -  System (Operating Branch Context Switcher & Module Hiding)
    - 31.8 Tab 7  -  Thermal Printer (Web Bluetooth Direct Pairing, Hardware Diagnostics)
    - 31.9 Tab 8  -  Consolidated Audit Logs (Orders, Stock, Users)


Shared Appendices
-----------------

  - Appendix A  -  Mobile Ecosystem (Customer App, Rider App & Parallel APIs)
  - Appendix B  -  Role-Specific Troubleshooting Matrix
  - Appendix C  -  Frequently Asked Questions by Role
  - Appendix D  -  System Glossary & Document Control


------------------------------------------------------------------------


============================================================
  COMMON FUNDAMENTALS
============================================================


1. Introduction
===============


1.1 Purpose
-----------

This User Manual provides complete, authoritative instructions for utilizing the Mister Takoyaki Centralized Sales and Management System. It is engineered to guide all operational personnel  -  from the business owner and enterprise administrators to store managers, cashiers, line cooks, and delivery dispatchers  -  in executing daily transactions, maintaining batch-level inventory, governing multi-store replenishment, supervising kitchen production stations, and leveraging predictive analytics.


1.2 Scope
---------

The centralized platform orchestrates end-to-end cafe and logistics operations:
  - Point of Sale (POS) Order Entry: High-speed checkout supporting Cash and Static QR GCash, line-item customizations, bounded selection limits (max_select), two-column modifier management, held drafts, and statutory VAT-exempt Senior/PWD discounts.
  - Kitchen Display System (KDS): Real-time queue and ready boards featuring production station routing (Kitchen vs. Barista slips), live elapsed-time stopwatches, and pulsing visual delay indicators.
  - Multi-Tier Inventory & Batch FEFO: Raw material tracking with packaging unit conversions (Box -> Bottle -> ml), supplier batch tracking, automated First-Expired, First-Out (FEFO) deduction guarantees, and batch disposal workflows.
  - Central Commissary & Inter-Branch Logistics: Supply chain replenishment workflow connecting satellite stores (/stock/orders) to the central commissary warehouse (/stock/orders/admin) with geodesic distance-based delivery fee calculations.
  - Staff Management & PSGC Geocoding: Role-based employee provisioning utilizing reactive Philippine Standard Geographic Code (PSGC) comboboxes and built-in avatar customization.
  - Customer Feedback Ecosystem: Public review portal (/review/{branch}) with receipt QR direct order binding, review limit enforcement, device anti-spam fingerprinting, and dedicated on-load cooldown screens.
  - Prescriptive & Predictive Business Intelligence: Multi-channel sales analysis, 24-hour trading heatmaps, and a 14-day weighted linear regression engine with stockout demand corrections (+30%) and waste-aware safety buffers.
  - Mobile Ecosystem Integration: Parallel REST APIs powering dedicated customer delivery mobile apps (with Google OAuth and favorite item management) and rider logistics apps (with digital Proof of Delivery photo capture and OSRM turn-by-turn routing).


1.3 Intended Users
------------------

| Role | System Badge | Operational Scope |
|---|---|---|
| Super Admin (Owner / Executive) | SUPER ADMIN (Indigo) | Unrestricted enterprise governance: global catalog, recipes, Options Library master templates, store onboarding, network supply dispatch, restock forecasting, and all 8 platform configuration tabs. |
| Administrator (Branch Manager) | BRANCH ADMIN (Rose) | Store-level operational command: local product availability toggles, POS display sort persistence, inventory adjustments, physical stock reconciliations, supply requests to HQ, local staff supervision, and customer review oversight. |
| Cashier (Front-of-House Staff) | CASHIER (Emerald) | Frontline order processing: POS order entry, payment verification, KDS status tracking, order packaging, customer handoff, and view-only stock monitoring. |
| Delivery Rider | RIDER | Mobile logistics application: ticket dispatch acceptance, GPS navigation, and digital Proof of Delivery (POD) camera verification. |


------------------------------------------------------------------------


2. System Overview
==================


2.1 Core Architectural Highlights
---------------------------------

The Mister Takoyaki system is built on Laravel and Livewire 3, engineered to unify multi-branch retail and delivery logistics under a single resilient architecture:
  - Ingredient-Level Stock Safety: The POS cannot oversell. Ingredient deductions are calculated automatically per order item, accounting for base recipe ingredients and selected option modifiers. If an ingredient balance is zero, dependent menu items are grayed out instantly.
  - Bounded Selection Limits (max_select): Customization groups support single-choice and multi-choice modes with configurable selection bounds (e.g., choosing up to 2 sauces or up to 3 toppings), enforced both client-side in Alpine and validated server-side.
  - Instant 0ms Client-Side Workbench: Options Library and Menu Customization utilize client-side Alpine data stores for instantaneous addition, reordering, and ingredient linking without incurring network round-trip latencies.
  - Central Commissary Exclusivity: Only the designated Main Branch is authorized to ingest supplier shipments (Stock In). Retail satellite stores must order supplies through the centralized branch transfer pipeline, enforcing complete supply chain transparency.
  - Immutable Financial Ledger: Every sale, void, refund, and stock movement writes a permanent, tamper-proof record to the database ledger, guaranteeing audit compliance.
  - Dual-Window Predictive Analytics: Combines 60-day short-term weighted linear regression with 12-month long-term trend modeling, incorporating IQR outlier removal, day-of-week seasonality, and stockout-suppressed demand multipliers.


2.2 Modernized Reactive State & Polling Architecture
----------------------------------------------------

To ensure high responsiveness across branch hardware while protecting server infrastructure against gateway timeouts:
  - Kitchen Display System (KDS): Employs a relaxed 15-second background polling cycle (`wire:poll.15s="refreshStats"`) paired with a client-side Alpine interval (`setInterval(_tick, 1000)`) to drive fluid ticket stopwatches without network strain.
  - Stock Ordering & Review Management: Operates on a balanced 30-second background polling frequency (`wire:poll.30s`).
  - Topbar Notifications: Uses an efficient 30-second heartbeat (`wire:poll.30s.keep-alive`) to alert staff of incoming orders and supply updates.
  - Topbar Shift Notifications: Uses an efficient 15-second heartbeat (`#[Poll(15000)]`) coupled with the centralized `NotificationService` to deliver incoming online orders, stock order status updates, and low-inventory alerts directly to active personnel.
  - Web Server Optimization: Configured with dedicated execution limits and memory buffers (256MB) to ensure smooth operations during peak operational trading hours.


2.3 Adaptive Collapsible Navigation Sidebar & Viewport-Aware Tooltips
---------------------------------------------------------------------

The desktop interface incorporates an adaptive, collapsible left navigation sidebar designed to optimize screen workspace across varying monitor resolutions and POS touch displays:
  - Collapsible Sidebar Toggle: Users can toggle the sidebar between expanded (full navigation labels and role badges) and collapsed (compact icon-only mode) via the toggle button at the bottom of the sidebar. State preferences persist across navigation.
  - Viewport-Aware Dynamic Floating Tooltips:
    - In collapsed mode, hovering over any navigation icon instantly projects a floating label card.
    - Precision Placement: Tooltip coordinates are computed in real time using the icon's exact bounding box (`Math.round(rect.right + 10)` and `Math.round(rect.top + (rect.height / 2))`). The badge hovers precisely 10px to the right of the icon, vertically centered, regardless of screen DPI scaling or zoom level.
    - Legibility & Pointer: Features a high-contrast dark theme (`bg-gray-900 text-white font-semibold text-[12px] shadow-2xl`) with an angled pointer arrow aligned cleanly to the left edge without clipping or occluding text.
    - Zero Tooltip Collision: Native browser `:title` tooltips are intentionally stripped, preventing conflicting, delayed system tooltips from rendering over the application interface.
  - Role-Scoped Accessibility: Automatically filters available navigation destinations based on the active user's role (Cashier, Branch Admin, Super Admin) and operating branch status.


------------------------------------------------------------------------


3. Accessing the System
=======================


3.1 Opening the System
----------------------

1. Launch a modern web browser (Google Chrome, Microsoft Edge, or Mozilla Firefox are recommended for Web Bluetooth support).
2. Navigate to the authorized domain URL provided by your system administrator.
3. The secure Login interface is presented by default.


3.2 Logging In
--------------

1. Enter your registered corporate email address.
2. Enter your password.
3. Click Log In.
4. The system validates credentials, establishes an authenticated session, and redirects to the appropriate role-based dashboard. All data, ledgers, and terminal views are automatically scoped to your assigned role and operating branch context.

    NOTE: Customer Ecosystem Authentication: While web console personnel authenticate using email and password, the customer mobile application also supports Google OAuth API registration and login (/api/auth/google-login) alongside 6-digit SMS/email OTP verification.


3.3 Forgot Password  -  3-Step OTP Recovery & Rate Limiting
-----------------------------------------------------------

The password recovery mechanism employs a secure 6-digit One-Time Password (OTP) delivered via email, backed by strict cryptographic hashing and rate-limiting throttles:

  Step 1: Request Reset Code
  ..........................
1. On the login screen, click Forgot your password?.
2. Enter your registered email address and click Email Password Reset Code.
3. The system generates a cryptographically random 6-digit OTP valid for 10 minutes.
4. Rate Limiting Protection: The endpoint is governed by the `throttle:otp-send` middleware. A mandatory 60-second cooldown applies between resend requests.

  Step 2: Verify Code
  ...................
1. Retrieve the 6-digit code from your email inbox.
2. Enter the code into the verification input field and click Verify Code.
3. Brute-Force Safeguard: The verification endpoint is governed by `throttle:otp-verify`. Users are permitted a maximum of 5 attempts. Exceeding this threshold permanently invalidates the token, requiring the user to restart from Step 1.

  Step 3: Set New Password
  ........................
1. Enter your new password (minimum 8 characters, requiring at least one uppercase letter, one lowercase letter, and one numeric character).
2. Confirm the password in the confirmation field.
3. Click Reset Password. The session authorization expires 15 minutes after successful code verification.
4. Upon successful reset, the OTP record is purged, and you are redirected to the Login screen with a success confirmation toast.


3.4 Operating Branch Context & Interactive Branch Required Screen
-----------------------------------------------------------------

In a multi-branch franchise architecture, operational modules (POS Terminal, Order Management, Kitchen Display, and Branch Stock Ordering) require an active branch context:

  - Cashiers & Branch Administrators: Automatically bound to their assigned branch. Their context is fixed and cannot be changed, ensuring store isolation.
  - Super Administrators: Possess enterprise-wide oversight. When viewing global dashboards or reports, their context defaults to General Headquarters (All Locations).
  - Operating as a Branch (Super Admin): Super Admins can select an active store context via the topbar `BranchContextLabel` dropdown or through System Settings (Tab 6 - System).

  Interactive Operating Branch Required Screen
  ............................................
  If a Super Admin navigates directly to a store-dependent operational route (/pos, /orders, /kds, or /stock/orders) without an active branch context selected:
  1. The system intercepts the request using the `RequiresOperatingBranch` trait.
  2. Rather than displaying a raw, unstyled 403 Forbidden error, the interface presents an intuitive, high-contrast Operating Branch Required card.
  3. The card displays active branch location tiles complete with branch names, store codes (MTC-), and a one-click Select Operating Branch trigger.
  4. Selecting a branch instantly activates the store context, stores it in the user session, and renders the operational workbench seamlessly.


------------------------------------------------------------------------


4. User Roles and Permissions
=============================


4.1 Centralized Permissions Matrix
----------------------------------

| Module / Operational Workbench | Route URL | Super Admin | Branch Admin | Cashier |
|---|---|---|---|---|
| Dashboard Overview | /dashboard | Full (Global / Branch) | Full (Branch-Scoped) | Shift Scoped |
| POS Checkout Terminal | /pos | Full (Operating Branch) | Full (Assigned Branch) | Full (Assigned Branch) |
| Order Management Hub | /orders | Full (All Branches) | Full (Assigned Branch) | Full (Assigned Branch) |
| Kitchen Display System (KDS) | /kds | Full (Operating Branch) | Full (Assigned Branch) | Full (Assigned Branch) |
| Menu Management (View & Branch Toggle) | /menu | Full | View & Branch Toggle | Restricted |
| Menu Management (Create, Edit, Delete) | /menu | Full | Restricted | Restricted |
| Options Library | /management/library | Full (Create, Edit, Delete) | View Master Templates | Restricted |
| Category Taxonomies & Station Routing | /management/categories | Full | Restricted | Restricted |
| Stock & Inventory Ledger | /stock | Full (Enterprise) | Full (Branch Ledger) | View-Only Badges |
| Stock Adjustment & Physical Count | /stock/adjustment | Full (Main & Satellites) | Full (Waste & Count) | Restricted |
| Branch Stock Ordering (Satellite Console) | /stock/orders | Satellite Stores Only | Full (Assigned Branch) | Restricted |
| Commissary Dispatch (HQ Admin Console) | /stock/orders/admin | Full (Approve & Dispatch) | Restricted | Restricted |
| Multi-Branch Network Administration | /branches | Full | Restricted | Restricted |
| User & Staff Management (Own Branch) | /users | Full | Cashiers & Riders | Restricted |
| User & Staff Management (Cross-Branch) | /users | Full | Restricted | Restricted |
| Business Intelligence & Forecasting | /reports | Full (Global & Multi-Store) | Full (Assigned Branch) | Restricted |
| Customer Feedback Management | /reviews | Full (All Branches) | Full (Assigned Branch) | Restricted |
| System Settings (Global Enterprise Tabs) | /settings | Full (Tabs 1, 2, 6, 8) | Restricted | Restricted |
| System Settings (Branch-Scoped Tabs) | /settings | Full Override | Scoped (Tabs 2, 3, 4, 5, 7) | Restricted |
| Topbar Shift Notifications | /notifications | Full | Full | Full |
| Personal Profile Settings | /profile | Full | Full | Full |


4.2 Branch Scoping & Administrative Access Boundaries
-----------------------------------------------------

  - Menu Customization Boundaries: Branch Administrators can toggle a menu item between Active and Hidden for their specific store. They cannot edit product descriptions, change base selling prices, or delete catalog items.
  - Central Commissary Procurement Rule: Non-main (satellite) branches are prohibited from executing supplier "Stock In" movements in Stock Adjustment. They replenish inventory exclusively through Branch Stock Ordering (/stock/orders).
  - Branch-Scoped System Settings: Branch Administrators have access to customize local store receipt headers, static GCash QR codes, local reorder thresholds, and Web Bluetooth receipt printers under System Settings. Changes apply solely to their branch (`branch_id`) without altering global franchise defaults.


------------------------------------------------------------------------


==============================================================
  PART I: STAFF & CASHIER OPERATIONS MANUAL (FRONT-OF-HOUSE)
==============================================================


This section is tailored specifically for Cashiers, Counter Staff, Line Cooks, and Baristas performing daily front-of-house operations.


------------------------------------------------------------------------


5. Cashier Shift Orientation & Welcome Banner
=============================================


When a Cashier logs into the system, the interface greets them with an Emerald-themed shift banner:
  - Visual Badge: Emerald badge reading CASHIER ACCESS with a cash register icon.
  - Time-Aware Greeting: Dynamically displays "Good morning", "Good afternoon", or "Good evening" alongside your first name.
  - Shift Tagline: "Welcome to your shift dashboard. Front-of-house order terminal, kitchen dispatch, and payment processing."
  - Branch Confinement: The header displays your locked branch location with a pin icon (e.g., "Mister Takoyaki  -  SM City Santa Rosa"). Cashiers cannot toggle across other branches, preventing accidental cross-branch order entries.
  - Dismiss Control: Click the close (X) button on the right to dismiss the banner for the current session with an animated fade-out transition.


------------------------------------------------------------------------


6. Cashier Shift Dashboard & Live Terminal Operations
=====================================================


The Cashier Dashboard (/dashboard) is designed to provide immediate visibility into shift metrics without operational noise:
  - Today's Orders Counter: Total orders processed during the active shift.
  - Active Orders Count: Real-time counter of orders currently in Pending, Preparing, or Ready state.
  - Inventory Shortage Alerts: Live warning pill displaying any items currently out of stock or below the critical threshold.
  - Live Terminal Order Feed: A continuous, dark-themed streaming ticker showing orders as they are placed via POS or incoming delivery app channels, updating with status color badges:
    - Amber / Yellow: Pending
    - Indigo: Preparing
    - Emerald: Ready
    - Slate / Gray: Completed
  - Optimized Background Polling: Polling is tuned to prevent unnecessary server strain while keeping the feed reactive.
  - POS Console Shortcut: Direct shortcut button linking immediately to the POS terminal (/pos).


------------------------------------------------------------------------


7. Point of Sale (POS) Terminal & Complete Order Entry Guide
============================================================


7.1 Opening the POS Terminal & Stock Badges
------------------------------------------

  - Target Roles: Super Admin (with active operating branch), Branch Administrator, Cashier.
  - Navigate to POS and Orders (/pos) from the navigation sidebar.
  - Top Bar Layout: Contains Category Navigation Tabs, Live Search Bar, Held Orders indicator button (showing draft count), and the Layout Sort toggle button.
  - Main Catalog Grid: Displays interactive product cards with live search filtering and stock health indicators.
  - Right Panel (Desktop) / Bottom Drawer (Mobile): Displays the real-time Order Summary cart, line-item modifiers, discount toggles, fulfillment method selector, table/reference input, and checkout actions.

  Stock Health Indicators on Product Cards
  ........................................

| Stock Status | Visual Indicator | Operational Behavior |
|---|---|---|
| In Stock | Emerald badge showing available units | Can be freely added to cart up to the available ingredient limit. |
| Low Stock | Amber badge (10 or fewer units remaining) | Item is nearing depletion based on raw material availability. |
| Out of Stock | Grayed-out card with red "Out of Stock" badge | One or more mandatory recipe ingredients are depleted. Adding to cart is disabled. |


7.2 Adding Items to the Cart
----------------------------

  Standard (Non-Customizable) Items
  .................................
1. Tap the product card or click Add to Cart.
2. The item is instantly staged in the cart with a quantity of 1.
3. Tapping repeatedly increments quantity. When cart volume reaches the physical stock limit (calculated from raw ingredient balances), further increments are blocked.

  Customizable Items with Bounded Selection Limits (max_select)
  .............................................................
1. Tap any customizable product card to open the Customization Modal.
2. Option Groups & Selection Modes:
   - Single Choice Groups: Rendered as radio buttons. Only one option can be chosen (e.g., Drink Size: Regular or Large).
   - Multi-Choice Groups with Selection Limits (max_select): Rendered as checkboxes. If the group has a configured limit (e.g., max_select = 2), the modal displays a clear badge: "(Select up to 2)".
   - Dynamic Limit Enforcement: Once the customer selects the maximum allowable number of choices, remaining unselected checkboxes are dynamically disabled in real time. Unchecking an item re-enables the other options instantly.
3. Out of Stock Modifiers: If an option requires an ingredient that is currently depleted in store inventory, that choice is marked with an "Out of Stock" badge and disabled.
4. Tap Add to Order to stage the customized item in the cart. Adding the identical configuration increments the existing cart row.


7.3 Managing Cart Items: The Two-Column Edit Modal
--------------------------------------------------

Tapping the pencil/edit icon on any cart row opens the Two-Column Item Customization & Discount Modal:

  Left Column: Options & Modifiers Reconfiguration
  ................................................
  - Displays all option groups and modifier choices for the product.
  - Cashiers can change flavors, sizes, dips, or add-ons directly without deleting and re-entering the line item.
  - Enforces `max_select` bounds and disables depleted ingredients dynamically.

  Right Column: Quantity, Notes & Discounts
  .........................................
  - Numeric Quantity Stepper (+/-): Adjust line quantity with live ingredient inventory validation.
  - Special Instructions Textarea: Enter kitchen notes (e.g., "Extra crispy", "Sauce on the side", "No seaweed"). Notes print directly on kitchen order slips and customer receipts.
  - Regular Discount Toggle: Applies the system-configured regular discount percentage (e.g., 10%) directly to this line item.
  - Senior / PWD Discount Toggle: Applies statutory 20% Senior Citizen / PWD discount and flags the line as VAT-exempt.
  - Stock Safety Guard: The modal's Save Changes button is dynamically disabled if a required option is missing or if the requested quantity exceeds physical branch ingredient stock.

  Cart Line Removal & Renderless Cart Clearing
  ............................................
  - To delete a single row, reduce quantity to 0 or click the red trash icon.
  - Clear Cart Action: Click Clear Cart in the order header to clear the entire cart. The action uses an optimized renderless execution with an instant confirmation prompt, ensuring zero UI flicker.


7.4 Fulfillment Method and Table References
-------------------------------------------

  - Order Type Selector: Select between Dine-in, Take-out, Delivery, or Pick-up.
  - Service Charge: Automatically calculated and added to the bill only when Dine-in is selected.
  - Table / Customer Reference Field:
    - For Dine-in: Entering a Table Number is strictly enforced to ensure floor runners deliver orders accurately.
    - For Take-out, Delivery, or Pick-up: Enter the customer's name, reference buzzer number, or phone contact.


7.5 Holding and Restoring Draft Orders
--------------------------------------

  Holding an In-Progress Order
  ............................
1. If a customer needs time to decide or retrieve cash, click Hold Order (pause icon) in the Order Summary header.
2. The system generates an alphanumeric draft reference (e.g., DFT-A1B2C3) and saves all items, customizations, special instructions, discount flags, and table notes.
3. Physical ingredient inventory is not deducted while an order is held in draft status.
4. The cart is cleared immediately so staff can process the next customer.

  Restoring or Discarding Held Drafts
  ...................................
1. Click Held Orders in the top navigation bar. The numeric badge reflects active drafts.
2. The Saved Drafts Panel displays held carts with table numbers, item counts, timestamps, and subtotals.
3. Click Restore on any draft: loads all items and configurations back into the active cart, removing the draft from the held list.
4. Click the red trash icon to delete an abandoned draft.


7.6 Processing Payments & GCash Lock Safeguard
----------------------------------------------

1. Click Proceed to Payment (or Place Order) to open the checkout drawer. The left side summarizes order line items; the right side presents payment methods:

| Payment Method | Transaction Procedure & Safeguards |
|---|---|
| Cash | Enter amount tendered or tap a quick-cash preset button (Exact, 100, 500, 1000). The system instantly calculates and displays the exact change due. Amount tendered cannot be less than total due. |
| GCash (Static QR) | Displays the store's official static GCash QR code image, merchant account name, and mobile number. The customer scans and transfers the exact amount. The cashier verifies payment confirmation on the store phone, then clicks Mark as Verified. A green verified badge is displayed. |

2. Tap Place Order to finalize the transaction, execute FEFO ingredient stock deductions, route tickets to KDS, and initiate receipt printing.

    IMPORTANT: Verified Payment Protection Safeguard:
    Once a GCash payment is marked as verified:
    - The payment modal cannot be closed or dismissed (close and escape triggers are locked).
    - The cart cannot be cleared (clearCart action is disabled).
    - Line items, quantities, discounts, and order references cannot be modified.
    This guarantee prevents cashflow leaks where digital funds are received but orders are discarded. If cancellation is necessary, the cashier must complete the order and perform an authorized Void in Order Management.


7.7 Thermal Receipt Printing & Multi-Slip Separation Alert
----------------------------------------------------------

  - Direct Web Bluetooth ESC/POS Integration: Pairs directly to Bluetooth 58mm/80mm thermal printers via Google Chrome or Microsoft Edge without opening standard browser print dialogs.
  - Multi-Slip Separation Alert: When printing multiple slips (Customer Receipt, Kitchen Order Slip, Barista Slip), the system displays an on-screen prompt: "Please tear off slip 1 of 2", featuring an automated 15-second countdown timer to prevent paper jams.
  - Multi-Slip Separation Alert: When printing multiple slips (Customer Receipt, Kitchen Order Slip, Barista Slip), the system displays an on-screen prompt: "Please tear off slip 1 of 2", featuring an automated 5-second countdown timer to prevent paper jams.
  - Receipt Review QR Code Binding: Customer receipts print an aligned, high-contrast QR code linked directly to the store feedback portal (/review/{branch}?order_ref=...). The QR code is cryptographically bound to the specific transaction, ensuring legitimate customer reviews.


7.8 Menu and Category Layout Customization (Sort Persistence)
-------------------------------------------------------------

  - Authorized Roles: Super Admin, Branch Administrator.
1. Click Customize Layout in the POS top bar to toggle into Layout Edit Mode.
2. Reordering Categories: Drag category tabs horizontally to match customer ordering flow. The "All" tab is permanently pinned in position 1.
3. Reordering Product Cards: Drag cards by their drag handles to place high-volume items first.
4. Click Save Layout: Sort sequences are saved locally to the branch database records (`branch_product.sort_order` and `BranchCategorySort.sort_order`) without altering layouts of other franchise stores.


------------------------------------------------------------------------


8. Shift Order Management & Lifecycle Progression
=================================================


8.1 Overview & Reactive Synchronization
---------------------------------------

The Order Management hub (/orders) coordinates customer delivery orders, in-store POS transactions, draft orders, and historical audit ledgers. Background reactivity keeps the view synchronized with mobile app orders, KDS prep states, and rider deliveries without full page refreshes.


8.2 Tab Navigation & the 1-Hour Operational Transition Window
-------------------------------------------------------------

The module is structured into three dedicated tabs:
  - Delivery Orders (App): Displays incoming and active mobile delivery orders: Pending, Preparing, Ready, Handed to Rider, and Out for Delivery.
  - POS Orders (POS): Displays in-store point-of-sale transactions and saved held drafts: Drafted, Pending, and recently fulfilled counter orders.
  - Order History: The permanent audit ledger for all Completed, Cancelled, Voided, Refunded, and Partially Refunded transactions across all channels.

    NOTE: 1-Hour Operational Transition Window:
    When an order is completed, cancelled, voided, or refunded, it remains in its active operational tab (Delivery Orders or POS Orders) for exactly 1 hour. This grace period allows cashiers to reprint receipts, issue quick refunds, or correct errors. After 1 hour, the transaction automatically transitions to the permanent Order History tab.


8.3 Tab-Scoped Order Health Overview
------------------------------------

Four KPI cards are positioned above the order table:
  - Total Orders: Transaction volume within active tab and date filter.
  - Active Orders: Scoped dynamically to the tab:
    - In Delivery Orders: Counts pending and preparing app orders. Highlights with an amber/red pulsing alert if any order remains unresolved for 24 hours or more.
    - In POS Orders: Counts in-progress counter orders and active held drafts.
    - In Order History: Displays 0 by definition.
  - Completed Today: Orders fulfilled, served, or delivered during the current calendar day.
  - Today's Revenue: Net sales revenue collected today (Gross sales minus refunds, excluding delivery fees and voided transactions).


8.4 Search and Filtering Controls
---------------------------------

  - Live Search Input: Instant filtering across Order Reference Number, Customer Name, or Phone Contact.
  - Contextual Status Filter: Dropdown displaying only statuses relevant to the current tab.
  - Date Presets: Today, Last 7 Days, Last 30 Days, All Time, or custom calendar date range.


8.5 Managing Delivery App Orders
--------------------------------

  1. Accepting an Incoming Order
  ..............................
  - When a customer places an order via the mobile delivery app, it appears in Delivery Orders as Pending.
  - Click Accept Order: Status advances to Preparing, the food ticket is dispatched to KDS, and the thermal printer pre-warms its connection.

    NOTE: 1-Hour Auto-Rejection Safeguard:
    If an incoming mobile delivery order remains in Pending status for more than 1 hour without cashier acceptance, the automated system scheduler marks the order as Rejected with the reason "Auto-cancelled: Order not accepted within 1 hour".

  2. Rejecting an Order
  .....................
  - If ingredients are depleted or the kitchen is closed, click Reject Order.
  - The Reject Order Modal requires selecting or typing an explanation reason (e.g., "Out of takoyaki batter", "Closing time").
  - Confirming marks the order as Cancelled / Rejected and alerts the customer via the mobile app.

  3. Dispatching to Rider
  .......................
  - Once food is prepared and marked Ready on the KDS, click Hand to Rider.
  - The Assign Rider Modal lists active branch-assigned delivery riders with their phone numbers and active delivery load.
  - Selecting a rider updates status to Handed to Rider and dispatches GPS directions to the rider's mobile app.

  4. Completing an Order & Proof of Delivery (POD) Photo
  ......................................................
  - When the rider arrives at the customer's address, they capture a delivery photo in their mobile app.
  - Uploading the photo updates order status to Delivered / Completed.
  - Staff can click the Proof of Delivery preview thumbnail in the order inspector to view the full-screen photo modal with delivery timestamp and rider name.


8.6 Managing POS Draft Orders
-----------------------------

  - Resume Draft: Select a draft order and click Resume Order. The system re-loads all line items, customizations, special instructions, and table references into the active POS cart and navigates to /pos.
  - Delete Draft: Click Delete Draft with confirmation to permanently clear an abandoned order.


8.7 Processing Refunds
----------------------

  - Authorized Roles: Super Admin, Branch Administrator.
1. Open any completed order row to reveal the slide-out inspector and click Refund.
2. Enter the Refund Amount (cannot exceed remaining refundable balance) and enter a mandatory Refund Reason.
3. Click Submit Refund: Deducts the refund from financial revenue, stamps the order timeline, and records an immutable accounting entry. Physical ingredient stock is not restored during a refund.


8.8 Voiding Orders (Automated FEFO Stock Reversal)
--------------------------------------------------
8.8 Order Cancellation & Voiding Operations (Mandatory Reasons & Reverse FEFO Stock Reversal)
-------------------------------------------------------------------------------------------------

The system provides two distinct operational mechanisms to terminate orders safely while maintaining full financial and inventory integrity:

A. Cancelling In-Flight Orders (Order Cancellation):
  - Applicable Scope: Pending, Accepted, or Preparing orders that cannot be fulfilled or are cancelled by the customer before completion.
  - Authorized Roles: Super Admin, Branch Administrator, and authorized Cashiers.
  1. Open the order in the slide-out inspector or order action menu and click Cancel Order.
  2. The Cancel Order Modal launches, requiring the entry of a mandatory Cancellation Reason.
  3. Validation Rules: The cancellation reason must be between 3 and 255 characters (`cancelReason`). Form submission is blocked if empty or below length constraints.
  4. Operational Effects of Cancellation:
     - The order status transitions immediately to `Cancelled`.
     - Raw Material Restock: All ingredient inventory deducted during order placement is automatically restored to branch inventory batches via reverse FEFO lot allocation.
     - The cancellation reason, timestamp, and authorizing user ID are permanently recorded in the immutable order activity audit trail.
     - A green success toast confirms the cancellation, the modal closes, and the operational queue refreshes.

B. Voiding Completed Transactions (Order Void):
  - Applicable Scope: Completed or drafted retail orders that must be officially nullified due to billing errors, fraud, or manager override.
  - Authorized Roles: Super Admin, Branch Administrator.
1. Open the order in the inspector drawer and click Void Order.
2. Review the irreversible warning prompt and confirm.
3. Operational Effects of Voiding:
   - The transaction total is zeroed out and purged from revenue metrics.
   - All ingredient deductions performed during checkout are automatically restored to branch inventory batches via reverse FEFO movements.
   - Voids apply even to orders that were previously locked via GCash payment verification.
  1. Open the order in the inspector drawer and click Void Order.
  2. Review the irreversible warning prompt and confirm manager override.
  3. Operational Effects of Voiding:
     - The transaction total is zeroed out and purged from revenue metrics in financial reports.
     - Reverse FEFO Restoration: All ingredient deductions performed during checkout are automatically credited back to active branch inventory batches.
     - Voids apply even to orders that were previously locked via GCash payment verification.
     - The void event is written permanently to the financial audit ledger with manager credentials.


8.9 Order Detail Panel (Slide-Out Inspector)
--------------------------------------------

Clicking an order row opens a comprehensive two-tab slide-out inspector:
  - General Information Tab: Transaction channel (POS or App), branch, cashier name, customer phone, delivery address with GPS coordinates, itemized list of products, chosen options, kitchen instructions, full financial summary, and Proof of Delivery (POD) photo.
  - Activity Timeline Tab: Chronological milestone log detailing Placed, Accepted, Preparing, Ready, Handed to Rider, and Delivered states with exact timestamps and attendant names.


8.10 Viewing and Printing Receipts
----------------------------------

1. Click Print Receipt from the order inspector to open the thermal receipt preview.
2. The preview displays business identity, TIN, order reference barcode, itemized breakdown with options, VAT analysis, and the receipt review QR code.
3. Click Print to transmit the job directly to the paired Web Bluetooth thermal printer.


------------------------------------------------------------------------


9. Kitchen Display System (KDS) for Line Cooks & Baristas
=========================================================


9.1 Overview & Production Station Routing (Kitchen vs. Barista)
---------------------------------------------------------------

The KDS (/kds) is a real-time digital kitchen coordination board for line cooks and beverage baristas:
  - Station Routing: Items are categorized by production station (Kitchen for hot cooked food vs. Barista for drinks and desserts). Line staff can filter views or inspect tickets routed specifically to their station.
  - Reactive Architecture: Uses a relaxed 15-second Livewire polling cycle (`wire:poll.15s="refreshStats"`) paired with a 1-second client-side Alpine timer interval (`setInterval(_tick, 1000)`), eliminating server timeouts while keeping stopwatches fluid.


9.2 Kitchen Health Metrics Bar
------------------------------

Four live metric cards are displayed at the top of the screen:
  - Queue: Active tickets currently in preparation (status: Preparing).
  - Delayed: Tickets exceeding the configured delay threshold (default: 10 minutes). Displayed in bold red.
  - Ready: Completed orders staged on the pass awaiting customer pickup or rider handoff.
  - Served: Total transactions fulfilled today.


9.3 Elapsed-Time Timers and Visual Urgency Alerts
-------------------------------------------------

Every active kitchen ticket features an automated stopwatch tracking exact elapsed cooking time:

| Urgency Stage | Elapsed Duration | Visual Behavior |
|---|---|---|
| Normal | Less than 5 minutes | Neutral slate header with calm elapsed timer. |
| Warning | 5 to 10 minutes | Amber header warning staff to expedite preparation. |
| Critical Delayed | Exceeding 10 minutes | High-visibility red header with continuous pulsing animation. |


9.4 Processing Queue (Tab 1  -  FIFO Progression)
-------------------------------------------------

  - In-progress orders (Preparing) are displayed in strict First-In, First-Out (FIFO) sequence.
  - Each card details the order reference, channel (POS or App), table number, elapsed stopwatch, and an itemized checklist with customization notes.
  - Once food is cooked and plated, tap Mark as Ready. The ticket instantly moves to the Ready Board.


9.5 Ready Board (Tab 2  -  Mark Served & Rider Handoff)
-------------------------------------------------------

  - Displays all completed orders staged at the counter.
  - Dine-in, Take-out & Pick-up: Tap Mark as Served once food is handed to the customer. This completes the order.
  - Delivery Orders: Tap Assign Rider to dispatch the delivery ticket.


9.6 Assigning a Rider from the Ready Board
------------------------------------------

1. Tap Assign Rider on any ready delivery ticket.
2. The Assign Rider modal opens, listing active branch riders, contact numbers, and current delivery loads.
3. Tap the assigned rider: updates status to Handed to Rider, removes the card from the Ready Board, and transmits push notifications and GPS directions to the rider's phone.


9.7 Historical Record (Tab 3  -  Archived Tickets)
--------------------------------------------------

  - Permanent log of completed and dispatched kitchen tickets.
  - Supports filtering by date presets (Today, Last 7 Days, Last 30 Days, All Time) or custom calendar ranges.
  - Completed orders remain in the operational feed for 1 hour before archiving permanently to the Historical Record.


------------------------------------------------------------------------


10. Stock & Ingredient Health Monitoring (View-Only)
====================================================


While Cashiers are restricted from creating manual stock adjustments or receiving deliveries, they have view-only access to Stock & Ingredients (/stock):
  - Operational Goal: Allows front-of-house staff to verify physical ingredient availability before taking bulk walk-in or telephone orders.
  - Stock Health Badges:
    - Green (In Stock): Healthy ingredient balances.
    - Amber (Low Stock): Balances have breached the reorder point.
    - Red (Out of Stock): Ingredient depleted. Dependent menu items are disabled on the POS terminal.
  - Expiration Oversight: Cashiers can review batch expiration dates to ensure expired ingredients are never utilized. Shortages should be reported immediately to the Branch Administrator.


------------------------------------------------------------------------


11. Shift Notifications & Personal Profile
==========================================


11.1 Topbar Notifications (Optimized Background Polling)
--------------------------------------------------------
11.1 Topbar Notifications & Central Notification Center (/notifications)
------------------------------------------------------------------------

  - The bell icon in the top navigation bar alerts staff of incoming delivery orders, dispatch milestones, and low-stock warnings.
  - Background Polling: Tuned to a 30-second heartbeat (`wire:poll.30s.keep-alive`), maintaining notification responsiveness without generating excessive database traffic.
  - Unread Badge: Displays a red numeric badge indicating unread alerts. Clicking open allows marking individual alerts as read or clearing all.
The system integrates a unified, real-time notification engine coordinated by the backend `NotificationService`:

A. Topbar Shift Notification Bell:
  - Real-Time Heartbeat: Powered by an optimized 15-second polling interval (`#[Poll(15000)]`) via Livewire 3, ensuring rapid alert delivery without database connection bottlenecks.
  - Numeric Counter Badge: Displays a vivid red badge indicating unread notifications awaiting review.
  - Smart Routing (`resolveSmartLink`):
    - Clicking any notification automatically inspects the live status of the target record and routes the user directly to the exact operational view and active tab:
      - Stock Order Requests: Automatically routes to the Satellite or HQ admin console and selects the correct tab (`inbox` for pending approvals, `active` for in-transit dispatches, or `history` for fulfilled transfers).
      - Incoming Customer Orders: Opens the Order Management console with the specific order record brought into focus.
      - Stock Expiry Alerts: Directs managers directly to the batch expiration monitor in Stock Management.
  - Multi-Tenant & Broadcast Read Tracking: For store-wide broadcast alerts (where `user_id` is null, such as new order announcements to all branch cashiers), users can mark alerts as read individually or click "Mark All as Read" without overwriting unread states for fellow shift personnel.

11.2 Audio Chimes & Alert Settings
----------------------------------
B. Central Notification Center Archive (/notifications):
  - Dedicated Archive View: Staff can click "View All Notifications" in the topbar dropdown to open the full-page notification console.
  - Category Filter Tabs: Instantly segment alerts into:
    - All Notifications: Consolidated timeline across all event types.
    - Orders: Customer delivery submissions, status progressions, and handoffs.
    - Inventory & Stock Requests: Satellite stock replenishment requests, HQ approvals, dispatches, and low-inventory alerts.
    - System Alerts: Security notices, batch expiration warnings, and administrative events.
  - Date Range Filtering: Filter notifications using rapid presets (Today, Last 7 Days, Last 30 Days) or custom calendar bounds.
  - Operational KPI Cards: Four summary metric cards provide shift status at a glance:
    - Total Notifications count.
    - Unread Notifications tally.
    - Critical Stock Alerts counter.
    - Today's Activity count.
  - Bulk Actions: Select multiple notification cards to mark them as read, toggle unread status, or permanently remove archived records.

  - Audible alerts chime when incoming delivery app orders arrive at the terminal.
  - Audio chimes can be enabled or muted under Profile Settings (/profile).

11.2 Ephemeral Toast Notifications & Synthesized Web Audio Chimes (4-Tier Classification)
-----------------------------------------------------------------------------------------

The user interface employs a non-blocking, ephemeral toast notification component (`x-toast`) positioned at the bottom-right of the viewport:

A. 4-Tier Notification Architecture:
To prevent alarm fatigue and maintain high operational clarity, all system feedback is strictly categorized into four standardized tiers:

| Tier | Visual Theme | Icon | Sound Profile | Operational Purpose & Scenarios |
|---|---|---|---|---|
| **Success** | Green accent (`bg-green-500`, `text-green-600`) | Checkmark Circle | Upbeat dual-tone chime (D5 -> A5) | Executed actions, saves, deletes, order completion, and status updates (e.g., "Order #MTC-101 completed", "Settings saved"). |
| **Warning** | Amber accent (`bg-amber-500`, `text-amber-600`) | Exclamation Triangle | Harmonic dual-tone chime (E5 -> D5) | Operational validations, business guards, and policy limits (e.g., "Cart is empty", "Insufficient ingredient stock", "Verified GCash payment locked", "Option template already exists", "Access Restricted: Branch assignment required"). |
| **Info** | Blue accent (`bg-blue-500`, `text-blue-600`) | Info Circle | Muted / silent | Neutral system guidance and dataset feedback (e.g., "No data available for export"). |
| **Error** | Red accent (`bg-red-500`, `text-red-600`) | X-Mark Circle | Cautionary low dual-tone chime (A4 -> F4) | Strictly reserved for true system exceptions (`try / catch`), database rollback failures, physical thermal printer disconnection, and external payment gateway API errors (e.g., "PayMongo API key missing", "Printer test failed: device unreachable"). |

B. Synthesized Web Audio API Chimes:
  - Zero Asset Latency: Chimes are generated dynamically in the browser using the native HTML5 Web Audio API (`window.AudioContext`). No audio files (.mp3 or .wav) are downloaded, eliminating HTTP latency, 404 errors, and caching glitches.
  - Harmonic Sound Synthesis: Synthesizes pleasant sine waves (`oscillator.type = 'sine'`) with exponential gain ramps (`exponentialRampToValueAtTime`) to deliver clean, modern acoustic cues:
    - Warning Chime: E5 (659.25 Hz) transitioning to D5 (587.33 Hz) over 450 milliseconds.
    - Error Chime: A4 (440.00 Hz) descending to F4 (349.23 Hz) over 450 milliseconds.
  - Autoplay Policy Compliance: Automatically checks for browser `suspended` audio context states and resumes audio during user touch/click interactions.


11.3 Profile Avatar Customization (Foods & Animals with Gradients)
------------------------------------------------------------------

1. Click your name in the top navigation bar and select Profile Settings.
2. Click Choose Avatar to launch the Avatar Customization Picker modal.
3. Browse two curated emoji collections:
   - Foods Collection: Takoyaki, Ramen, Bento, Sushi, Dango, Bubble Tea, and Cafe Delights.
   - Animals Collection: Shiba Inu, Cat, Panda, Fox, Bear, and Owl.
4. Each icon is paired with a vibrant gradient tile.
5. Select an avatar to update your navigation profile and user cards immediately without page reloads. Click Reset Avatar to revert to letter-based initial avatars.


11.4 Password Updates & Strength Validation
-------------------------------------------

  - Users can update their security password anytime under Profile Settings.
  - Requires entering the current password, followed by the new password and confirmation.
  - The live password strength meter enforces a minimum of 8 characters, an uppercase letter, a lowercase letter, and a numeric digit.


------------------------------------------------------------------------


============================================================
  PART II: BRANCH ADMINISTRATOR MANUAL (STORE OPERATIONS)
============================================================


This section is tailored specifically for Branch Administrators, Store Managers, and Assistant Managers responsible for local store operations, inventory oversight, supply replenishment, staff supervision, and customer satisfaction.


------------------------------------------------------------------------


12. Branch Manager Orientation & Daily Responsibilities
=======================================================


When a Branch Administrator logs into the system, the interface greets them with a Rose-themed management banner:
  - Visual Badge: Rose badge reading BRANCH ADMIN ACCESS with a store building icon.
  - Time-Aware Greeting: Dynamically displays "Good morning", "Good afternoon", or "Good evening" alongside your name.
  - Store Tagline: Displays your active store location: "Overseeing [Branch Name]  -  your branch is live and operational."
  - Role Scoping Boundary: Branch Admins possess complete operational authority over their assigned branch location. They monitor store sales, adjust local inventory, request raw materials from Central HQ, toggle local menu availability, customize category display sorting, and supervise branch cashiers and riders. They cannot access or modify settings, ledgers, or records belonging to other branch stores.


------------------------------------------------------------------------


13. Branch Operational Dashboard & Local Performance Telemetry
==============================================================


13.1 Overview & Scoped Store Telemetry
--------------------------------------

The Dashboard (/dashboard) unifies real-time financial telemetry, sales channel distribution, ingredient consumption velocity, stock deficiency alerts, and a live terminal order feed scoped specifically to the store:
  - Target Audience: Administrator (Branch-scoped operational view).
  - Reactive Recalculation: Metrics update immediately upon order completion, void, refund, or stock adjustment. An in-memory request-level segment cache guarantees fast rendering without serving stale data.


13.2 RBAC Welcome Banner
------------------------

  - Responsive Banner: Rendered with a Rose gradient theme, store icon, and the subtitle: "Branch operational control, inventory oversight, and staff management."
  - Dismiss Action: Clicking the close (X) button dismisses the banner for the active session.


13.3 Scoped Control Panel
-------------------------

  - Locked Branch Indicator: Displays your assigned branch name with a location pin icon. Cross-branch switching is disabled to guarantee store data confinement.
  - Integrated 3-in-1 Date Filter (x-date-filter): Scopes all dashboard cards, charts, and metrics simultaneously.
    - Dual-month calendar view on desktop; single-month view on mobile devices.
    - Quick presets: Today, Yesterday, This week, Last week, This month, Last month, This year, Last year, All time.
    - Date Validation Engine: Prevents future date selections and ensures start dates precede end dates with visual alert banners.


13.4 Financial Intelligence KPI Cards & ApexCharts Sparklines
-------------------------------------------------------------

Five interactive cards summarize store fiscal health:

| KPI Card | Operational Definition | Visual Highlights |
|---|---|---|
| Gross Revenue (Hero Card) | Total store revenue before deductions, including discounts. | Live pulsing emerald indicator, total discounts applied counter, and green gradient sparkline. |
| Gross Profit (Cashflow Card) | Net sales minus COGS and recorded inventory wastage. | Displays Gross Profit Margin percentage (%), green revenue inflow bar, and 12-block pulsing rose COGS & Spoilage bar. |
| Net Sales | Store sales collected excluding delivery surcharges. | Displays exact delivery fee deduction notice and emerald sparkline. |
| Average Order Value (AOV) | Average customer spend per transaction (Net Sales / Orders). | Displays average spend per transaction with indigo sparkline. |
| Ingredient Costs (COGS) | Direct raw material cost of sold items, derived from recipes. | Displays period cost of goods sold with rose sparkline. |

    NOTE: Clicking any KPI card or its "View breakdown ->" trigger opens the Intelligence Report Slide-Over Panel for granular inspection.


13.5 Intelligence Report Slide-Over Panel & Profit Waterfall
------------------------------------------------------------

Clicking a KPI card opens a slide-over drawer from the right side of the screen:
  - Identity & Formula Header: Displays metric calculation formulas:
    - Gross Profit: Net Sales - COGS - Waste
    - Margin (%): (Gross Profit / Net Sales) x 100
  - Profit Waterfall View: For Gross Profit, renders an accounting waterfall:
    1. Total Revenue Inflow (Gross Sales).
    2. Direct Resource Consumption (Recipe COGS).
    3. Variance & Spoilage Deductions (Logged kitchen waste and stocktake deficits).
    4. Gross Profit Result with color-coded profit margin health badge.
  - Categorical Distributions: Breaks down revenue and COGS by product category, and segments ticket sizes into AOV brackets (Light Snack, Standard Meal, Family Pack, Party/Bulk).


13.6 Branch Operations Index (Staff Ratios & Local Deficits)
-----------------------------------------------------------

Four high-level operational counters:
  - Menu Items: Active products currently available on the branch menu.
  - Ingredients: Raw materials and packaging items tracked in local inventory.
  - Inventory Alerts: Combined count of depleted ingredients and batches expiring within 7 days. Highlights in bold red when alerts exist.
  - Staff: Ratio of active personnel to total registered staff at the branch (e.g., 3 / 4 Active Accounts).


13.7 Spend & Revenue Activity Chart (Hourly & Daily Resolutions)
----------------------------------------------------------------

Powered by ApexCharts to visualize store revenue trends:
  - View Modes: Sales (Revenue vs. COGS), Volume (Order counts), and Profit (Gross Profit vs. COGS).
  - Adaptive Time-Resolution:
    - Single-day filters (Today, Yesterday) automatically render a 24-hour hourly resolution (00:00 to 23:00).
    - Multi-day, monthly, or yearly ranges group data by day or week.


13.8 Secondary Analytical Grid
------------------------------

  - Fulfillment Split: Order volume and percentage split across Dine-in (Emerald), Take-out (Blue), Delivery (Amber), and Pick-up (Indigo).
  - Payment Methods Breakdown: Transactions split between Cash and Static QR GCash with monetary totals in PHP.
  - Ingredient Consumption (Usage Velocity): Fastest-moving raw materials ranked by daily consumption rate (units / day) with rose progress bars.
  - Inventory Efficiency Battery Indicator: High-tech segmented battery graphic:
    - Emerald (>80%): Efficient turnover with minimal waste loss.
    - Amber (50% - 80%): Moderate variance requiring monitoring.
    - Rose (<50%): Severe waste or stock discrepancies detected.


13.9 Operational Workstation Grid
---------------------------------

  - Top Products: Top 5 best-selling menu items ranked by volume with item name, category badge, units sold, and revenue.
  - Stock Alerts Widget (Sliding Tabs):
    - Shortage Tab: Ingredients below minimum thresholds with an instant link to Stock Ordering (/stock/orders).
    - Expiry Tab: Batches expiring within 7 days or already expired with countdown badges (e.g., 2d) and a link to Stock Management (/stock).
  - Live Order Feed: Dark console widget displaying the 10 most recent branch orders in real time with status borders (Amber for Preparing, Emerald for Ready, Gray for Completed).


------------------------------------------------------------------------


14. Local Menu Availability & Category Sorting Customization
============================================================


14.1 Toggling Product Availability per Branch (Active vs. Hidden)
-----------------------------------------------------------------

If a branch runs out of a specific ingredient or chooses not to serve an item:
1. Navigate to Menu Management (/menu).
2. Locate the product in the table or card grid.
3. Click the Branch Status Toggle switch to flip between Active and Hidden.
4. Toggling to Hidden immediately removes the item from the branch POS catalog and mobile delivery app for that store location.
5. Master Data Integrity: Branch Admins cannot edit base prices, descriptions, recipes, or delete master products, ensuring network catalog uniformity.


14.2 Drag-and-Drop Category and Product Sort Order Persistence
--------------------------------------------------------------

Branch Admins can tailor the visual order of items on their POS terminal to match their counter workflow:
1. On the POS Terminal (/pos), click Customize Layout.
2. Reordering Categories: Drag category tabs horizontally. The "All" category is locked in position 1.
3. Reordering Products: Grab product cards using their drag handles to set the preferred sequence.
4. Click Save Layout: Changes are persisted to `branch_product.sort_order` and `BranchCategorySort.sort_order` specifically for your branch.


14.3 Options Library (Branch Perspective: Viewing Master Templates)
-------------------------------------------------------------------

Branch Administrators have read-only access to master option templates (/management/library):
  - Inspect standardized option groups (e.g., "Takoyaki Sauce Selection", "Drink Sweetness").
  - Review Single vs. Multiple selection modes and configured selection limits (`max_select`).
  - Verify mapped ingredient deductions without modifying franchise templates.


------------------------------------------------------------------------


15. Local Stock & Batch Inventory Management
============================================


15.1 Overview & Branch Ledger
-----------------------------

The Stock Management hub (/stock) governs the local store's raw materials, packaging, batch tracking, and expiry records:
  - Branch Scoping: Branch Admins supervise inventory balances held physically within their store.
  - FEFO Deduction Rule: All sales and waste deductions automatically retire ingredients from the earliest-expiring batch first.


15.2 Inventory KPI Dashboard
----------------------------

Four live metric cards:
  - Total Catalog: Number of raw material assets tracked at the store.
  - Low Stock: Count of ingredients at or below configured reorder thresholds (amber).
  - Expiring Batches: Batches expiring within 7 days or fewer.
  - Monthly Procurement: Total value of stock transferred from HQ and received during the current month.


15.3 Ingredient Configuration and Bulk Conversions
--------------------------------------------------

Raw materials support multi-tier packaging hierarchies configured by headquarters:
  - Base Unit: Grams (g), Milliliters (ml), or Pieces (pcs) used directly in recipes.
  - Bulk Conversions: 1 Bottle = 500 ml; 1 Box = 12 Bottles (cascading to 6,000 ml).
  - Base Unit Cost Re-indexing: Automatically divides bulk purchase costs down to the base recipe unit to ensure accurate COGS.


15.4 Inventory List and Batch Detail View
-----------------------------------------

The main table presents Ingredient Name, Category, On-Hand Stock, Total Value, Stock Health Badge (Green, Amber, Red), and Active Batches. Clicking any row expands the Batch Detail View displaying batch numbers, quantities, costs, and supplier expiration dates.


15.5 Expiry Tracking & Batch Disposal Workflow
----------------------------------------------

Switch to the Expiry Tracking tab to inspect batch life-cycles:
  - Expired: Expiration date is in the past (prominent red badge).
  - Expiring Soon: Expires within 7 days (amber badge).
  - Fresh: Healthy expiration buffer (green badge).

  Batch Disposal Workflow
  .......................
1. Locate the damaged or expired batch in Expiry Tracking.
2. Click Dispose Batch (trash icon).
3. Review the Batch Disposal Modal detailing ingredient name, batch number, unit cost, and quantity.
4. Click Confirm Disposal: Batch balance is zeroed, branch stock is decremented, and a permanent Waste Movement is recorded in the financial ledger.


15.6 Automated Daily 6:00 AM Stock Email Alerts
-----------------------------------------------
15.6 Automated Daily 6:00 AM Stock Email & Bell Alerts
-----------------------------------------------------

The automated system compiles and emails a daily localized inventory digest at 6:00 AM:
  - Lists low-stock ingredients requiring replenishment from HQ.
  - Warns of batches expiring within 7 days.
  - Protected by a concurrency mutex lock ensuring only one email transmission per day.
The automated system compiles and distributes daily localized inventory health alerts at 6:00 AM via the `CheckStockAlerts` command:
  - Email Digest: Sends a formatted summary table to store managers detailing low-stock ingredients breaching reorder thresholds and batches expiring within 7 days.
  - Synchronized In-App Bell Notifications: Dispatches real-time alerts through `NotificationService` to branch managers and administrators, featuring Smart Links that navigate directly to the affected inventory batches or reorder screen.
  - Concurrency Mutex Protection: Protected by a database-backed concurrency mutex lock ensuring exactly one alert run per day without duplicate emails or alerts.


------------------------------------------------------------------------


16. Stock Adjustments, Spoilage & Physical Count Reconciliation
===============================================================


16.1 Overview & Central Commissary Rules
========================================

The Stock Adjustment module (/stock/adjustment) manages kitchen waste, non-sales checkouts, and physical stocktaking audits:
  - Central Commissary Rule: Only the Main Branch is permitted to record supplier "Procurement (Stock In)".
  - Satellite Branch Behavior: For all satellite branches, the system defaults new adjustments to `Waste`. The `Stock In` option is strictly guarded and disabled in both interface and backend to prevent unverified inventory creation.
  - Auto-Fill Master Cost: Selecting an ingredient automatically pre-fills the unit cost from the ingredient master record.


16.2 Audit Ledger & Period KPI Cards
------------------------------------

Displays Period Logs, Waste Count, Restock Value, and Out Count within the selected date range. Click Export Ledger to download an immutable audit spreadsheet (CSV or PDF).


16.3 Staged Adjustment Queue Engine
-----------------------------------

Prevents incomplete entries by staging inventory movements before committing them atomically:
1. Navigate to Stock Adjustment and click New Adjustment.
2. Select the Ingredient from the dropdown.
3. Select Movement Type: Waste, Stock Out, or Return to Supplier.
4. Enter the Quantity and optional internal operational notes.
5. Click Add to Queue: The item is staged in the review list. Repeat for additional ingredients.
6. Inline Validation: If the queue is empty or quantities exceed stock, clear inline validation errors and toast notifications guide the user.
7. Click Commit Adjustments and confirm: All queued movements are written atomically to the database.


16.4 Physical Count Reconciliation Sheet & Live Variance Calculation
--------------------------------------------------------------------

1. Click Stock Reconcile in the adjustment toolbar.
2. The sheet lists every tracked ingredient alongside its recorded System Quantity.
3. Count physical shelf stock and type verified numbers into the Actual Quantity column.
4. Live Variance Calculation: The system computes `Variance = Actual Quantity - System Quantity` in real time:
   - Negative Variance (Deficit): Triggers an automatic FEFO deduction and logs a reconciliation shortage loss.
   - Positive Variance (Surplus): Creates an untracked adjustment batch to increment branch stock to match reality.
5. Click Reconcile Inventory and confirm: Only rows with entered counts are updated; unverified items remain untouched.


------------------------------------------------------------------------


17. Branch Stock Ordering (Requesting Supplies from HQ)
=======================================================


17.1 Satellite Branch Console (/stock/orders) & Exclusive Supplier Architecture
-------------------------------------------------------------------------------

Satellite retail branches replenish inventory from the central commissary warehouse (Main Branch) through the ordering console (/stock/orders):
  - Exclusive Supplier Rule: The central Main Branch acts exclusively as supplier and warehouse. It cannot place orders to itself.
  - Background Polling: Tuned to a 30-second interval (`wire:poll.30s`) to reflect approval and dispatch milestones.
  - Three Dedicated Panels:
    - Active Requests: Tracks submitted, approved, packing, and in-transit orders with live progress bars.
    - New Stock Request: Interactive ordering workbench.
    - Transfer History: Completed, delivered, and rejected historical transfers.


17.2 Low-Stock Deficit Assistant & Real-Time Commissary Stock Verification
--------------------------------------------------------------------------

  - Low-Stock Deficit Assistant: Audits store stock against minimum thresholds. Depleted items display an amber warning badge with an Add to Cart button that automatically calculates and stages the exact deficit quantity.
  - Real-Time Commissary Verification: When entering requested quantities, the system verifies live stock balances at the Main Branch warehouse. If requested amounts exceed commissary stock, an inline warning prevents submitting unrealistic requests.


17.3 Request Priority Levels
----------------------------

| Priority | Operational Condition | Visual Styling |
|---|---|---|
| Normal | Standard scheduled weekly restocking. | Slate badge. |
| Urgent | Stock projected to deplete within 24 to 48 hours. | Amber badge. |
| Critical | Immediate stockout or operational halt. | High-visibility red badge alerting HQ dispatchers. |


17.4 Geodesic Logistics Fee Calculation Engine & Clamping Limits
----------------------------------------------------------------

Logistics delivery fees are calculated automatically using geodesic GPS coordinates between the Main Branch and the store:
$$\text{Delivery Fee} = \text{Base Fee} + (\text{Distance in km} \times \text{Rate per km})$$
  - Fee Clamping: Clamped between configured Minimum Fee and Maximum Fee Cap limits.
  - Free Delivery Threshold: If order subtotal exceeds the threshold, delivery is automatically waived (PHP 0.00).
  - HQ Override: Super Admins can adjust or waive fees during fulfillment review.


17.5 The 5-Stage Order Fulfillment Lifecycle
--------------------------------------------

```
[1. Pending] --> [2. Approved] --> [3. Preparing] --> [4. In Transit] --> [5. Delivered]
      |               |
      +--> Cancelled  +--> Rejected
```

1. Pending: Branch submits request. Editable or cancellable by branch manager until approved.
2. Approved: HQ Super Admin verifies stock, adjusts approved quantities if needed, and approves.
3. Preparing: Warehouse team packs supplies into crates.
4. In Transit (Dispatched): HQ driver departs. Warehouse inventory is deducted immediately via FEFO.
5. Delivered: Shipment arrives at store. Store manager verifies crates and confirms receipt.


17.6 Submitting a Stock Request  -  Step by Step
------------------------------------------------

1. Navigate to Stock Ordering (/stock/orders) and open New Stock Request.
2. Use the Deficit Assistant to add depleted items or manually select ingredients and packaging units.
3. Enter requested quantities, choose Priority Level, and enter delivery instructions.
4. Review subtotal, estimated logistics fee, and grand total.
5. Click Submit Order and confirm.


17.7 Receiving Deliveries & Automatic Inventory Ingestion
---------------------------------------------------------

1. When the delivery vehicle arrives, open Active Requests (status: In Transit).
2. Physically inspect incoming crates against the digital dispatch manifest.
3. Click Confirm Delivery.
4. Automatic Ingestion: Transferred batches are committed immediately into local branch inventory (preserving original supplier lot numbers, expiry dates, and unit acquisition costs). The order is archived to Transfer History.


------------------------------------------------------------------------


18. Branch Staff Oversight (Cashiers & Delivery Riders)
=======================================================


18.1 Overview & Directory Filtering
-----------------------------------

Under User Management (/users), Branch Administrators manage store personnel:
  - Scoped Management: Branch Admins can provision, edit, and supervise Cashiers and Delivery Riders assigned to their store.
  - Display Modes: Toggle between Table View and visual Card View.
  - Search & Filters: Search by name, email, phone, or employee ID, and filter by status (Active / Inactive).


18.2 Standardized Staff Roles & Positions
-----------------------------------------

  - Cashier: Front-of-house checkout, cash/GCash processing, and ticket handoff.
  - Delivery Rider: Mobile order delivery, GPS navigation, and digital Proof of Delivery photo capture.


18.3 Provisioning Branch Staff with Searchable PSGC Address Comboboxes
----------------------------------------------------------------------

1. In User Management, click Add User.
2. Enter Full Legal Name, Email Address, and Philippine Mobile Number (+63).
3. Set Password (minimum 8 characters with uppercase, lowercase, and number).
4. Select Role (Cashier or Rider). Branch assignment is locked to your store.
5. Address Entry via Searchable PSGC Comboboxes:
   - Modernized Alpine comboboxes allow typing to search Region, Province, City/Municipality, and Barangay with instant filtering.
   - Cascading Hierarchy: Selecting Region updates Provinces; selecting Province updates Cities; selecting City updates Barangays.
6. Click Create User.


18.4 Manager Conflict Resolution Modal
--------------------------------------

If an administrative user is assigned to a store that already has an active manager, the system displays the Manager Conflict Resolution Modal, prompting confirmation before safely reassigning managerial credentials within an atomic database transaction.


18.5 Staff Performance Dossiers & Order Audit Logs
--------------------------------------------------

Clicking View Performance on any staff profile opens their operational dossier:
  - Overview Tab: Total orders processed, revenue generated, average ticket value, and completion rates.
  - Order Audit Log: Paginated transaction list with date filters and slide-out order detail inspections.
  - Profile Details: Employment date, PSGC address, and contact information.


18.6 Account Deactivation vs. Deletion Safeguards
-------------------------------------------------

  - Deactivation (Toggle): Setting status to Inactive terminates active sessions immediately, preventing login while preserving historical sales and KDS records.
  - Deletion Safeguard: Permanent account deletion is restricted to Super Admins. When a rider is deleted, foreign keys on proof-of-delivery records are safely set to null to preserve historical integrity.


18.7 Built-In Avatar Customization Picker
-----------------------------------------

Branch staff can personalize profile avatars using the built-in modal picker featuring curated Food and Animal collections over custom gradient backdrops.


------------------------------------------------------------------------


19. Store Customer Reviews & Feedback Management
================================================


19.1 Public Customer Review Portal (/review/{branch})
-----------------------------------------------------

Customers access the store feedback page without account creation via direct URL (/review/{branch}) or by scanning the receipt QR code.


19.2 Direct Receipt QR Order Binding & Review Limit Safeguards
--------------------------------------------------------------

  - Encrypted Order Reference Binding: Receipt QR codes encode a direct parameter (`?order_ref=...`), binding the review submission directly to the customer's physical purchase.
  - Review Limits per Order: Governed by system settings (`reviews_max_per_order`, default: 1). Prevents duplicate reviews against a single purchase receipt.
  - Verified Receipt Badge: Reviews submitted through verified receipt QR codes are marked with a prominent "Verified Receipt Order" badge in the management console.


19.3 Device Anti-Spam Tracking & Dedicated Cooldown Screen
----------------------------------------------------------

To eliminate fraudulent spam submissions:
  - Multi-Factor Device Tracking: Identifies devices via a secure, persistent signed cookie (`mtc_device_id`), IP address, and browser client fingerprint.
  - Cooldown Window: Configurable in System Settings (e.g., 24 hours between submissions).
  - Dedicated Cooldown Screen: If a customer or device accesses `/review/{branch}` during an active cooldown period, the system renders a dedicated Cooldown Screen on initial page load:
    - Displays an animated timer badge and exact date/time when feedback can be submitted again.
    - Prevents wasting customer time filling out forms that would later fail submission.


19.4 Review Management Console (/reviews) & Slide-Out Inspector
---------------------------------------------------------------

  - Management Hub: Access via Customer Reviews (/reviews).
  - Background Polling: Tuned to a 30-second frequency (`wire:poll.30s`).
  - Metrics Bar: Total Reviews, Store Average Rating (out of 5.0 stars), and Latest Submission timestamp.
  - Slide-Out Inspector: Click any review row to view questionnaire star ratings, open feedback text, verified receipt tags, customer contact info, and submission timestamps.


------------------------------------------------------------------------


20. Branch Business Reports & Sales Analytics
=============================================


20.1 Overview & Immutable Financial Ledger
------------------------------------------

The Business Intelligence hub (/reports) operates on an immutable financial ledger derived directly from sales, refunds, voids, and batch recipe deductions:
  - Authorized Roles: Super Admin and Branch Administrator (scoped strictly to their store).
  - Synchronized Date Scoping: The 3-in-1 date filter scopes all calculations, regression models, and ledgers across all tabs simultaneously.


20.2 Tab 1  -  Performance (Financial Summary & 3-Tier COGS Fallback)
----------------------------------------------------------------------

Core Financial Formulas:
  - Gross Revenue: $\text{Net Sales} + \text{Discounts}$
  - Net Sales: $\text{Cash & GCash Collected} - \text{Delivery Surcharges} - \text{Refunds}$
  - Gross Profit: $\text{Net Sales} - \text{COGS} - \text{Wastage Loss}$
  - Gross Profit Margin (%): $(\text{Gross Profit} \div \text{Net Sales}) \times 100$
  - Average Order Value (AOV): $\text{Net Sales} \div \text{Completed Order Count}$

3-Tier COGS Fallback Hierarchy:
Every sold item's cost is resolved via an automated fallback lookup:
1. Branch Standard Cost: Local unit cost in the store's inventory.
2. Latest Purchase Price: Most recent transfer intake cost.
3. Global Master Cost: Master catalog default cost.


20.3 Tab 2  -  Forecasting & Prescriptive Restock Intelligence
--------------------------------------------------------------

Predictive Engine (Weighted Linear Regression):
  - Outlier Cleansing: Strips sales spikes outside $1.5 \times \text{IQR}$ boundaries.
  - Exponential Recency Weighting: Prioritizes recent sales days to capture emerging trends.
  - Day-of-Week Seasonality: Calculates multipliers for Monday through Sunday.
  - Damped Trend & Floor: Damped trend factor (0.98 short-term) with a 30% rolling mean baseline floor.

Prescriptive Restock Recommendations:
$$\text{Recommended Quantity} = \text{Projected 14-Day Demand} + \text{Safety Stock} - \text{Current Stock}$$
  - Stockout Correction (+30%): If stock is zero, historical sales understate true demand. The engine applies a +30% multiplier (`projectedDemand * 1.3`).
  - Waste-Aware Safety Buffer: High-waste items receive scaled safety buffers down from 15% to 5%:
    $$\text{Safety Stock \%} = \max(0.05, 0.15 - (\text{Waste Rate} \times 0.5))$$
  - Coverage Days: $\text{Current Stock} \div \text{Daily Projected Demand}$.
  - Urgency Tiers: Critical (Red, zero stock), High (Amber, <=3 days), Medium (Yellow, 4-7 days), Low (Green, >7 days).
  - Direct Workflow Integration: Click Open Stock Workflow on any recommendation card to stage reorder items directly in Stock Ordering.
  - Prescriptive CSV Export: Download standardized reorder spreadsheets with demand and safety stock breakdowns.


20.4 Tab 3  -  Products (Volume & Revenue Rankings)
---------------------------------------------------

  - Product Performance Table: Ranks items by volume, gross sales, average price, and revenue contribution.
  - Seasonality Patterns: Interactive chart toggling between Weekly (day-of-week) and Monthly (annual) trends.


20.5 Tab 4  -  Operations (24-Hour Heatmap & Channel Split)
-----------------------------------------------------------

  - 24-Hour Trading Heatmap: Visualizes sales volume and revenue by hour (00:00 to 23:00) to optimize shift staffing.
  - Fulfillment Split: Walk-in counter vs. online delivery orders.
  - Payment Proportions: Cash vs. GCash transactions.


20.6 Tab 5  -  Sales (Consolidated Order Ledger)
-----------------------------------------------

  - Searchable transaction ledger with reference numbers, order types, payment methods, cashier names, and totals.
  - Click any row to open the complete Order Detail drawer.


20.7 Executive Report Generation (PDF Streaming, CSV, Excel)
------------------------------------------------------------

Click Generate Report in the top toolbar to stream a standardized executive PDF report (`Branch_Report_YYYY-MM-DD.pdf`) or export raw data in CSV/Excel formats.


------------------------------------------------------------------------


21. Branch Operational Settings
===============================


Branch Administrators have scoped access to configure store-specific settings under System Settings (/settings):
  - Receipt Customization: Configure local store telephone numbers, exact street address notes, and promotional receipt footer messages.
  - Inventory Alert Thresholds: Adjust local low-stock threshold levels and 7-day expiry warning windows.
  - POS Configuration: Upload the branch's official static GCash QR code image and specify the merchant account name.
  - Thermal Printer Setup: Pair local Web Bluetooth ESC/POS receipt printers and execute diagnostic test prints.


------------------------------------------------------------------------


===============================================================
  PART III: SUPER ADMIN ENTERPRISE MANUAL (GLOBAL GOVERNANCE)
===============================================================


This section is tailored specifically for the Business Owner, Franchise Executives, and Senior System Administrators possessing unrestricted system-wide governance privileges.


------------------------------------------------------------------------


22. Super Admin Enterprise Orientation & Global Scope
=====================================================


When a Super Admin logs into the system, the interface greets them with an Indigo-themed command banner:
  - Visual Badge: Indigo badge reading SUPER ADMIN ACCESS with a security shield icon.
  - Time-Aware Greeting: Dynamically displays "Good morning", "Good afternoon", or "Good evening" alongside your name.
  - Enterprise Tagline: "Enterprise command center and network-wide telemetry. You have full system privileges across all branches."
  - Global Governance Authority: Super Admins possess unrestricted operational authority over all system modules and branch locations. They govern the master product catalog, recipe formulations, Options Library templates, cross-branch supply chain approvals, branch onboarding with GPS mapping, network staff administration, Prescriptive Restock Analytics, and all 8 platform configuration tabs.


------------------------------------------------------------------------


23. Enterprise Command Dashboard & Network Telemetry
====================================================


23.1 Global vs. Branch View Selector (All Locations)
----------------------------------------------------

Located in the top control bar, the Branch Selector dropdown allows Super Admins to toggle between:
  - All Locations (Global / Enterprise Overview): Aggregates sales, orders, ingredient consumption, and financial metrics across the entire franchise network.
  - Specific Branch Views: Focuses metrics on any individual store (e.g., SM City Santa Rosa, Cabuyao, Calamba) for deep-dive operational reviews.


23.2 Integrated 3-in-1 Date Filter with 9 Presets & Validation
--------------------------------------------------------------

The date filter (x-date-filter) scopes all enterprise telemetry simultaneously:
  - Responsive Dual-Month Calendar: Side-by-side view on desktop monitors with click-to-select start and end dates.
  - 9 Quick Presets: Today, Yesterday, This week, Last week, This month, Last month, This year, Last year, All time.
  - Validation Safeguards: Blocks future dates and prevents start dates from following end dates.


23.3 Financial Intelligence KPI Cards & Pulsing Gross Profit Bar
----------------------------------------------------------------

Five interactive enterprise cards powered by ApexCharts sparklines:
  - Gross Revenue: Total network sales before deductions. Displays aggregate discounts counter.
  - Gross Profit: Network sales minus total COGS and recorded wastage. Features a solid green Revenue Collected bar and a 12-block pulsing rose COGS & Spoilage outflow bar.
  - Net Sales: Total sales excluding delivery fees.
  - Average Order Value (AOV): System-wide average customer spend per transaction.
  - Ingredient Costs (COGS): Direct cost of raw materials consumed across all kitchens.


23.4 Intelligence Report Slide-Over Drawer & Network Profit Waterfall
---------------------------------------------------------------------

Clicking any KPI card opens the full-height slide-over drawer (x-side-panel):
  - Profit Waterfall: Visually traces Network Gross Inflow -> Franchise Recipe COGS -> Network Waste/Discrepancy -> Net Gross Profit.
  - Categorical Performance: Breakdown by product category across all stores.
  - Spend Distribution Buckets: AOV bracket breakdown (Light Snack, Standard Meal, Family Pack, Party/Bulk).


23.5 Leaflet Interactive Branch Live Map & 520px Expanded Modal
---------------------------------------------------------------

Visualizes the geographical footprint and sales performance of all Laguna branch locations:
  - Map Modes: Aerial Satellite (ESRI), Street View (OpenStreetMap), and Minimalist Vector (CartoDB Positron).
  - Interactive Store Pins: Hovering reveals store name, period sales amount, and percentage share of total network sales.
  - Top Store Highlight: The highest-grossing branch is rendered with a pulsing emerald marker and a star badge.
  - Expanded Modal (520px Canvas): Click the expand icon to launch the full-screen modal featuring an expanded 520px high map canvas and a 4-column store comparison grid.
  - Branch Leaderboard: Ranks stores by revenue and network market share.


23.6 Multi-Branch Comparative Insights & Executive PDF Report Streaming
-----------------------------------------------------------------------

  - Comparative Insights Panel: Select multiple branches to compare revenue, ticket volume, and staff efficiency side-by-side.
  - Executive PDF Streaming: Click Generate Dashboard to stream a standardized executive PDF report (`Executive_Report_YYYY-MM-DD.pdf`) containing financial tables, top products, shortage warnings, and restock velocity.


------------------------------------------------------------------------


24. Master Product Catalog & Recipe Engineering
===============================================


24.1 Catalog Navigation & Display Modes
---------------------------------------

Access via Menu Management (/menu):
  - Real-Time Search: Filter by product name, SKU, or description.
  - Multi-Tier Filters: Filter by Category, Branch Scope, or Availability (Available vs. Hidden).
  - View Toggle: Switch between dense Table View and visual Card Grid.


24.2 The 4-Tab Product Workbench
--------------------------------

Creating or editing a product uses an organized 4-tab workbench:

  Tab 1  -  Information
  .....................
  - Product Name: System-wide unique identifier (2 to 255 characters).
  - Category: Select category or click (+) to quick-add inline.
  - Sale Price: Base retail selling price in PHP.
  - Status: Available or Hidden.
  - Product Image: Upload high-resolution PNG, JPG, or WEBP images (up to 4 MB).

  Tab 2  -  Options
  ................
  - Add Option Group: Configure customization groups (e.g., "Flavors", "Sizes", "Dips").
  - Single vs. Multiple Selection Modes:
    - Single Choice: Radio button interface where customers choose exactly one item.
    - Multiple Choice: Checkbox interface allowing multiple selections.
  - Bounded Selection Limits (`max_select`): Specify maximum allowed selections (e.g., choose up to 2 dips). Enforced in POS and validated in mobile APIs.
  - Pricing Modes: Additive (adds to base price) or Fixed (overrides base price).
  - Non-Depleting Toggle: For choices not consuming raw stock (e.g., sweetness level).

  Tab 3  -  Recipe
  ................
  - Base Recipe Ingredients: Raw materials consumed whenever the product is prepared.
  - Option-Specific Recipe Ingredients: Raw materials consumed only when specific option choices are ordered.
  - Duplicate Protection: Blocks adding the same ingredient multiple times within the same base or option scope.

  Tab 4  -  Profitability
  .......................
  - Dynamic COGS Engine: Sums raw material costs based on current ingredient procurement values.
  - Margin Calculations: Computes Net Margin and Gross Profit Margin (%).
  - Margin Health Badges: Green (>=60%, Healthy), Yellow (35%-59%, Moderate), Red (<35%, Low).


24.3 Single/Multi Option Selection Modes & Bounded Selection Caps (max_select)
------------------------------------------------------------------------------

The options engine allows configuring strict selection bounds:
  - Set `type` to `single` for mutually exclusive options (e.g., drink size).
  - Set `type` to `multiple` and configure `max_select` (e.g., 2 or 3) for bounded add-ons. If unlimited selections are allowed, leave `max_select` blank.
  - When customer selections reach `max_select`, POS and mobile interfaces prevent further selections.


24.4 3-Tier Recipe Costing & Dynamic Margin Calculation
-------------------------------------------------------

Every recipe cost is dynamically calculated against current branch acquisition prices using the 3-tier fallback hierarchy (Branch Standard -> Purchase Price -> Global Master Cost).


24.5 Creating, Editing & Deleting Products Step by Step
-------------------------------------------------------

1. Navigate to Menu Management and click Add Product.
2. Complete Information tab fields, upload a product image, and set status to Available.
3. Configure Option Groups on the Options tab, or click Import from Library.
4. On the Recipe tab, map base ingredients and option-specific ingredients.
5. Review the Profitability tab to verify gross margin percentages.
6. Click Save Product.
7. Deletion: Only Super Admins can delete products. Deleting removes the product image, recipe mappings, and branch availability records permanently.


24.6 Per-Branch Availability Governance
---------------------------------------

From the catalog list, Super Admins can toggle the Availability badge for any branch to immediately enable or disable selling that item at that location.


24.7 Quick-Adding a Product Category Inline
-------------------------------------------

Click the plus (+) icon beside the Category dropdown on Tab 1 to create a new category (Name and Station: Kitchen or Barista) without leaving the workbench.


------------------------------------------------------------------------


25. Options Library Management
==============================


25.1 Reusable Master Option Templates
-------------------------------------

Access via Options Library (/management/library):
  - Standardizes customization blueprints across the entire franchise (e.g., "Standard Sugar Levels", "Topping Choices", "Cup Sizes").
  - Templates store option choices, pricing logic, selection limits, and pre-mapped ingredient recipe deductions.


25.2 Template Architecture (Single/Multi Modes, max_select, Additive vs. Fixed)
--------------------------------------------------------------------------------

  - Template Name: Unique master name.
  - Selection Mode: Single Choice (Radio) or Multiple Choice (Checkboxes).
  - Selection Cap (`max_select`): Maximum allowable choices in multi-mode.
  - Pricing Mode: Additive or Fixed price override.
  - Mandatory Requirement: Enforces choice before cart staging.
  - Non-Depleting Flag: Disables recipe deductions for service choices.


25.3 Instant 0ms Client-Side Option Operations
----------------------------------------------

The Options Library workbench utilizes an optimized Alpine client-side store:
  - Adding choices, reordering, updating prices, and linking ingredients execute at 0ms latency without waiting for server round-trips.
  - Changes are committed to the database atomically when clicking Save Template.


25.4 Recipe Ingredient Mapping to Option Choices
------------------------------------------------

Expand any option choice to link specific ingredients from the global inventory catalog (e.g., linking "Cheese Sauce Choice" to "Cheddar Cheese Sauce, 30g"). Products importing this template automatically inherit these recipe deductions.


25.5 Importing, Bi-Directional Syncing & Saving Templates
---------------------------------------------------------

  - Import from Library: On any product's Options tab, click Import from Library to clone the template and its recipe mappings.
  - Sync Group: Click the sync icon on a product option group to pull new choices or ingredient mappings from the matching master template without overwriting custom local prices.
  - Save to Library: Click the save icon on a product option group to export its configuration as a new library master template or update an existing one.


------------------------------------------------------------------------


26. Category Taxonomies & Station Routing
=========================================


26.1 Dual Taxonomies: Product Categories vs. Ingredient Categories
------------------------------------------------------------------

Access via Category Management (/management/categories):
  - Product Categories: Organize menu items on POS terminals and mobile apps. Governs ticket routing.
  - Ingredient Categories: Classify raw materials, perishables, and packaging in stock ledgers.
  - Restricted Access: Restricted exclusively to Super Admins.


26.2 Alphanumeric Reference Identifiers (PRD-CAT- and ING-CAT-)
---------------------------------------------------------------

Categories are assigned standardized reference codes:
  - Product Categories: PRD-CAT-0001, PRD-CAT-0002, etc.
  - Ingredient Categories: ING-CAT-0001, ING-CAT-0002, etc.


26.3 Production Station Routing (Kitchen vs. Barista Slips)
-----------------------------------------------------------

Every Product Category must be assigned a Production Station:
  - Kitchen: Routed to hot food prep line cooks.
  - Barista: Routed to beverage and dessert stations.
When an order is completed, the POS print engine splits items into separate Kitchen Order Slips and Barista Slips according to category assignments.


26.4 Safe Category Lifecycle & Integrity Deletion Guards
--------------------------------------------------------

Deletion Guard: A category cannot be deleted while products or ingredients remain assigned to it. The system displays the blocking item count and requires re-categorizing items before allowing permanent deletion.


------------------------------------------------------------------------


27. Enterprise Stock Control & HQ Commissary Dispatch
=====================================================


27.1 Global Ingredient Catalog Creation & Multi-Tier Conversions
----------------------------------------------------------------

Under Stock Management (/stock):
  - Define global raw materials, base units (g, ml, pcs), and low-stock alert thresholds.
  - Build multi-tier packaging conversions (e.g., 1 Box = 12 Bottles = 6,000 ml) with automatic base unit cost re-indexing.


27.2 Enterprise Inventory Monitoring & Daily 6:00 AM Digest Concurrency Mutex
-----------------------------------------------------------------------------

  - Consolidated overview of company-wide ingredient reserves across all branch stores.
  - Daily 6:00 AM Email Digest: Dispatches an enterprise report detailing low stock, stockouts, and expiring batches across the network. Mutex locking guarantees a single email dispatch per day.
  - Daily 6:00 AM Multi-Channel Alerts: Compiles network-wide deficits and dispatches both an executive email digest and in-app bell alerts to Super Admins, highlighting depleted ingredients and expiring lots with click-to-route shortcuts.
  - Concurrency Mutex: Database-backed locking ensures the automated command executes cleanly once per day without parallel duplication.


27.3 Reviewing, Approving & Dispatching Branch Stock Requests (/stock/orders/admin)
-----------------------------------------------------------------------------------

Super Admins supervise inter-branch replenishment through the HQ Admin console (/stock/orders/admin):
1. Review Incoming Requests: Inspect requesting branch, urgency priority, and requested items.
2. Verify Stock & Adjust Quantities: Check live warehouse balances at the Main Branch; adjust approved quantities if stock is constrained.
3. Logistics Fee Review: Inspect calculated geodesic delivery fees; apply administrative overrides or waivers if appropriate.
4. Click Approve Request: Order moves to Approved.
5. Click Mark as Preparing: Warehouse staff assemble and crate the shipment.
6. Click Dispatch Order: Deducts Main Branch warehouse inventory immediately via FEFO, generates the official dispatch manifest, and sets status to In Transit.
7. Delivery Confirmation: When the satellite branch confirms delivery receipt, the transfer is archived to Transfer History.


27.4 Central Procurement Ingestion (Stock In) at Main Branch
------------------------------------------------------------

Only the designated Main Branch is authorized to execute supplier "Stock In" movements under Stock Adjustment (/stock/adjustment). Creates new batches with supplier batch numbers, unit costs, and expiration dates.


------------------------------------------------------------------------


28. Multi-Branch Network Administration
=======================================


28.1 Branch Onboarding with Automated Codes (MTC-) & PSGC Comboboxes
--------------------------------------------------------------------

Access via Branch Management (/branches):
1. Click Add Branch.
2. Enter Branch Name: The system automatically generates a standardized code prefixed with MTC- (e.g., "SM City Santa Rosa" produces MTC-SMSR).
3. Contact Details: Official branch telephone and email.
4. PSGC Address Entry: Searchable reactive comboboxes for Region, Province, City/Municipality, and Barangay.


28.2 Interactive Leaflet Geo-Pinning for GPS Coordinates
--------------------------------------------------------

Click or drag the pin on the interactive Leaflet map to capture exact latitude and longitude coordinates, driving customer app branch discovery and logistics routing.


28.3 Geodesic Distance Matrix & Delivery Fee Configurations
-----------------------------------------------------------

Stores GPS coordinates to compute geodesic road distances from the Main Branch, driving automated inter-branch delivery fee formulas with configurable base rates, per-kilometer fees, and free-delivery caps.


28.4 Branch Manager Assignment & Conflict Decoupling
----------------------------------------------------

Every branch must be assigned an active Branch Manager. Assigning a user who currently manages another branch triggers the Manager Conflict Resolution Modal, cleanly decoupling them from the prior store within an atomic database transaction.


28.5 Operational Status Toggles & Safe Decommissioning Safeguards
-----------------------------------------------------------------

  - Active / Inactive Status: Toggling to Inactive suspends POS operations and hides the branch from mobile apps while preserving all historical records.
  - Decommissioning Safeguard: Branch deletion is blocked if active staff members are assigned. Staff must be reassigned in User Management before a store can be deleted.


------------------------------------------------------------------------


29. Network-Wide User Management & Security Auditing
====================================================


29.1 Cross-Branch Staff Directory & Role Provisioning
-----------------------------------------------------

Access via User Management (/users):
  - Super Admins can provision, transfer, deactivate, or delete accounts across all roles: Super Admin, Branch Administrator, Cashier, and Delivery Rider.
  - Directory filtering by Role, Branch, Status, and Search keywords.


29.2 Searchable PSGC Address Hierarchy Comboboxes
-------------------------------------------------

Staff profiles use modernized Alpine comboboxes for Region, Province, City, and Barangay, providing instant search filtering and automatic geographic tier cascading.


29.3 Password Resets, Account Suspension & Danger Zone Deletion
---------------------------------------------------------------

  - Administrative Password Resets: Set a new password directly on any user profile.
  - Account Deactivation: Instantly terminates active sessions without erasing sales or audit histories.
  - Danger Zone Deletion: Permanent deletion. Foreign keys on Proof of Delivery records are safely set to null to prevent database integrity errors.


29.4 Security Audit Trails & Failed Attempt Monitoring
------------------------------------------------------

Inspect user login histories, OTP verification attempts, privilege escalations, and account status transitions.


------------------------------------------------------------------------


30. Prescriptive Analytics & Restock Recommendation Engine
==========================================================


30.1 14-Day Weighted Linear Regression Forecast & IQR Outlier Cleansing
-----------------------------------------------------------------------

Under Business Intelligence (/reports, Tab 2  -  Forecasting):
  - IQR Outlier Cleansing: Strips sales spikes outside the $1.5 \times \text{IQR}$ boundary before model training.
  - Exponential Recency Weighting: Gives higher mathematical weight to recent sales days.
  - Day-of-Week Seasonality: Calculates demand multipliers for each day (Monday through Sunday).
  - Damped Trend (0.98 short-term) and 30% rolling mean baseline floor.


30.2 Stockout-Suppressed Demand Multiplier (+30%)
-------------------------------------------------

When an ingredient has zero stock, customer sales figures artificially understate demand. The engine automatically applies a +30% upward correction (`projectedDemand * 1.3`) to prevent repetitive stockouts.


30.3 Waste-Scaled Safety Stock Buffers (5% to 15%)
--------------------------------------------------

Calculates dynamic safety stock buffers scaled inversely to historical spoilage:
$$\text{Safety Stock \%} = \max(0.05, 0.15 - (\text{Waste Rate} \times 0.5))$$
High-spoilage perishables receive smaller buffers (5%) to prevent rot, while shelf-stable items receive up to 15%.


30.4 Urgency Classifications (Critical, High, Medium, Low) & Coverage Days
--------------------------------------------------------------------------

  - Coverage Days: $\text{Current Stock} \div \text{Daily Projected Demand}$.
  - Critical (Red): Current stock is zero (immediate stockout).
  - High (Amber): Covers 3 days or fewer.
  - Medium (Yellow): Covers 4 to 7 days.
  - Low (Green): Stock comfortably covers projected demand plus buffer.


30.5 Network Restock Summary (loadNetworkRestockSummary) & Prescriptive CSV Export
----------------------------------------------------------------------------------

  - Network Restock Summary: When viewing All Locations, Super Admins can click View Network Restock Summary to aggregate ingredient demand across all branches into a single consolidated procurement order for central commissary purchasing.
  - Prescriptive CSV Export: Download a standardized supplier order manifest containing Ingredient, Current Stock, Projected Demand, Safety Stock, Recommended Order Quantity, and Urgency.


------------------------------------------------------------------------


31. System Administration & Platform Configuration (All 8 Tabs)
===============================================================


31.1 Overview & Cascading Branch Override Hierarchy (branch_id)
---------------------------------------------------------------

Access via System Settings (/settings):
  - Super Admins configure settings globally (Default / All Branches) or override settings for any specific store.
  - Branch Scoping Hierarchy: When retrieving configuration, the system checks for a store-specific override (`branch_id = X`). If not found, it seamlessly falls back to the Global Enterprise Default (`branch_id = null`).


31.2 Tab 1  -  General (Business Identity, TIN, Logo, Headquarters PSGC)
------------------------------------------------------------------------

  - Authorized Role: Super Admin only.
  - Business Trading Name, Contact Email, Contact Phone (+63 format), and Official TIN (000-000-000-000).
  - Headquarters Address with reactive PSGC comboboxes and GPS coordinates.
  - Brand Logo Upload (PNG, JPG, WEBP up to 1 MB) for receipt printing.


31.3 Tab 2  -  Receipts (Headers, Station Slips, Review QR Binding, Print Copies)
---------------------------------------------------------------------------------

  - Station Slips: Configurable titles and subtitles for Kitchen Order Slips and Barista Drink Slips.
  - Logo on Receipt & Statutory VAT Calculations (12% VATable sales and VAT amounts).
  - Promotional Footer & Return Policy text.
  - Default Print Copies (1 to 3 copies).
  - Receipt Review QR Code Toggle: Encodes dynamic order-bound review URLs (`?order_ref=...`). Includes live on-screen thermal receipt preview.


31.4 Tab 3  -  Inventory (Thresholds, Expiry Alert Days, Automated 6:00 AM Digest)
----------------------------------------------------------------------------------

  - Low Stock Threshold & Critical Stock Threshold levels.
  - Expiry Warning Alert Window (default: 7 days).
  - Automated Daily 6:00 AM Stock Digest Toggle.


31.5 Tab 4  -  POS Configuration (Rates, Static GCash QR Upload, Merchant Details)
----------------------------------------------------------------------------------

  - Service Charge Rate for Dine-in orders (e.g., 0.10 for 10%).
  - Regular Discount Rate (e.g., 0.10 for 10%).
  - Senior / PWD Statutory Discount Rate (0.20 for 20% VAT-exempt).
  - POS Terminal Header Title.
  - Static GCash QR Image Upload, Merchant Account Name, and Registered Mobile Number.


31.6 Tab 5  -  Reviews (Questionnaire Builder, Mobile Simulator, QR Limits, Device Cooldown)
--------------------------------------------------------------------------------------------

  - Form Title & Subtitle for customer review portal.
  - Questionnaire Builder: Create, reorder, or delete survey questions (Star Rating 1-5 or Open Text, Mandatory or Optional).
  - Interactive Mobile Simulator: Live preview of questionnaire changes.
  - Review Limits per Order: Enforce maximum reviews allowed per receipt (`reviews_max_per_order`, default 1).
  - Device Anti-Spam Tracking & Cooldown Hours: Configure mandatory cooldown duration between review submissions from the same device (e.g., 24 hours).


31.7 Tab 6  -  System (Operating Branch Context Switcher & Module Hiding)
-------------------------------------------------------------------------

  - Operating Branch Context: Assigns Super Admin to a specific branch environment to test or operate POS, KDS, and stock ordering. Select "General Headquarters (No Branch)" to return to executive overview.
  - Hide Operational Modules Toggle: Hides branch-specific operational modules (POS, Orders, KDS) from the sidebar for executive-only workflows.


31.8 Tab 7  -  Thermal Printer (Web Bluetooth Direct Pairing, Hardware Diagnostics)
-----------------------------------------------------------------------------------

  - Master Printing Toggle: Enable or disable thermal printing.
  - Protocol Selection: Web Bluetooth ESC/POS (Chrome/Edge direct) or Wired/USB/Serial.
  - Auto-Cut Paper: Transmits ESC/POS paper cutting commands after each ticket.
  - Diagnostic Test Print: Transmits a standardized hardware diagnostic voucher to verify printer communication.


31.9 Tab 8  -  Consolidated Audit Logs (Orders, Stock, Users)
-------------------------------------------------------------

  - Permanent, unified activity ledger merging Order Transitions, Stock Intake & Wastage Movements, and User Authentication & Privilege events.
  - Indexed with exact timestamps, operator user IDs, and client IP addresses.


------------------------------------------------------------------------


============================================================
  SHARED APPENDICES
============================================================


Appendix A  -  Mobile Ecosystem
===============================


A.1 Customer Delivery Mobile Application
----------------------------------------

The system exposes a secure REST API powering the official customer mobile application:
  - Account Authentication & OTP: Customers register and authenticate using email and mobile number, validated via 6-digit OTP codes. The customer API also supports Google OAuth registration and login (`/api/auth/google-login`, `/api/auth/google-register`).
  - Rate Limiting Safeguards: OTP dispatch and verification endpoints are protected by `throttle:otp-send` and `throttle:otp-verify`.
  - Account Deletion Compliance: A self-service account deletion endpoint (`/api/user/delete`) allows customers to permanently close their accounts.
  - User Addresses Management: Complete multi-address book API (`/api/user/addresses`) allowing customers to create, update, set default, and delete saved delivery addresses with GPS geocoding.
  - Branch Geolocation Discovery: Uses customer GPS coordinates to calculate road distances to active stores, automatically recommending the nearest branch.
  - Menu Browsing & Customizations: Real-time catalog browsing respecting store-specific active/hidden toggles, ingredient stock availability, and option group selection limits (`max_select`).
  - Favorites System: Dedicated customer favorites API (`/api/favorites`, `/api/favorites/toggle`) enabling customers to bookmark preferred menu items for one-tap reordering.
  - Product & Order Reviews: Customers can submit item-level ratings and full order reviews directly through the mobile app (`/api/products/{id}/reviews`, `/api/orders/{id}/reviews`).
  - Order Placement & Live Tracking: Customers place orders for Delivery or Pick-up and receive live status updates (Accepted -> Preparing -> Handed to Rider -> Out for Delivery -> Delivered).


A.2 Rider Delivery Mobile Application
-------------------------------------

The Rider API powers a purpose-built logistics mobile application for store delivery personnel:
  - Real-Time Dispatch Acceptance: When an order is handed to a rider from KDS or Order Management, the dispatch ticket appears on the rider's phone (`/api/rider/orders/{id}/accept`).
  - GPS Navigation & Directions API: Integrates turn-by-turn routing powered by the Directions API (`/api/directions`) to guide riders directly to the customer's delivery address.
  - Real-Time Location Telemetry: Riders broadcast GPS coordinates to the server (`/api/rider/orders/{id}/location`) during transit.
  - Digital Proof of Delivery (POD) Photo: Upon parcel handoff, the rider captures a photo of the delivered food order (`/api/rider/orders/{id}/deliver`). Uploading the POD photo automatically marks the order as Delivered, timestamps the delivery, attaches the image to the store ledger, and closes the delivery lifecycle.


------------------------------------------------------------------------


Appendix B  -  Role-Specific Troubleshooting Matrix
====================================================


| Symptom / Error | Probable Cause | Corrective Action |
|---|---|---|
| Operating Branch Required screen appears on POS or KDS | Super Admin has not selected an active store context | Click "Select Operating Branch" directly on the screen card or use the topbar `BranchContextLabel` dropdown to choose an active store. |
| Cannot select more options in customization modal | Group selection limit (`max_select`) reached | By design, bounded multi-choice option groups disable additional checkboxes when the configured limit (e.g., 2 of 2) is reached. Uncheck an option to select another. |
| Cooldown screen appears when loading customer review page | Device recently submitted a review | Anti-spam device tracking enforces a mandatory cooldown period (e.g., 24 hours). The screen displays the exact time when new feedback can be submitted. |
| Review submission blocked: "Review limit reached for this order" | Receipt order was already reviewed | In System Settings (Tab 5), `reviews_max_per_order` limits reviews to 1 per receipt to prevent review duplication. |
| Satellite branch cannot select "Stock In" in Stock Adjustment | Central commissary rule enforced | Satellite stores cannot execute supplier intake. Request inventory from the Main Branch via Branch Stock Ordering (/stock/orders). |
| Product is grayed out / disabled on POS | Mandatory recipe ingredient depleted | The POS checks live raw material stock in real time. Replenish inventory via Stock Ordering or perform physical reconciliation. |
| GCash payment modal cannot be closed / cart cannot be cleared | GCash payment marked as verified | To safeguard against cashflow discrepancies, verified GCash transactions are locked. Complete the checkout, then execute an authorized Void in Order Management. |
| Multi-slip separation countdown alert appears | Multi-slip printing active | Tear off the first printed receipt slip. Printing of the kitchen or barista slip will resume automatically when the 15-second countdown expires or when clicking Continue. |
| Multi-slip separation countdown alert appears | Multi-slip printing active | Tear off the first printed receipt slip. Printing of the kitchen or barista slip will resume automatically when the 5-second countdown expires or when clicking Continue. |
| Thermal printer not responding | Bluetooth pairing disconnected | Ensure printer is powered on and paired via Web Bluetooth in Chrome/Edge. Run "Test Print Connection" in System Settings (Tab 7). |
| PSGC address combobox not populating | Parent geographic tier not selected | Select Region first, then Province, then City/Municipality, then Barangay to ensure cascading geographic hierarchy loads properly. |
| Cannot delete branch in Branch Management | Registered staff still assigned to branch | Reassign or transfer all cashiers and riders to another branch in User Management before deleting the store. |
| Category cannot be deleted | Products or ingredients assigned | The system displays blocking item counts. Re-categorize all assigned items before attempting permanent deletion. |
| Business Intelligence shows "Insufficient Data" | Fewer than 5 clean sales days recorded | The regression model requires a minimum of 5 clean daily sales points (or 3 monthly points) to establish mathematical baselines. |
| Toast notification displays an amber card instead of red | Intentional operational safeguard | Amber toasts signify business rules, insufficient stock, validation errors, or policy limits rather than system crashes. Accompanied by a harmonic chime. |
| Cannot cancel order in Order Management | Mandatory cancellation reason missing | Order cancellation requires supplying a reason of at least 3 characters in the prompt modal. This logs an audit entry and executes automated reverse FEFO restock. |
| Hovering over collapsed sidebar icons | Dynamic floating tooltip positioning | The system projects a dark, high-contrast tooltip 10px to the right of the icon with zero browser tooltip collision. |
| Clicking notification navigates to an unexpected tab | Smart Routing active | The system inspects live record status to automatically open the active tab (`inbox`, `active`, `history`, or expiry panel) rather than stale views. |


------------------------------------------------------------------------


Appendix C  -  Frequently Asked Questions by Role
=================================================


Cashier & Staff Operations
--------------------------

  Why are some checkboxes disabled in the product customization modal?
  .....................................................................
Option groups can have bounded selection limits (`max_select`). For example, a "Choice of 2 Dips" option group allows selecting up to 2 items. Once 2 checkboxes are checked, all remaining checkboxes in that group are automatically disabled. Unchecking any item re-enables the remaining choices immediately.

  Can I clear the cart or dismiss the payment modal after verifying a GCash payment?
  ...................................................................................
No. To protect against lost funds, the system locks the checkout modal once GCash payment is marked as verified. You must complete the order. If the customer cancels, complete the transaction and request an Administrator to Void the order.

  How does the 1-hour transition window work in Order Management?
  ...............................................................
When an order is completed, voided, or refunded, it remains in the active Delivery Orders or POS Orders tab for exactly 1 hour. This allows staff to reprint receipts or make adjustments without switching tabs. After 1 hour, it moves permanently to Order History.


Branch Administrator Operations
-------------------------------

  Why can't my branch record "Stock In" movements under Stock Adjustment?
  ........................................................................
Only the central Main Branch acts as the commissary warehouse authorized to ingest external vendor shipments. Satellite stores must request inventory from headquarters via Branch Stock Ordering (/stock/orders). This guarantees supply chain traceability and uniform batch costs.

  How does customer review order-binding and device anti-spam work?
  ..................................................................
When customers scan the receipt QR code, the URL contains a direct order reference (`?order_ref=...`). The system verifies the order, marks the review as a "Verified Receipt Order", enforces maximum reviews per receipt (default: 1), and places the customer's device on a 24-hour cooldown using persistent cookies, IP tracking, and client fingerprinting.

  Can I configure custom receipt headers or GCash QR codes just for my branch?
  ...........................................................................
Yes. In System Settings, changes made by a Branch Administrator are saved with your store's `branch_id`. Your store will use your custom telephone number, promotional receipt footer, and static GCash QR code while inheriting global defaults for untouched settings.


Super Admin Enterprise Operations
---------------------------------

  How do I switch between global enterprise overview and operating as a branch?
  ..............................................................................
To operate a store (e.g., test POS or KDS), click the topbar `BranchContextLabel` dropdown or open System Settings (Tab 6 - System) and pick an active branch. To return to company-wide enterprise command, select "General Headquarters (All Locations)".

  What happens if I assign a manager to a branch that already has an administrator?
  .................................................................................
The system displays the Manager Conflict Resolution Modal. Confirming safely decouples the previous manager, resets their store assignment to null, and binds the new administrator within an atomic database transaction.

  What happens to delivery history if a rider account is deleted?
  ...............................................................
The system uses nullable foreign keys on Proof of Delivery records. Deleting a rider profile safely unlinks the rider ID without corrupting order timelines or delivery photo archives.


------------------------------------------------------------------------


Appendix D  -  Glossary & Document Control
==========================================


Glossary of Operational Terms
-----------------------------

| Operational Term | Definition |
|---|---|
| AOV (Average Order Value) | Net sales revenue divided by total completed order count. |
| Bounded Selection Limit (max_select) | The maximum number of option choices a customer is permitted to select within a multi-choice option group. |
| Branch-Scoped Setting | A system configuration record assigned to a specific `branch_id` that overrides the global franchise default. |
| COGS (Cost of Goods Sold) | Direct acquisition cost of raw materials consumed during food preparation, calculated via recipes. |
| Device Cooldown | A security time window enforced via cookies and client fingerprinting preventing repetitive review submissions. |
| FEFO (First-Expired, First-Out) | Inventory deduction algorithm that automatically retires stock batches with the earliest expiration dates first. |
| Immutable Ledger | Tamper-proof database record storing every Sale, Void, Refund, and Stock Adjustment event permanently. |
| IQR (Interquartile Range) | Statistical distribution method used by predictive analytics to cleanse promotional sales anomalies before regression. |
| KDS (Kitchen Display System) | Digital operations board coordinating order queues, elapsed cooking stopwatches, and station slip routing. |
| Operating Branch Context | The active store environment loaded into a session, required for store-specific operations (POS, KDS, Stock Orders). |
| POD (Proof of Delivery) | Digital photograph captured by delivery riders upon parcel handoff, serving as definitive delivery verification. |
| PSGC | Philippine Standard Geographic Code  -  official geographic hierarchy (Region, Province, City, Barangay) used for address data. |
| 4-Tier Toast Notification System | Standardized operational alert classification dividing system feedback into Green Success, Amber Warning, Blue Info, and Red Error with dedicated acoustic cues. |
| Harmonic Web Audio Chime | Browser-synthesized dual-tone sine-wave audio alert generated via native HTML5 AudioContext without external audio files. |
| Reverse FEFO Stock Restoration | Automated return of deducted ingredient lots back to active branch inventory batches when orders are cancelled or voided. |
| Smart Routing | Context-aware notification navigation dynamically determining active view tabs (`inbox`, `active`, `history`, or expiry) based on live record state. |
| Station Routing | Mechanism routing menu items to specific kitchen stations (Kitchen for cooked food vs. Barista for drinks) on KDS and printed slips. |
| Stock Batch | Physical intake of raw materials tracked with its own lot number, unit acquisition cost, and supplier expiration date. |
| Weighted Linear Regression | Predictive algorithm forecasting future demand by assigning exponentially higher mathematical weight to recent sales days. |


------------------------------------------------------------------------


Document Control
================


| Field | Value |
|---|---|
| Document | Mister Takoyaki Centralized Sales and Management System User Manual |
| System | Web Application & Integrated Mobile Delivery Ecosystem |
| Version | 1.5 |
| Version | 1.6 |
| Status | Approved & Released |
| Release Date | September 2026 |
| Prepared By | Development & Architecture Team |
| Approved By | Executive Management |
