# Attendance Management System - Setup Guide (Simplified AI Edition)

This guide helps you set up the Attendance Management System. The system now uses **Client-Side AI**, meaning **NO Python** or XAMPP is required for face verification!

## Prerequisites

1.  **Install PHP Manually**:
    *   **Download**: Go to [windows.php.net/download](https://windows.php.net/download/) and download the **VS16 x64 Thread Safe** zip.
    *   **Extract**: Unzip to `C:\php`.
    *   **Configure**:
        *   Rename `php.ini-development` to `php.ini`.
        *   Uncomment (remove `;`) these lines in `php.ini`:
            *   `extension_dir = "ext"`
            *   `extension=pdo_sqlite`
            *   `extension=sqlite3`
            *   `extension=mbstring`
            *   `extension=openssl`
    *   **Add to System Path**: Add `C:\php` to your Windows Environment Variables Path. Type `php -v` in a new terminal to verify.

2.  **Internet Connection**:
    *   **Important**: An active internet connection is required to load the AI models (Face Detection/Recognition) from the CDN when marking attendance.

## Installation & Running

1.  **Prepare the Database**:
    *   Open terminal in the project folder and run: `php -S localhost:8000` (or use `start_server.bat`).
    *   Open browser to: `http://localhost:8000/setup_database.php`
    *   This creates the database with these defaults:
        *   **Admin**: `admin` / **Password**: `admin123`

2.  **Using the App**:
    *   **Dashboard**: `http://localhost:8000`
    *   **Admin Tools**: Use the dashboard to manage batches, students, and teachers.
    *   **Database Viewer**: Use `http://localhost:8000/db_viewer.php` to see all raw data in the tables.

## Project Structure (Cleaned)

*   `database.sqlite`: Your data storage.
*   `mark_attendance.php`: Handles student face verification (Client-side AI).
*   `db_viewer.php`: Tool for viewing database tables.
*   `uploads/`: Folder where student profile photos are stored.
*   `students/`: Folder where captured attendance photos are kept.

## Troubleshooting

*   **"Failed to load AI models"**: Check your internet connection. Ad-blockers might also block the AI weights from loading.
*   **"Driver not found"**: Ensure `extension=pdo_sqlite` is enabled in `php.ini`.
*   **Face Matching issues**: Ensure the profile photo in `uploads/` is clear and matches the student's face.

