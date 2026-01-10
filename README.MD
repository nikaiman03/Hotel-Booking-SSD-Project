# 🏨 Secure Hotel Booking System

A secure, OWASP-compliant hotel booking web application built with PHP, MySQL, and modern security practices.

## 📋 Project Information

**Course:** Secure Software Development (IKB 21503)  
**Institution:** UniKL MIIT  
**Project Type:** Group Project (Max 4 members)  
**Assessment Weight:** 20% (100 marks)

---

## 🎯 Project Objectives

- Develop a secure web application using approved secure frameworks
- Implement OWASP Top 10 security controls
- Apply OWASP ASVS standards
- Demonstrate injection-free coding practices
- Implement comprehensive security testing

---

## ✨ Features

### Functional Features
- ✅ User Registration & Login with secure authentication
- ✅ Role-Based Access Control (Admin & User roles)
- ✅ Hotel Room Booking System (CRUD operations)
- ✅ User Profile Management
- ✅ Admin Dashboard for user management
- ✅ Audit Log System (tracks all important activities)

### Security Features
- ✅ Password hashing with bcrypt (cost: 12)
- ✅ SQL injection prevention with prepared statements
- ✅ XSS protection with output encoding
- ✅ CSRF token protection on all forms
- ✅ Session security with timeout and secure cookies
- ✅ Input validation (server-side & client-side)
- ✅ Custom error pages (403, 404, 500)
- ✅ Audit logging for security events
- ✅ Failed login attempt tracking

---

## 🛠️ Technology Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache 2.4+
- **Security Framework:** OWASP compliant practices

---

## 📦 Installation Instructions

### Prerequisites
- XAMPP/WAMP/LAMP Stack installed
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Modern web browser

### Step 1: Clone/Download Project
```bash
# If using Git
git clone <your-repo-url>

# Or download ZIP and extract to htdocs folder
```

### Step 2: Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `hotel_booking`
3. Import the SQL file:
   - Click on `hotel_booking` database
   - Go to "Import" tab
   - Choose file: `hotel_booking.sql`
   - Click "Go"

### Step 3: Configure Environment
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` file with your database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=hotel_booking
   DB_USER=root
   DB_PASSWORD=
   APP_ENV=development
   APP_DEBUG=false
   ```

3. Update `config.php` if needed (default settings work for XAMPP)

### Step 4: Set File Permissions
```bash
# For Linux/Mac
chmod 755 -R hotelb/
chmod 777 logs/

# For Windows - right-click logs folder → Properties → Security
# Give "Full Control" to IUSR and IIS_IUSRS
```

### Step 5: Start Server
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open browser and navigate to: `http://localhost/hotelb/`

---

## 👥 Default Accounts

### Admin Account
- **Username:** `admin2`
- **Password:** `admin123456`
- **Role:** Administrator

### User Account
- **Username:** `nik`
- **Password:** `nik12345678`
- **Role:** Regular User

**⚠️ IMPORTANT:** Change these passwords immediately after installation!

---

## 🏗️ Project Structure

```
hotelb/
│
├── index.html              # Landing page
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # Logout handler
├── book_room.php           # Room booking (CRUD)
├── user_profile.php        # User profile management
├── admin_dashboard.php     # Admin panel
│
├── config.php              # Database & app configuration
├── session_security.php    # Session management functions
├── audit_log.php           # Logging functions
│
├── 403.php                 # Access denied page
├── 404.php                 # Page not found
├── 500.php                 # Server error page
│
├── styles.css              # Main stylesheet
├── .htaccess               # Apache security config
├── .env.example            # Environment variables template
├── .gitignore              # Git ignore rules
│
├── logs/                   # Application logs (auto-created)
│   └── php_errors.log
│
└── hotel_booking.sql       # Database schema & data
```

---

## 🔒 Security Implementation

### OWASP Top 10 Coverage

| Risk | Implementation | Status |
|------|----------------|--------|
| A01: Broken Access Control | RBAC, session checks | ✅ |
| A02: Cryptographic Failures | Bcrypt password hashing | ✅ |
| A03: Injection | Prepared statements | ✅ |
| A04: Insecure Design | CSRF protection, secure sessions | ✅ |
| A05: Security Misconfiguration | .htaccess, error handling | ✅ |
| A06: Vulnerable Components | Regular updates | ⚠️ |
| A07: Authentication Failures | Strong passwords, logging | ✅ |
| A08: Software Integrity | Input validation | ✅ |
| A09: Logging Failures | Audit log system | ✅ |
| A10: SSRF | Not applicable | N/A |

### OWASP ASVS Controls

- **V2: Authentication** - Secure login, password policies, session management
- **V3: Session Management** - Secure cookies, timeout, regeneration
- **V4: Access Control** - RBAC implementation
- **V5: Validation** - Input validation, output encoding
- **V7: Error Handling** - Custom error pages, no stack traces
- **V8: Data Protection** - Password hashing, secure storage
- **V9: Communication** - HTTPS ready (requires SSL certificate)

---

## 🧪 Security Testing

### Manual Testing
1. **SQL Injection:** Try `admin' OR '1'='1` in login → Should fail
2. **XSS:** Try `<script>alert('XSS')</script>` → Should be encoded
3. **CSRF:** Remove CSRF token from form → Should be rejected
4. **Session Timeout:** Wait 30 minutes → Should auto logout
5. **Access Control:** Access admin pages as user → Should redirect

### Automated Testing Tools
- **OWASP ZAP:** Dynamic application security testing
- **SonarQube:** Static code analysis
- **Snyk:** Dependency vulnerability scanning

### Test Reports Location
```
docs/
├── manual_testing_checklist.pdf
├── zap_scan_report.pdf
├── sonarqube_analysis.pdf
└── penetration_test_summary.pdf
```

---

## 📸 Screenshots

### User Flow
1. **Homepage** - Landing page with hotel information
2. **Registration** - Secure user registration with validation
3. **Login** - Authentication with CSRF protection
4. **Book Room** - Room selection and booking interface
5. **User Profile** - Profile management page

### Admin Flow
1. **Admin Dashboard** - User management interface
2. **Add User** - Create new users with role assignment
3. **Audit Log** - View system activity logs

*Screenshots should be included in your project report appendix*

---

## 🚀 Deployment Guide

### Production Deployment Checklist

#### 1. Update Configuration
```php
// config.php
define('APP_ENV', 'production');
define('APP_DEBUG', false);
```

#### 2. Enable HTTPS
```apache
# .htaccess - Uncomment these lines
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 3. Secure Session Cookies
```php
// session_security.php
$isSecure = true; // Force HTTPS cookies
```

#### 4. Database Security
- Use strong database passwords
- Limit database user permissions
- Enable SSL for database connections
- Regular backups

#### 5. File Permissions
```bash
chmod 755 hotelb/
chmod 644 *.php
chmod 600 .env
chmod 777 logs/
```

#### 6. Hide Sensitive Files
Already configured in `.htaccess`:
- `.env` file
- `.git` directory
- `logs/` folder
- `*.sql` files

---

## 🐛 Known Issues & Fixes

### Issue 1: Session Not Persisting
**Solution:** Check `php.ini` session settings
```ini
session.save_path = "/tmp"
session.gc_maxlifetime = 1800
```

### Issue 2: CSRF Token Mismatch
**Solution:** Ensure session is started before CSRF generation

### Issue 3: Database Connection Error
**Solution:** Verify credentials in `config.php` and MySQL service is running

---

## 📚 API Documentation

### Session Functions

```php
// Start secure session
startSecureSession();

// Validate CSRF token
validateCSRFToken($token);

// Check if user is authenticated
isAuthenticated();

// Check if user is admin
isAdmin();

// Log user activity
logActivity($user_id, $action, $details);
```

### Database Functions

```php
// All queries use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
```

---

## 🤝 Contributing

This is an academic project. For improvements:

1. Fork the repository
2. Create a feature branch
3. Implement security improvements
4. Test thoroughly
5. Submit pull request with documentation

---

## 📄 License

This project is for educational purposes only.  
© 2026 UniKL MIIT - Secure Software Development Course

---

## 👨‍💻 Team Members

| Name | Student ID | Role |
|------|------------|------|
| [Your Name] | [ID] | Team Lead / Backend Developer |
| [Member 2] | [ID] | Frontend Developer |
| [Member 3] | [ID] | Security Tester |
| [Member 4] | [ID] | Documentation |

---

## 📞 Support

For issues or questions:
- **Email:** [your-email@student.unikl.edu.my]
- **GitHub Issues:** [repository-url]/issues
- **Lecturer:** Mardiana Mahari

---

## 🙏 Acknowledgments

- OWASP Foundation for security guidelines
- UniKL MIIT for the learning opportunity
- Course lecturer for guidance and support

---

## 📅 Project Timeline

- **Week 8:** Project planning and design
- **Week 9-10:** Development and implementation
- **Week 11:** Security testing and fixes
- **Week 12-13:** Documentation and submission

---

## ✅ Submission Checklist

- [ ] All source code files
- [ ] Database SQL file
- [ ] README.md documentation
- [ ] .env.example file
- [ ] Security testing reports
- [ ] Screenshots of working application
- [ ] Video demonstration (5-10 minutes)
- [ ] Technical report (PDF)
- [ ] GitHub repository link

---

**Last Updated:** January 2026  
**Version:** 1.0.0  
**Status:** ✅ Completed & Ready for Submission
