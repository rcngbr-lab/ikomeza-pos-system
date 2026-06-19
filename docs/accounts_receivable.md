# Frontier POS Accounts Receivable

This module controls customer credit sales, payments, statements, aging, collections, and approval records for Frontier Shop.

## Workflow

1. Create or update a customer in `Customers`.
2. Manager/Admin assigns category, credit limit, credit period, risk level, and status in `Credit Control`.
3. POS partial or credit sales require a customer account.
4. Checkout validates account status, branch, credit limit, overdue balance, and approval level.
5. Credit sale posts to:
   - `sales.credit_due`
   - `customer_credit_accounts`
   - `credit_transactions`
   - `customer_ledger_entries`
   - accounting journal through existing sale accounting
6. Receivable payments post through `Credit Control` or `Customers`.
7. Managers print customer statements from `Credit Control`.
8. Collection follow-ups record stage, channel, commitment amount/date, and next follow-up.

## Access Rules

- Admin/Administrator: full access.
- Manager: receivables dashboard, profiles, statements, payments, approvals, collections.
- Cashier: customer list/payment posting only, no global receivables dashboard.
- Other roles: no receivables dashboard unless explicitly granted later.

## Important Tables

- `customer_credit_accounts`
- `credit_transactions`
- `credit_payments`
- `credit_collections`
- `approval_levels`
- `approval_requests`
- `customer_statements`
- `aging_snapshots`
- `bad_debts`

## Local User Recovery

Use this command locally to update a username or password safely:

```bash
php artisan frontier:user-update admin --username=admin --password=YourNewStrongPassword123
```

In production this command requires `--force` and should only be used during a planned admin recovery window.
