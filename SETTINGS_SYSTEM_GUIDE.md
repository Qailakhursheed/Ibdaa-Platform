# ⚙️ Unified Settings System Upgrade

This update introduces a centralized, role-based settings system for all users (Managers, Technical, Trainers, and Students).

## 🚀 Features

### 1. Unified Interface (`Manager/settings.php`)
- **Adaptive UI:** Automatically shows/hides tabs based on user role.
- **Profile Management:** Update name, email, phone, bio, and profile picture.
- **Security:** Change password with current password verification.
- **Platform Settings (Manager Only):** Manage site name, description, contact info, and SMTP settings.

### 2. Robust Backend (`Manager/api/settings_api.php`)
- **Security:** Role-based access control for sensitive actions.
- **Validation:** Checks for email uniqueness and password matching.
- **File Uploads:** Secure profile picture upload handling.

### 3. Integration
- **Manager Dashboard:** Updated sidebar link to point to the new system.
- **Student Dashboard:** Added a "Settings" link to the header.

## 🛠️ Installation

1. **Database Update:**
   Run the `SETTINGS_SYSTEM_UPDATE.sql` script to create the `platform_settings` table and add necessary columns (`bio`, `profile_picture`) to the `users` table.

2. **Permissions:**
   Ensure the `uploads/avatars` directory exists and is writable. The API attempts to create it, but manual verification is recommended.

## 📂 Files
- `Manager/settings.php`: The main frontend file.
- `Manager/api/settings_api.php`: The backend logic.
- `SETTINGS_SYSTEM_UPDATE.sql`: Database schema changes.
