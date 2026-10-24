# 📚 LibraryHub — Library Management System

LibraryHub is a web-based Library Management System developed to simplify book management and borrowing operations. It provides separate functionality for students and administrators to manage books, track borrowing activity, and monitor book availability.

🌐 **Live Website:** https://libraryhub.infinityfreeapp.com/

💻 **Source Code:** https://github.com/chinmayi-k/LibraryHub

## ✨ Features

### 📖 Book Catalogue
- Browse available books.
- Search by title, author, category, or ISBN.
- Filter books by category.
- View book availability.

### 👤 User Management
- Student registration and login.
- Session-based authentication.
- Separate student and administrator access.

### 🛠️ Admin Dashboard
- Add, edit, and delete books.
- View library statistics.
- Issue books to students.
- Manage issued books and process returns.

### 📚 Borrowing Management
- View borrowed-book history.
- Track issue dates and due dates.
- Identify overdue books.
- Record book returns and update availability.

## 🧰 Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Development Environment:** XAMPP
- **Version Control:** Git and GitHub
- **Hosting:** InfinityFree

## ⚙️ Local Installation

### Prerequisites
- XAMPP
- A web browser
- Git (optional)

### Setup Instructions

1. Clone the repository:

   ```bash
   git clone https://github.com/chinmayi-k/LibraryHub.git
   ```

2. Copy the project folder into XAMPP's `htdocs` directory.

3. Start Apache and MySQL from the XAMPP Control Panel.

4. Create a MySQL database in phpMyAdmin.

5. Import your database SQL file if you have one.

6. Create or configure `db.php` with your local database connection details. Refer to `db.example.php` if available.

7. Open the project in your browser:

   `http://localhost/LibraryHub/`

   If your folder is named `booknest`, use:

   `http://localhost/booknest/`

## 🔐 Security Notes

- Never publish database passwords or private credentials.
- Keep `db.php`, `.env`, and database backup files out of public repositories.
- Configure database credentials separately for local development and online hosting.
- Use strong passwords for administrator accounts.

## 🎯 Project Objective

The main objective of LibraryHub is to provide a simple, user-friendly system for managing library books, student borrowing records, and returns digitally.

## 👩‍💻 Author

**Chinmayi K**

Developed as an academic project.
