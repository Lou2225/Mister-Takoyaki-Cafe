Menu Management Module
----------------------
### [DONE] Branch-Specific Pricing
- Allow different prices for the same item at different locations (e.g., Airport vs. Mall).
- **Value**: Revenue Optimization

### [DONE] Availability Forecast
- Show how many individual servings of an item can be made with current stock levels (e.g., "15 servings left").
- **Value**: Operational Clarity

Stock Management Module
-----------------------
### Critical Missing Features

#### [NEW] Branch-Specific Minimum Stock Thresholds
- `minimum_stock` is currently global for all ingredients.
- High-traffic branches might need more stock than low-traffic branches.
- **Value**: Prevents stock-outs in busy locations while avoiding overstock in others.

#### [NEW] Advanced Ingredient Analytics
- **Ingredient Usage Tracking**: Visibility into how much of each ingredient a product used vs. expected.
- **Unit Cost History Tracking**: Analysis of cost inflation trends and supplier cost comparison.
- **Value**: Profit margin optimization and root-cause waste analysis.

#### [NEW] Damaged/Recalled Stock Tracking
- Distinguish between spoiled items, damaged packaging, product recalls, and customer returns.
- **Value**: Better waste attribution (Kitchen error vs. Supplier issue).

#### [NEW] Supplier Performance Dashboard
- Metrics: Average delivery time, price trends, quality/return rates.
- **Value**: Data-driven supplier evaluation and negotiation.

POS Module
----------
### Next Priority (Phase 2 & 3)

#### 1. Cash Drawer Management
- Opening balance, cash drops, and closing balance (X/Z results).
- **Impact**: Financial accountability and theft prevention.

#### 3. Split Payments & Tip Handling
- Allow paying with 2+ methods (e.g., 50% Cash, 50% GCash).
- % or fixed amount tips before/after payment.
- **Impact**: Flexible customer experience.

#### 4. Delivery Ordered Management
- Driver assignment, customer address tracking, and delivery status updates.
- **Impact**: Expands revenue channels.

#### 5. Advanced Item Customization
- **Modifier Quantities**: "Extra extra spicy" vs "spicy".
- **Combo Bundles**: Save frequent item combinations (e.g., "Family Meal A").

Advanced Features (Future Consideration)
----------------------------------------
- **Customer Database**: Phone number lookup, order history, and loyalty points.
- **Call-ahead Orders**: Reservations with estimated wait times.
- **Batch Operations**: Merge tables, split checks for large groups.
- **Dark Mode**: Optimized interface for late-night shifts.

---
*Roadmap updated based on implemented KDS, Expiry tracking, BI analytics, and Multi-user support.*
