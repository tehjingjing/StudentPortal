# Student Portal
Student Portal is a comprehensive PHP and MySQL web application designed for educational institutions to manage student records, teachers, academic programmes, courses, enrolment, and leave applications. The system features separate administrator and student dashboards with role-based access control, ensuring secure and efficient management of academic data.

## Key Features

### Administrator Capabilities
- **Student Management**: Create, edit, view, and delete student profiles with detailed academic and personal information
- **Teacher Management**: Manage teacher records including faculty assignments and employment status
- **Program Management**: Create and manage academic programmes with faculty associations and credit requirements
- **Course Management**: Add, edit, and remove courses with program associations, credit hours, and duration
- **Leave Approval**: Review, approve, or reject student leave applications with supporting document verification

### Student Capabilities
- **Profile Viewing**: Access personal profile information including academic details and guardian contacts
- **Course Registration**: Register for available courses within their faculty and academic term
- **Course Dropping**: Drop enrolled courses with confirmation
- **Leave Applications**: Submit leave requests with optional supporting document uploads (PDF, JPG, PNG - max 5MB)
- **Application History**: View status of submitted leave applications (pending, approved, rejected)

### Security Features
- Secure password hashing using bcrypt
- CSRF (Cross-Site Request Forgery) protection on all forms
- SQL injection prevention via prepared statements
- Session-based authentication with role verification
- Server-side input validation and sanitization

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- A web browser

No local PHP or MySQL installation is required.

## Setup and run

1. Clone the GitHub repository and open the project folder.

   ```bash
   git clone <your-repository-url>
   cd StudentPortal
   ```

2. Start the application containers.

   ```bash
   docker compose up --build
   ```

3. Wait until Docker reports that the web and database services are running, then open:

   | Service | Address |
   | --- | --- |
   | Student Portal | http://localhost:8080 |
   | phpMyAdmin | http://localhost:8081 |

4. Stop the application when finished.

   ```bash
   docker compose down
   ```

### Resetting the sample database

The SQL seed script only runs when the database volume is created for the first
time. To remove all local data and recreate the sample data, run:

```bash
docker compose down -v
docker compose up --build
```

> Warning: this permanently deletes the local database volume.

## Test login credentials

After starting with a fresh database, use either account below. The password is
intentionally included for coursework testing only; replace seeded credentials
in any real deployment.

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@portal.com` | `Portal123!` |
| Student | `alice@school.edu` | `Portal123!` |

## phpMyAdmin credentials

Use the following values at http://localhost:8081:

| Field | Value |
| --- | --- |
| Server | `db` |
| Username | `appuser` |
| Password | `apppass` |

## Project structure

```text
StudentPortal/
├── auth/
│   ├── login.php              # User login with CSRF protection and lockout
│   ├── register.php           # Student self-registration
│   ├── logout.php             # Session cleanup and logout
│   ├── forgot_password.php    # Password reset request
│   └── reset_password.php     # Password reset with token validation
├── config/
│   └── db.php                 # Database connection configuration
├── courses/
│   ├── index.php              # Course listing with search
│   ├── create.php             # Add new course form
│   ├── edit.php               # Edit existing course
│   └── delete.php             # Delete course handler
├── dashboard/
│   ├── admin_dashboard.php    # Administrator dashboard with statistics
│   └── student_dashboard.php  # Student dashboard with profile
├── db/
│   └── init.sql               # Database schema and sample data
├── enrolment/
│   ├── my_course.php          # Student course registration page
│   ├── register_course.php    # Course registration handler
│   └── drop_course.php        # Course drop handler
├── includes/
│   ├── csrf_helper.php        # CSRF token generation and validation
│   ├── require_login.php      # Authentication guard
│   ├── require_admin.php      # Admin role guard
│   ├── student_sidebar.php    # Student navigation sidebar
│   └── admin_sidebar.php      # Admin navigation sidebar
├── leaves/
│   ├── index.php              # Admin leave management page
│   ├── apply.php              # Student leave application form
│   ├── submit_leave.php       # Leave submission handler
│   └── process_leave.php     # Leave approval/rejection handler
├── logo/
│   └── Spacecollege.png       # Application logo
├── programs/
│   ├── index.php              # Program listing with search
│   ├── create.php             # Add new program form
│   ├── edit.php               # Edit existing program
│   └── delete.php             # Delete program handler
├── public/
│   ├── css/
│   │   └── styles.css         # Main stylesheet
│   └── js/
│       └── sidebar.js         # Sidebar toggle functionality
├── students/
│   ├── index.php              # Student listing with search
│   ├── create.php             # Add new student form
│   ├── edit.php               # Edit existing student
│   ├── delete.php             # Delete student handler
│   └── profile.php            # Student profile view
├── teachers/
│   ├── index.php              # Teacher listing with search
│   ├── create.php             # Add new teacher form
│   ├── edit.php               # Edit existing teacher
│   └── delete.php             # Delete teacher handler
├── uploads/
│   └── leavedoc/              # Leave evidence file uploads
├── tests/
│   └── student_portal.spec.js # Playwright end-to-end tests
├── .gitignore                 # Git ignore rules
├── docker-compose.yml         # Docker Compose configuration
├── index.php                  # Application entry point
├── package.json               # Node.js dependencies (Playwright)
├── playwright.config.js       # Playwright test configuration
└── README.md                  # Project documentation
```

## Notes

- The database host in `config/db.php` is `db`, which is the Docker Compose
  service name. It will not work when opening PHP files directly without Docker.
- Uploaded leave evidence is stored locally in `uploads/leavedoc/`.
- For Render deployment, file uploads require persistent storage configuration.
