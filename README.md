# IKOMEZA POS

Professional point of sale and business operations platform for bars, restaurants, shops, supermarkets, and retail businesses.

## Core Modules

- Executive dashboard with daily, weekly, monthly, and yearly revenue analytics
- POS cashier terminal with barcode search, category filtering, cart controls, receipts, and shift-aware checkout
- Payment support for Cash, MOMO, Airtel Money, VISA, Mastercard, and Bank Transfer
- Inventory management with stock-in, damaged stock, automatic sale deduction, low-stock alerts, valuation, and movement history
- Sales, refund, and receipt workflows with stock restoration on refunds
- Shift management with opening cash, expected cash, payment breakdown, shortage/overage, history, and printable reports
- User, role, permission, and audit-control foundation
- Responsive Tailwind UI for desktop, tablet, and mobile use
- SQLite/local-first operation with Laravel and Electron desktop packaging scripts

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000`.

## Desktop Mode

```bash
npm run electron-dev
```

## Production Build

```bash
npm run build
php artisan view:cache
php artisan config:cache
```

## Operational Notes

- Cashiers must open a shift before accessing the POS terminal.
- Stock is deducted automatically during checkout and restored during refunds.
- Inventory stock-in and damage actions write both stock ledger and stock movement records.
- Admin and manager access is handled through role checks; cashier dashboards and reports are scoped to the signed-in cashier.
