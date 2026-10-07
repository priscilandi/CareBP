# CareBP Backend

The `backend/` directory contains the PHP files responsible for server-side logic, database access, API functionality, and administrative tools.

## Admin Files

The `backend/admin/` directory contains pages intended for CareBP administrators. These pages should eventually be protected with authentication and restricted from public users.

### `index.php`
Admin landing page and dashboard.

**Used for:**
- Providing links to the main admin tools
- Displaying basic administrative information
- Serving as the starting point for administrators

### `facilities.php`
Manages CareBP facility and screening-location records.

**Used for:**
- Viewing facility records
- Adding or editing locations
- Updating facility information such as address, ZIP code, phone number, and availability

### `recommendations.php`
Manages blood pressure guidance and recommendation content.

**Used for:**
- Viewing recommendations stored in the database
- Adding or editing recommendation text
- Associating guidance with blood pressure categories or location types

### `approvals.php`
Handles review and approval of pending changes.

**Used for:**
- Reviewing submitted updates
- Approving or rejecting changes before they are published
- Supporting a controlled update process

### `audit-log.php`
Tracks administrative activity.

**Used for:**
- Recording changes made by administrators
- Reviewing previous administrative actions
- Providing accountability for database updates

## Structure

```text
backend/
└── admin/
    ├── index.php
    ├── facilities.php
    ├── recommendations.php
    ├── approvals.php
    └── audit-log.php