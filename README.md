# Student Portal

Student Portal is a PHP and MySQL web application designed for educational institutions to manage students, teachers, academic programmes, courses, enrolment, and leave applications. The system provides separate administrator and student dashboards with role-based access control, ensuring secure and efficient management of academic data.

---

## Features

### Administrator

- Manage student records (create, update, view, delete)
- Manage teacher records
- Manage academic programmes
- Manage courses
- Review, approve, or reject student leave applications
- View administrator dashboard with statistics

### Student

- View personal profile
- Register for available courses
- Drop registered courses
- Submit leave applications with supporting document uploads
- View leave application history and status

### Security

- Password hashing using bcrypt
- CSRF protection
- SQL injection prevention using prepared statements
- Session-based authentication
- Role-based access control
- Server-side validation and sanitization

---

# Technologies Used

- PHP 8
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Docker
- Railway
- SendGrid (SMTP Mail)
- Playwright (End-to-End Testing)

---

# Requirements

Before deploying the application, ensure you have:

- A Railway account
- A GitHub account
- Git (optional, for cloning the repository)
- A SendGrid account (for email functionality)
- A modern web browser

No local PHP, MySQL, Apache, or Docker installation is required.

---

# Deployment

## Option 1: Clone the Repository

```bash
git clone <your-github-repository-url>
cd StudentPortal
```

---

## Option 2: Download the Repository

1. Open the GitHub repository.
2. Click the **Code** button.
3. Select **Download ZIP**.
4. Extract the ZIP archive.

---

## Deploy to Railway

1. Log in to Railway.
2. Click **New Project**.
3. Select **Deploy from GitHub Repo**.
4. Connect your GitHub account.
5. Choose the **StudentPortal** repository.
6. Railway will automatically detect the `Dockerfile`.
7. Wait until the application finishes building.
8. Configure the required environment variables.
9. Redeploy the application if prompted.

---

# Configure the MySQL Database

1. Open your Railway project.
2. Click **New**.
3. Select **Database**.
4. Choose **MySQL**.
5. Railway will automatically create the database.

Configure the following environment variables:

```text
DB_HOST=<Railway MySQL Host>
DB_PORT=<Railway MySQL Port>
DB_DATABASE=<Database Name>
DB_USERNAME=<Database Username>
DB_PASSWORD=<Database Password>
```

---

# Configure SendGrid Email

The application uses **SendGrid SMTP** to send password reset emails.

Create a SendGrid account and generate an API Key.

Add the following environment variables in Railway.

```text
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=<YOUR_SENDGRID_API_KEY>

SMTP_FROM=noreply@example.com
SMTP_FROM_NAME=Student Portal
```

> **Note:** When using SendGrid SMTP, the username must always be `apikey`, while the password is your SendGrid API key.

---

# Access the Application

After deployment, Railway provides a public URL similar to

```
https://studentportal-production.up.railway.app
```

Open the URL in your web browser.

---

# Test Login Credentials

The seeded database includes the following accounts for testing.

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@portal.com | Portal123! |
| Student | alice@school.edu | Portal123! |

> Replace these credentials before deploying the application in a production environment.

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

# Running Playwright Tests

Install the dependencies.

```bash
npm install
```

Run the automated tests.

```bash
npx playwright test
```

---

# Security Features

The application implements the following security measures:

- Passwords are securely hashed using bcrypt.
- Prepared SQL statements prevent SQL injection.
- CSRF tokens protect all forms.
- Session-based authentication.
- Role-based authorization.
- Server-side input validation and sanitization.
- Secure password reset via SendGrid SMTP.

---

# Notes

- Uploaded leave documents are stored in `uploads/leavedoc/`.
- Railway stores uploaded files on the container filesystem. Files may not persist after a redeployment. For production deployments, use cloud storage such as Amazon S3, Azure Blob Storage, or Cloudinary.
- Email functionality requires a valid SendGrid API key.
- The application is configured for deployment using the included `Dockerfile` and `railway.json`.

---

# License

This project was developed for educational and coursework purposes.
