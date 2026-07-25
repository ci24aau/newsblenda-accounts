# Newsblenda Accounts

A production-ready WordPress plugin that provides a complete frontend account management system for Newsblenda.

The plugin replaces the default WordPress login experience with a modern frontend portal while allowing administrators to continue using the WordPress dashboard.

---

# Features

## Authentication

- Frontend Registration
- Frontend Login
- Frontend Logout
- Forgot Password
- Password Reset
- Email Verification
- Account Approval
- Pending Approval Screen
- Restricted Account Screen
- Access Denied Screen

---

## Author Dashboard

- Dashboard
- Profile Management
- Notifications
- Account Status
- Profile Completion
- Earnings Overview
- Quick Actions
- Recent Articles

---

## Author Profile

- Personal Information
- Biography
- Contact Information
- Social Links
- Author Information
- Payment Information

---

## Security

- Nonce Verification
- CSRF Protection
- REST Authentication
- Login Rate Limiting
- Input Sanitization
- Data Validation
- Escaped Output
- Secure Password Reset
- Email Verification
- Role-Based Permissions

---

## REST API

Includes REST endpoints for:

- Dashboard
- Profile
- Notifications
- Account
- Logout

---

## Notifications

- Read / Unread Status
- Mark as Read
- Mark All Read
- Delete Notifications
- Action Links
- Notification Statistics

---

## Roles

The plugin creates:

- Newsblenda Author (Pending)
- Newsblenda Author
- Restricted Author
- Newsblenda Editor

Administrators automatically receive all Newsblenda capabilities.

---

# Requirements

- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+

---

# Installation

1. Upload the plugin folder to:

```
wp-content/plugins/newsblenda-accounts
```

2. Activate the plugin.

3. Visit:

```
WordPress Admin → Plugins
```

4. Activate **Newsblenda Accounts**.

---

# Frontend Pages

The plugin provides frontend pages for:

- Login
- Register
- Logout
- Forgot Password
- Reset Password
- Verify Email
- Dashboard
- Profile
- Notifications

---

# Database Tables

The plugin creates:

- wp_nb_notifications
- wp_nb_activity
- wp_nb_email_verification_tokens

---

# Folder Structure

```
newsblenda-accounts/

├── assets/
├── includes/
│   ├── Auth/
│   ├── Core/
│   ├── Dashboard/
│   ├── Helpers/
│   ├── Notifications/
│   ├── Profile/
│   ├── REST/
│   ├── Roles/
│   └── Security/
├── templates/
├── languages/
├── uninstall.php
├── README.md
└── newsblenda-accounts.php
```

---

# Developer Hooks

Actions

- nb_accounts_before_load
- nb_accounts_loaded
- nb_accounts_login_footer
- nb_accounts_register_footer
- nb_accounts_profile_footer
- nb_accounts_dashboard_footer
- nb_accounts_notifications_footer
- nb_accounts_verify_email_footer
- nb_accounts_account_approved_footer
- nb_accounts_account_restricted_footer
- nb_accounts_pending_approval_footer

---

# Coding Standards

The plugin follows:

- WordPress Coding Standards
- Object-Oriented PHP
- PSR-4 Autoloading
- Secure Database Queries
- Strict Types
- Namespaces

---

# Changelog

## Version 1.0.0

Initial production release including:

- Frontend Authentication
- Registration
- Email Verification
- Dashboard
- Profile Management
- Notifications
- REST API
- Security Layer
- Custom Roles
- Modular Architecture

---

# Frequently Asked Questions

### Does this replace wp-login.php?

No.

Administrators can continue using the normal WordPress login.

---

### Can authors access wp-admin?

No.

Authors use the frontend dashboard provided by this plugin.

---

### Is email verification required?

Yes.

Users must verify their email before their account can become active.

---

### Does the plugin support editors?

Yes.

A dedicated Newsblenda Editor role is included.

---

# Support

Plugin Author

Law Blessing

Website

https://newsblenda.com

---

# License

GPL v2 or later

Copyright © Law Blessing.