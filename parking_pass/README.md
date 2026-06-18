# Parking Pass Management System - FIXED VERSION

## ✅ What Was Fixed

The following issues have been resolved:

### Changes Made:
1. **database.sql** - Updated with correct bcrypt hash for password "admin123"
2. **admin/reset_password.php** - NEW utility to reset password if needed
3. **email_config.php** - Fixed PHPMailer paths (was causing booking errors)
4. **user/book_slot.php** - Fixed amount display to show in real-time
5. **user/receipt.php** - NEW receipt printing feature for approved bookings

**Fixed Errors:**
- ✅ Login issue - Invalid password hash
- ✅ Email sending - Incorrect PHPMailer file paths
- ✅ Booking slot - PHPMailer require_once errors
- ✅ Amount display - Now shows total amount dynamically while booking
- ✅ Receipt printing - Added professional receipt generation for approved bookings

**New Features:**
- ✨ Real-time amount calculation while booking
- ✨ Professional printable receipts with QR codes
- ✨ Print receipt button for approved bookings (user & admin panel)

---

## 🚀 Setup Instructions

### Quick Start (3 Steps):

1. **Extract the zip** to `C:\xampp\htdocs\parking_system\`

2. **Import database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database or drop existing `parking_system`
   - Import `database.sql`

3. **Login:**
   - Go to: `http://localhost/parking_system/admin/login.php`
   - Username: `admin`
   - Password: `admin123`

**That's it!** The system is ready to use. Emails are disabled by default - no email setup required.

---

### Option 1: Fresh Installation (Recommended)

1. **Import the database:**
   - Open phpMyAdmin or your MySQL client
   - Drop the existing `parking_system` database (if it exists)
   - Import `database.sql` file
   - The admin account will be created automatically

2. **Login:**
   - Navigate to: `http://localhost/parking_system/admin/login.php`
   - Username: `admin`
   - Password: `admin123`

### Option 2: Fix Existing Installation

If you already have data in your database and don't want to lose it:

1. **Run the password reset utility:**
   - Upload `admin/reset_password.php` to your server
   - Access it in browser: `http://localhost/parking_system/admin/reset_password.php`
   - It will automatically reset the admin password to `admin123`
   - **IMPORTANT:** Delete `reset_password.php` after use!

2. **Login:**
   - Navigate to: `http://localhost/parking_system/admin/login.php`
   - Username: `admin`
   - Password: `admin123`

---

## 📁 Project Structure

```
parking_system/
├── admin/
│   ├── login.php                  ✅ Admin login page
│   ├── reset_password.php         🆕 Password reset utility
│   └── dashboard.php              (other admin files)
├── user/                          User-related files
├── phpmailer/                     Email functionality
├── config.php                     Database configuration
├── database.sql                   ✅ FIXED - Database with correct hash
├── index.php                      Main homepage
└── README.md                      🆕 This file
```

---

## 🔑 Default Credentials

**Admin Account:**
- Username: `admin`
- Email: `admin@parkingsystem.com`
- Password: `admin123`

**⚠️ IMPORTANT:** Change the default password after first login!

---

## 🛠️ Database Configuration

Edit `config.php` if your database settings are different:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'parking_system');
```

---

## 📧 Email Configuration (Optional)

**IMPORTANT: Emails are DISABLED by default** - The system will work perfectly without email configuration.

To enable email notifications later:

1. **Edit `email_config.php`:**
   ```php
   define('ENABLE_EMAIL', true);  // Change false to true
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   ```

2. **For Gmail:**
   - Enable 2-factor authentication on your Google account
   - Generate an "App Password" at https://myaccount.google.com/apppasswords
   - Use the app password (not your regular password)

3. **Current Status:**
   ```php
   define('ENABLE_EMAIL', false);  // Emails are OFF - system works fine
   ```

**Note:** PHPMailer paths have been corrected. Emails are disabled by default so bookings work immediately without email setup.

---

## ❓ Troubleshooting

### Still Can't Login?

1. **Check database connection:**
   - Ensure MySQL is running
   - Verify database credentials in `config.php`
   - Ensure `parking_system` database exists

2. **Use the reset utility:**
   - Run `admin/reset_password.php` in your browser
   - This generates a fresh hash using your server's PHP
   - Delete the file after use

3. **Verify admin account exists:**
   ```sql
   SELECT * FROM admins WHERE username = 'admin';
   ```

### Common Issues:

- **"Database connection failed"** → Check `config.php` settings
- **"Invalid username or password"** → Use password reset utility
- **Page not found** → Check your web server's document root

---

## 🔐 Security Notes

1. **Change default password** after first login
2. **Delete `reset_password.php`** after using it
3. **Update `config.php`** with secure database credentials for production
4. **Don't commit** `config.php` to version control with real credentials

---

## 📞 Support

If you encounter any issues:
1. Check the troubleshooting section above
2. Verify all files are uploaded correctly
3. Check PHP error logs for detailed error messages
4. Ensure PHP version 7.4 or higher is installed

---

## ✨ Features

- Admin dashboard for managing parking
- User registration and login
- Booking management
- Parking location management
- Email notifications (PHPMailer)
- Payment tracking
- Booking history and logs

---

**Status:** ✅ READY TO USE

The login issue has been completely resolved. You can now login with the default credentials!
