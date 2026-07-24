# Student Portal

Student Portal is a PHP and MySQL web application designed for educational institutions to manage students, teachers, academic programmes, courses, enrolment, and leave applications. The system provides separate administrator and student dashboards with role-based access control, ensuring secure and efficient management of academic data.

---

## Features

### Administrator

- Manage student records (create, update, view, delete)
- Manage teacher records
- Manage academic programmes
- Manage courses
- Review and approve/reject student leave applications
- View dashboard statistics

### Student

- View personal profile
- Register for available courses
- Drop registered courses
- Submit leave applications with supporting documents
- View leave application history and status

### Security

- Password hashing using **bcrypt**
- CSRF protection on all forms
- SQL injection prevention using prepared statements
- Session-based authentication
- Role-based access control
- Server-side input validation and sanitization

---

## Technologies Used

- PHP 8
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Docker
- Railway
- Playwright (End-to-End Testing)

---

## Requirements

Before deploying the application, ensure you have:

- A Railway account
- A GitHub account
- Git (optional, for cloning the repository)
- A modern web browser

No local PHP, MySQL, Apache, or Docker installation is required for deployment.

---

# Deployment

## Option 1: Clone the Repository

```bash
git clone <your-github-repository-url>
cd StudentPortal
```

---

## Option 2: Download the Repository

1. Open the repository on GitHub.
2. Click **Code**.
3. Select **Download ZIP**.
4. Extract the downloaded ZIP file.

---

## Deploy to Railway

1. Log in to your Railway account.
2. Click **New Project**.
3. Select **Deploy from GitHub Repo**.
4. Connect your GitHub account if prompted.
5. Select the **StudentPortal** repository.
6. Railway will automatically detect the `Dockerfile`.
7. Wait for Railway to build and deploy the application.
8. Add the required environment variables under the **Variables** tab.
9. Redeploy the project if necessary.

---

## Configure the Database

Create a MySQL database in Railway:

1. Inside your Railway project, click **New**.
2. Select **Database**.
3. Choose **MySQL**.
4. Railway will automatically generate the database credentials.

Configure the following environment variables:

```text
DB_HOST=<Railway MySQL Host>
DB_PORT=<Railway MySQL Port>
DB_DATABASE=<Database Name>
DB_USERNAME=<Database Username>
DB_PASSWORD=<Database Password>
```

If your application uses additional variables (such as `APP_ENV` or `APP_URL`), configure them in the Railway **Variables** tab.

---

## Access the Application

Once deployment is complete, Railway will provide a public URL similar to:

```
https://studentportal-production.up.railway.app
```

Open the URL in your browser to access the application.

---

# Test Login Credentials

The database is seeded with the following accounts for coursework testing.

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@portal.com | Portal123! |
| Student | alice@school.edu | Portal123! |

> **Note:** These credentials are intended for testing only. Replace them before deploying the application in a production environment.

---

# Project Structure

```text
StudentPortal/
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── forgot_password.php
│   └── reset_password.php
│
├── config/
│   └── db.php
│
├── courses/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── dashboard/
│   ├── admin_dashboard.php
│   └── student_dashboard.php
│
├── db/
│   └── init.sql
│
├── enrolment/
│   ├── my_course.php
│   ├── register_course.php
│   └── drop_course.php
│
├── includes/
│   ├── csrf_helper.php
│   ├── require_login.php
│   ├── require_admin.php
│   ├── student_sidebar.php
│   └── admin_sidebar.php
│
├── leaves/
│   ├── index.php
│   ├── apply.php
│   ├── submit_leave.php
│   └── process_leave.php
│
├── programs/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── public/
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   └── sidebar.js
│   └── logo/
│       └── Spacecollege.png
│
├── students/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   ├── delete.php
│   └── profile.php
│
├── teachers/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
│
├── uploads/
│   └── leavedoc/
│
├── tests/
│   └── student_portal.spec.js
│
├── .gitignore
├── Dockerfile
├── railway.json
├── package.json
├── playwright.config.js
├── index.php
└── README.md
```

---

# Security Features

The application implements several security best practices:

- Passwords are securely hashed using bcrypt.
- SQL queries use prepared statements to prevent SQL injection.
- CSRF tokens protect all forms.
- User sessions are securely managed.
- Role-based authorization restricts access to administrator functions.
- User input is validated and sanitized on the server.

---

# Running Tests

This project includes Playwright end-to-end tests.

Install dependencies:

```bash
npm install
```

Run the tests:

```bash
npx playwright test
```

---

# Notes

- Leave application documents are uploaded to `uploads/leavedoc/`.
- On Railway, uploaded files are stored on the application's filesystem and may not persist after a redeployment. For production use, consider storing uploaded files in a cloud storage service such as Amazon S3 or Cloudinary.
- The project is configured for deployment using **Railway** with the included `Dockerfile` and `railway.json`.

---

# License

This project was developed for educational and coursework purposes.
