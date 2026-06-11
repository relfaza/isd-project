# Requirements: Library Management System

## 1. Requirement Summary

This document defines the initial functional and non-functional requirements for a web-based Library Management System built with native PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap.

## 2. Actors and Roles

| Actor | Description | Primary Capabilities |
|-------|-------------|----------------------|
| Admin | Library staff user | Manage books, view members, oversee transactions |
| Member | Registered library user | Search books, borrow books, return books, view own transaction status |

## 3. Functional Requirements

### Authentication and Authorization

- [ ] **AUTH-01**: User can log in with valid credentials.
- [ ] **AUTH-02**: User can log out and end the active session.
- [ ] **AUTH-03**: System assigns each authenticated user exactly one role: Admin or Member.
- [ ] **AUTH-04**: System restricts Admin pages to authenticated Admin users only.
- [ ] **AUTH-05**: System restricts Member pages to authenticated Member users only.
- [ ] **AUTH-06**: System redirects unauthenticated users away from protected pages.

### Admin Dashboard

- [ ] **ADMN-01**: Admin can access a dashboard after successful login.
- [ ] **ADMN-02**: Admin can view a summary of total books, available books, registered members, active borrowings, and overdue transactions.
- [ ] **ADMN-03**: Admin can navigate from the dashboard to book management, member data, and transaction views.

### Book Catalog Management

- [ ] **BOOK-01**: Admin can create a new book record.
- [ ] **BOOK-02**: Admin can view the list of book records.
- [ ] **BOOK-03**: Admin can view the details of a single book.
- [ ] **BOOK-04**: Admin can update an existing book record.
- [ ] **BOOK-05**: Admin can delete a book record when deletion does not break transaction history.
- [ ] **BOOK-06**: Book records include title, author, category, ISBN, publication year, description, and stock quantity.
- [ ] **BOOK-07**: System validates required book fields before saving a book record.
- [ ] **BOOK-08**: System prevents stock quantity from being saved as a negative value.

### Member Data Management

- [ ] **MEMB-01**: Admin can view the list of registered members.
- [ ] **MEMB-02**: Admin can view member details.
- [ ] **MEMB-03**: Admin can view a member's borrowing history.

### Member Catalog Search and Filter

- [ ] **SRCH-01**: Member can view the book catalog after login.
- [ ] **SRCH-02**: Member can search books by title.
- [ ] **SRCH-03**: Member can search books by author.
- [ ] **SRCH-04**: Member can filter books by category.
- [ ] **SRCH-05**: Member can filter books by availability.
- [ ] **SRCH-06**: System clearly shows whether each book is available or out of stock.

### Borrowing Transactions

- [ ] **BRRW-01**: Member can borrow an available book.
- [ ] **BRRW-02**: System prevents borrowing when the selected book has zero available stock.
- [ ] **BRRW-03**: System records the borrow date when a book is borrowed.
- [ ] **BRRW-04**: System assigns a due date when a book is borrowed.
- [ ] **BRRW-05**: System decreases the book's available stock by one after a successful borrow transaction.
- [ ] **BRRW-06**: System marks an active borrow transaction as borrowed until the book is returned.

### Return Transactions

- [ ] **RTRN-01**: Member can return a borrowed book.
- [ ] **RTRN-02**: System records the actual return date when a book is returned.
- [ ] **RTRN-03**: System increases the book's available stock by one after a successful return transaction.
- [ ] **RTRN-04**: System marks the transaction as returned after a successful return.
- [ ] **RTRN-05**: System prevents returning the same transaction more than once.

### Late Fine Calculation

- [ ] **FINE-01**: System compares the actual return date with the due date when a book is returned.
- [ ] **FINE-02**: System calculates zero fine when the book is returned on or before the due date.
- [ ] **FINE-03**: System calculates a late fine when the actual return date is later than the due date.
- [ ] **FINE-04**: System calculates the late fine using the number of late days multiplied by the configured fine rate per day.
- [ ] **FINE-05**: System stores the calculated fine amount in the transaction record.
- [ ] **FINE-06**: Admin can view fines associated with member transactions.

## 4. Non-Functional Requirements

### Technology

- [ ] **TECH-01**: The backend must use native PHP.
- [ ] **TECH-02**: The database must use MySQL.
- [ ] **TECH-03**: The frontend must use HTML, CSS, JavaScript, and Bootstrap.
- [ ] **TECH-04**: The system must not use a PHP framework.

### Security

- [ ] **SECR-01**: Passwords must be stored using secure password hashing.
- [ ] **SECR-02**: Login forms must validate user input before authentication.
- [ ] **SECR-03**: Database access must use prepared statements to reduce SQL injection risk.
- [ ] **SECR-04**: Protected pages must verify session state and role before rendering.

### Usability

- [ ] **USAB-01**: The UI must be responsive across desktop, tablet, and mobile screen sizes.
- [ ] **USAB-02**: Forms must show clear validation errors.
- [ ] **USAB-03**: Admin and Member navigation must expose only role-appropriate actions.

### Reliability

- [ ] **RELY-01**: Borrow and return operations must update transaction and stock data consistently.
- [ ] **RELY-02**: The system must not allow stock quantity to become negative.
- [ ] **RELY-03**: The system must preserve transaction history even if a book is no longer available for borrowing.

## 5. Data Requirements

### Users

Stores Admin and Member account data.

Suggested fields:

- user_id
- full_name
- email
- password_hash
- role
- status
- created_at

### Books

Stores catalog data.

Suggested fields:

- book_id
- title
- author
- category
- isbn
- publication_year
- description
- stock_quantity
- created_at
- updated_at

### Transactions

Stores borrowing and return activity.

Suggested fields:

- transaction_id
- user_id
- book_id
- borrow_date
- due_date
- return_date
- status
- fine_amount
- created_at
- updated_at

### Fine Settings

Stores the fine policy.

Suggested fields:

- setting_id
- fine_rate_per_day
- updated_at

## 6. Business Rules

- [ ] **RULE-01**: Only Admin users can create, update, or delete books.
- [ ] **RULE-02**: Only Admin users can view all member data.
- [ ] **RULE-03**: Members can search and view catalog data.
- [ ] **RULE-04**: Members can only borrow books with available stock.
- [ ] **RULE-05**: Borrowing a book decreases stock by one.
- [ ] **RULE-06**: Returning a book increases stock by one.
- [ ] **RULE-07**: A returned transaction cannot be returned again.
- [ ] **RULE-08**: Late fine equals late days multiplied by fine rate per day.
- [ ] **RULE-09**: Fine is zero when late days are zero or less.

## 7. Initial Acceptance Criteria

- [ ] Admin can log in and access the Admin dashboard.
- [ ] Member can log in and access the Member catalog.
- [ ] Admin can create, view, update, and delete book records.
- [ ] Admin can view registered member data.
- [ ] Member can search and filter books.
- [ ] Member cannot borrow an out-of-stock book.
- [ ] Successful borrowing creates a transaction and reduces stock.
- [ ] Successful returning updates the transaction and restores stock.
- [ ] Late returns automatically generate a fine.
- [ ] On-time returns generate no fine.

## 8. Open Questions

- What is the fine rate per late day?
- How many days is the default borrowing period?
- Should members self-register, or should admins create member accounts?
- Should a member be allowed to borrow multiple copies of the same book at the same time?
- Should there be a maximum number of active borrowed books per member?
- Should admins be able to manually waive or adjust fines?
