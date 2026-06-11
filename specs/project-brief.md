# Project Brief: Library Management System

## 1. Project Overview

The Library Management System is a web-based application for managing a library's book catalog, members, and borrowing transactions. The system supports two user roles: Admin and Member. Admin users manage the catalog, review member data, and oversee borrowing activity, while Member users search the catalog and borrow or return books according to library rules.

The application must be implemented using native PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap. No PHP frameworks are in scope for the initial version.

## 2. Project Objectives

- Provide a responsive web interface for library administrators and members.
- Allow secure authentication with role-based access control.
- Enable admins to manage book records through full create, read, update, and delete operations.
- Track borrowing and returning transactions with stock validation.
- Automatically calculate late fines based on the due date and actual return date.
- Allow members to search and filter available books efficiently.

## 3. Target Users

### Admin

Library staff responsible for maintaining the book catalog, monitoring member records, and managing borrowing activity.

### Member

Registered library users who browse the catalog, search for available books, and borrow or return books.

## 4. Core Value

Users can reliably find, borrow, return, and manage books while the system accurately tracks stock availability and late fines.

## 5. Scope

### In Scope

- Admin and Member authentication.
- Role-based access control.
- Admin dashboard.
- Book catalog CRUD.
- Member data viewing for admins.
- Borrow and return transaction handling.
- Book stock availability validation.
- Due date tracking.
- Automatic late fine calculation.
- Member-facing book search and filters.
- Responsive UI using Bootstrap.

### Out of Scope for Initial Version

- Online payment for fines.
- Barcode or QR code scanning.
- Email or SMS notifications.
- Public guest browsing without login.
- Multi-branch library support.
- Advanced analytics and reporting dashboards.
- Integration with external library systems.

## 6. Technology Constraints

| Area | Requirement |
|------|-------------|
| Backend | Native PHP only |
| Database | MySQL |
| Frontend | HTML, CSS, JavaScript |
| UI Framework | Bootstrap |
| Architecture | Server-rendered PHP pages with form-based workflows |
| Framework Restriction | No Laravel, Symfony, CodeIgniter, or other PHP frameworks |

## 7. High-Level Modules

### Authentication Module

Handles login, logout, session management, and role-based page access for Admin and Member users.

### Admin Dashboard Module

Provides admin navigation and management views for books, members, and transactions.

### Book Catalog Module

Stores and manages book details such as title, author, category, ISBN, publication year, and stock quantity.

### Member Catalog Module

Allows members to view, search, filter, and inspect available books.

### Transaction Module

Handles borrowing and returning books, including stock updates, due dates, return dates, and transaction status.

### Fine Calculation Module

Calculates late fees when a book is returned after the due date.

## 8. Key Business Rules

- A user must be authenticated before accessing protected system pages.
- Admin-only pages must not be accessible to Member users.
- A book can only be borrowed if its available stock is greater than zero.
- Borrowing a book decreases available stock by one.
- Returning a book increases available stock by one.
- Each borrow transaction must have a due date.
- A returned book must record the actual return date.
- If the actual return date is later than the due date, the system must calculate a fine.
- If the book is returned on or before the due date, the fine must be zero.

## 9. Success Criteria

- Admin users can manage the full book catalog from a dashboard.
- Admin users can view registered member data.
- Member users can search and filter books by relevant fields.
- The system prevents borrowing when a book is out of stock.
- The system records borrow and return transactions accurately.
- The system calculates late fines automatically and consistently.
- The application remains usable on desktop, tablet, and mobile screen sizes.

## 10. Risks and Assumptions

### Risks

- Incorrect stock updates could cause inaccurate catalog availability.
- Weak session handling could expose admin functionality to unauthorized users.
- Fine calculation rules may need adjustment if library policy changes.
- Native PHP implementation requires disciplined structure to avoid duplicated logic.

### Assumptions

- Users are registered before they borrow books.
- Admins create and manage member accounts, or members can be created through a controlled registration flow.
- Fine amount per late day will be configurable or defined before implementation.
- Each physical book copy is represented through stock quantity rather than individual copy records in the initial version.
