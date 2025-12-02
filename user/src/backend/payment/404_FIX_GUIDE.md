# 404 Error Fix - Registration Return Page

## Problem
When registering, you're getting a 404 error because `return.php` is using **absolute paths** for redirects, which don't match your directory structure.

## Root Cause
In `return.php`, the redirect paths are set as:
```php
$redirect = "/user/src/ui/register.php";  // ❌ This causes 404
$redirect = "/user/src/ui/login.php";     // ❌ This causes 404
```

These absolute paths assume your app is at the root of the domain, but it's actually in `/qr/user/src/`.

## Solution

### Quick Fix - Update Redirect Paths in return.php

Find and replace these lines in `C:\xampp\htdocs\qr\user\src\backend\payment\return.php`:

**Line 260:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/register.php";

// NEW (works):
$redirect = "../../ui/register.php";
```

**Line 308:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/login.php";

// NEW (works):
$redirect = "../../ui/login.php";
```

**Line 312:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/register.php";

// NEW (works):
$redirect = "../../ui/register.php";
```

**Line 316:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/register.php";

// NEW (works):
$redirect = "../../ui/register.php";
```

**Line 321:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/register.php";

// NEW (works):
$redirect = "../../ui/register.php";
```

**Line 327:**
```php
// OLD (causes 404):
$redirect = "/user/src/ui/register.php";

// NEW (works):
$redirect = "../../ui/register.php";
```

## Additional Fix - Update GST Calculation

While fixing the redirects, also update the GST calculation to match the GST-inclusive method:

**Lines 114-125:**
```php
// OLD (incorrect GST):
$cgst = $amount * 0.09;
$sgst = $amount * 0.09;
$igst = 0.00;
$gst_total = $cgst + $sgst + $igst;
$total_amount = $amount + $gst_total;

$invoice_number = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);

$sqlInvoice = "INSERT INTO user_invoice ...";
$stmtInvoice = $conn->prepare($sqlInvoice);
$stmtInvoice->bind_param('isdddddssss', $user_id, $invoice_number, $amount, $cgst, $sgst, $igst, $gst_total, $total_amount, $payment_id, $now, $now);

// NEW (GST-inclusive):
$total_amount = $amount;
$base_amount = round($total_amount / 1.18, 2);
$cgst = round(($base_amount * 9.0) / 100, 2);
$sgst = round(($base_amount * 9.0) / 100, 2);
$igst = 0.00;
$gst_total = $cgst + $sgst + $igst;

// Adjust for rounding
$calculated_total = $base_amount + $gst_total;
if ($calculated_total != $total_amount) {
    $difference = $total_amount - $calculated_total;
    $base_amount = round($base_amount + $difference, 2);
}

$invoice_number = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);

$sqlInvoice = "INSERT INTO user_invoice ...";
$stmtInvoice = $conn->prepare($sqlInvoice);
$stmtInvoice->bind_param('isdddddssss', $user_id, $invoice_number, $base_amount, $cgst, $sgst, $igst, $gst_total, $total_amount, $payment_id, $now, $now);
```

## Why This Happens

**Current file location:**
```
C:\xampp\htdocs\qr\user\src\backend\payment\return.php
```

**Relative path to register.php:**
```
../../ui/register.php
```

**Breakdown:**
- `../` = go up to `backend/`
- `../` = go up to `src/`
- `ui/register.php` = go into `ui/` folder

## Test After Fix

1. Save the changes to `return.php`
2. Try registering again
3. After payment, you should be redirected properly to either:
   - Login page (on success)
   - Register page (on failure)

## Summary

✅ Replace all `/user/src/ui/...` paths with `../../ui/...`  
✅ Update GST calculation to GST-inclusive method  
✅ Test registration flow

This will fix the 404 error!
