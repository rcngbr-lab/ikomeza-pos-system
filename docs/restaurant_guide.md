# Restaurant And Bar Guide

## Unified POS

Waiters sell food and drinks from one POS cart. Products keep their department internally:

- Kitchen items create KOT tickets.
- Bar items create BOT tickets.
- Customer receives one receipt.
- Reports separate Kitchen and Bar revenue.

## Tickets

Ticket statuses:

- PENDING
- PREPARING
- ACCEPTED
- READY
- SERVED
- CANCELLED

Cancelled tickets require notes and are audited.

## Tables

Restaurant tables are branch-scoped. Table transfer/merge foundation exists in the database, but full UI workflows still need pilot refinement before claiming advanced restaurant operations.

