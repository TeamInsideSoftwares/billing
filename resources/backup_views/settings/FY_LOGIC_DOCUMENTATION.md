# Financial Year (FY) Logic - Current Implementation

## Overview
The Financial Year (FY) system is implemented across multiple components in the billing application. It handles FY date configuration, serial number generation, and automatic reset of serial counters when a new FY begins.

---

## 1. FY Start Date Configuration

### Location
- **View:** `resources/views/settings/index.blade.php` (Personal Info Tab)
- **Database:** `accounts.fy_startdate` (format: `MM-DD`)
- **Controller:** `BillingUiController.php` & `SettingsController.php`

### How It Works
The FY start date is configured in the **Business Info** tab using two dropdowns:
- **Day selector:** 1-31
- **Month selector:** January-December

```php
// In index.blade.php (line ~259)
$currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
$parts = explode('-', $currentFy);
$curMonth = $parts[0] ?? '04';
$curDay = $parts[1] ?? '01';
```

**Default:** April 1st (`04-01`)

### Storage
When the form is submitted, the controller combines day and month into `MM-DD` format:
```php
// In SettingsController.php (line ~157)
$validated['fy_startdate'] = $validated['fy_month'] . '-' . $validated['fy_day'];
```

**Example:** Day `15`, Month `April` → `04-15`

---

## 2. Financial Year Records

### Location
- **Tab:** Financial Year (separate tab in settings)
- **Route:** `financial-year.update` (POST), `financial-year.default` (PUT)
- **View:** `resources/views/settings/index.blade.php` (line ~286-380)

### How It Works
Users can create FY records by selecting:
- **Start Year:** (Current Year - 1) to (Current Year + 1)
- **End Year:** Current Year to (Current Year + 2)

The system then creates a FY record like `2025-2026`.

### Features
- Multiple FY records can be stored
- One FY can be marked as **Default**
- Non-default FYs can have their default status updated

### Database Structure
```
financial_years table:
- fy_id (PK)
- financial_year (e.g., "2025-2026")
- default (boolean)
- created_at, updated_at
```

---

## 3. Serial Number Reset on FY Change

### Location
- **View:** `resources/views/settings/serial-config.blade.php`
- **Database:** 
  - `account_billing_details.reset_on_fy` (boolean)
  - `account_quotation_details.reset_on_fy` (boolean)
- **Controllers:** 
  - `SettingsController.php` (billing)
  - `BillingUiController.php` (quotation)

### How It Works
In the **Financial Year** tab, under **Serial Number Configuration**, there are two sections:

#### A. Invoice Serial Configuration
- Checkbox: **"Reset Invoice Serial Number when new FY starts"**
- Stored in: `account_billing_details.reset_on_fy`

#### B. Quotation Serial Configuration
- Checkbox: **"Reset Quotation Serial Number when new FY starts"**
- Stored in: `account_quotation_details.reset_on_fy`

### Checkbox Logic
```php
// In serial-config.blade.php (line ~88)
<input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1" 
    {{ ($editingBillingDetail->reset_on_fy ?? false) ? 'checked' : '' }}>
```

### Controller Validation
```php
// In SettingsController.php
$validated = $request->validate([
    'reset_on_fy' => 'boolean',
]);

// Normalize boolean
$validated['reset_on_fy'] = $request->has('reset_on_fy');
```

---

## 4. Serial Number Configuration System

### Location
- **View:** `resources/views/settings/serial-config.blade.php`
- **Included in:** `resources/views/settings/index.blade.php` (line ~382)

### Serial Type Options
```php
$serialOptions = [
    'manual text' => 'Fixed Value',
    'date' => 'Date',
    'year' => 'Year',
    'month-year' => 'Month-Year',
    'date-month' => 'Date-Month',
    'auto increment' => 'Auto Increment',
    'auto generate' => 'Auto Generate',
];
```

### Serial Structure
Each serial (Invoice & Quotation) has 3 parts:
1. **Prefix** (First Value)
2. **Number** (Second Value) - typically auto-increment
3. **Suffix** (Third Value)

Each part has:
- `_type`: Type of value (manual text, date, year, auto increment, etc.)
- `_value`: The actual value or starting number
- `_length`: Length (for auto-generate type)
- `_separator`: Separator before this part (`none`, `-`, `/`)

### Reset on FY Checkbox
Both Invoice and Quotation serial configs include the `reset_on_fy` checkbox:

```php
// Invoice (line ~88)
<input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1" 
    {{ ($editingBillingDetail->reset_on_fy ?? false) ? 'checked' : '' }}>

// Quotation (line ~164)
<input type="checkbox" name="reset_on_fy" id="quotation-reset-on-fy" value="1" 
    {{ ($editingQuotationDetail->reset_on_fy ?? false) ? 'checked' : '' }}>
```

---

## 5. FY Reset Logic in Controllers (UPDATED)

### Helper Method: `resetSerialNumbersIfRequired($accountid)`

Both `SettingsController` and `BillingUiController` have this private method that:

1. **Checks FY Start Date:** Reads `accounts.fy_startdate` (format: `MM-DD`)
2. **Validates Full Year FY:** Only resets if FY starts on 1st of month
   ```php
   $fyParts = explode('-', $account->fy_startdate);
   $fyDay = $fyParts[1] ?? '01';
   $isFullYearFy = ($fyDay == '01'); // True for Apr 1, Jan 1, etc.
   ```
3. **Resets Serial Values to NULL:**
   - `prefix_value` → `null`
   - `number_value` → `null`
   - `suffix_value` → `null`
   - Only if `reset_on_fy` is enabled
   - Affects both billing and quotation serials

### When Reset is Triggered

#### Scenario A: New FY Created or Updated (`financialYearUpdate`)
```php
// Check previous default FY
$previousDefault = FinancialYear::where('accountid', $account->accountid)
    ->where('default', true)
    ->first();

// Create/update FY and set as default
$fy = FinancialYear::updateOrCreate(...);

// Check if default actually changed
$defaultChanged = !$previousDefault || $previousDefault->fy_id !== $fy->fy_id;

if ($defaultChanged) {
    $this->resetSerialNumbersIfRequired($accountid);
}
```

#### Scenario B: Existing FY Set as Default (`financialYearSetDefault`)
```php
// Check previous default FY
$previousDefault = FinancialYear::where('accountid', $accountid)
    ->where('default', true)
    ->first();

// Set new default
FinancialYear::where('accountid', $accountid)->update(['default' => false]);
$financialYear->update(['default' => true]);

// Reset if default changed
if (!$previousDefault || $previousDefault->fy_id !== $financialYear->fy_id) {
    $this->resetSerialNumbersIfRequired($accountid);
}
```

### What Gets Reset
When reset is triggered (and `reset_on_fy` is checked):
- **Database values are set to NULL** (not empty string, not 0)
- This **blanks** the serial configuration fields
- System will regenerate them based on new FY settings

### Example Flow
```
User Action: Set FY 2026-2027 as default (was 2025-2026)
         ↓
Check: Was there a previous default? YES (2025-2026)
         ↓
Check: Is it different? YES (2025-2026 → 2026-2027)
         ↓
Check: FY starts on 1st? (fy_startdate = "04-01")
         ↓
Check: Is day = "01"? YES → Full year FY
         ↓
Reset: prefix_value, number_value, suffix_value → NULL
         ↓
Show: Success message
```

---

## 6. Database Schema

### `accounts` table
```php
$table->string('fy_startdate', 10)->nullable()->after('timezone'); // format: 'MM-DD'
```

### `account_billing_details` table
```php
$table->boolean('reset_on_fy')->default(false)->after('auto_increment_start');
// Also includes: prefix_type, prefix_value, prefix_separator, 
//                 number_type, number_value, number_separator,
//                 suffix_type, suffix_value, suffix_length,
//                 auto_increment_start
```

### `account_quotation_details` table
```php
$table->boolean('reset_on_fy')->default(false)->after('auto_increment_start');
// Same structure as billing details
```

### Migration Files
1. `2026_03_31_074057_restructure_financial_year_logic.php` - Added `fy_startdate`
2. `2026_04_02_100000_add_serial_config_to_account_billing_and_quotation_details_tables.php` - Added `reset_on_fy`
3. `2026_04_03_120000_fix_serial_number_configuration.php` - Enhanced `reset_on_fy` with comments

---

## 7. UI/UX Flow

### Tab Structure (index.blade.php)
```
1. Business Info (Personal) - Contains FY Start Date (Day/Month)
2. Financial Year - Contains FY records + Serial Number Configuration
3. Configuration Keys
4. Billing Details
5. Quotation Details
6. Terms & Conditions
7. Taxes
```

### Serial Configuration UI (serial-config.blade.php)
```
┌─────────────────────────┬─────────────────────────┐
│   Invoice Serial        │  Quotation Serial       │
│                         │                         │
│  Preview: INV-1-8596    │  Preview: QUO-1-8512    │
│                         │                         │
│  [Prefix Config]        │  [Prefix Config]        │
│  [Number Config]        │  [Number Config]        │
│  [Suffix Config]        │  [Suffix Config]        │
│                         │                         │
│  ☑ Reset when new FY    │  ☑ Reset when new FY    │
│    starts               │    starts               │
│                         │                         │
│  [Save Invoice Serial]  │  [Save Quotation Serial]│
└─────────────────────────┴─────────────────────────┘
```

---

## 8. Key Relationships

```
Account (fy_startdate: MM-DD)
    ↓
Financial Years (multiple records, one default)
    ↓
Serial Configuration (reset_on_fy: boolean)
    ├── Invoice Serial (account_billing_details)
    └── Quotation Serial (account_quotation_details)
```

---

## 9. Current Limitations / Notes

1. **FY Start Date vs FY Records are Separate:**
   - `accounts.fy_startdate` stores the day/month when FY starts (e.g., `04-01`)
   - `financial_years` table stores actual FY year ranges (e.g., `2025-2026`)
   - These two are NOT automatically linked

2. **Reset Logic is Triggered on Default Change:**
   - Reset happens when default FY changes (new or existing)
   - Only if `reset_on_fy` checkbox is enabled
   - Only if FY starts on 1st of month (full year FY)

3. **Serial Values Set to NULL:**
   - Reset sets `prefix_value`, `number_value`, `suffix_value` to `null`
   - This blanks the configuration, not the entire record
   - System will regenerate serials based on new FY

4. **Full Year FY Check:**
   - System checks if `fy_startdate` day is `01`
   - Example: `04-01` (April 1) → Full year FY → Reset enabled
   - Example: `04-15` (April 15) → Not full year → No reset

---

## 10. Example Use Case

**Scenario:** Company FY starts on April 1st

1. **Set FY Start Date:**
   - Go to Business Info tab
   - Set Day: `1`, Month: `April`
   - Saves as `fy_startdate = "04-01"`

2. **Create FY Records:**
   - Go to Financial Year tab
   - Create `2025-2026`, set as default
   - Later create `2026-2027`

3. **Configure Serial Reset:**
   - In Serial Configuration section
   - Check "Reset Invoice Serial Number when new FY starts"
   - When `2026-2027` is set as default, serial values reset to NULL

4. **Result:**
   - FY 2025-2026: INV-1-0001, INV-1-0002, ... INV-1-0500
   - Switch to FY 2026-2027 → Serial values blanked
   - FY 2026-2027: INV-1-0001, INV-1-0002, ... (starts fresh!)

---

## Files Referenced

| File | Purpose |
|------|---------|
| `resources/views/settings/index.blade.php` | Main settings UI with tabs |
| `resources/views/settings/serial-config.blade.php` | Serial configuration partial |
| `app/Http/Controllers/SettingsController.php` | Settings CRUD operations + reset logic |
| `app/Http/Controllers/BillingUiController.php` | Billing/Quotation updates + reset logic |
| `app/Models/Account.php` | Account model (fy_startdate) |
| `app/Models/AccountBillingDetail.php` | Billing detail model (reset_on_fy) |
| Database migrations | Schema definitions |

---

**Last Updated:** April 7, 2026
