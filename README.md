# 📊 Smart Attendance Management System (Client-Side AI)

A modern, web-based Attendance Management System featuring **Client-Side AI** face recognition. No heavy server-side Python setups or external API costs are required—face detection and verification run directly in the student's browser!

---

## 🌟 Key Features

### 👤 Admin Panel
* **User Management**: Add, update, and manage student and teacher accounts.
* **Batch & Subject Control**: Organize courses, batches, and assign subjects to teachers.
* **Database Viewer**: Built-in raw SQLite viewer for easy debugging and data auditing.

### 👨‍🏫 Teacher Dashboard
* **Session Creation**: Start temporary attendance sessions for specific batches and periods.
* **Manual Overrides**: Mark or adjust attendance records manually for any student.
* **Notes Sharing**: Upload and manage study material links for student batches.
* **Reports**: View and download attendance sheets.

### 🎓 Student Portal
* **AI Face Verification**: Check-in during active sessions by scanning their face via webcam (AI models load automatically from CDN).
* **Location Verification**: Checks student GPS coordinates to ensure they are present in the classroom.
* **Student Dashboard**: View personal attendance percentages, notifications, and download class notes.

---

## 🛠️ Tech Stack

* **Frontend**: HTML5, Vanilla CSS3 (Modern, Responsive Dashboard Design), JavaScript (ES6+)
* **AI Engine**: Client-side face-api.js (loaded via CDN)
* **Backend**: PHP 8.x
* **Database**: SQLite3 / PDO

---

## 💻 Local Setup Instructions

### Prerequisites
1. **PHP**: Ensure you have PHP (8.0 or newer) installed on your system.
2. **Extensions**: Enable `pdo_sqlite` and `sqlite3` in your `php.ini` configuration.

### Getting Started
1. **Clone the repository**:
   ```bash
   git clone https://github.com/sahal-ismail-k-k/AttendanceManagemenSystem.git
   cd AttendanceManagemenSystem
   ```
2. **Start the local server**:
   * Double-click `start_server.bat` (Windows), or run:
     ```bash
     php -S localhost:8000
     ```
3. **Initialize the Database**:
   * Visit `http://localhost:8000/setup_database.php` in your web browser. This creates the SQLite database structure.
4. **Log in**:
   * Go to `http://localhost:8000`
   * **Default Admin Username**: `admin`
   * **Default Admin Password**: `admin123`

---

## 🌐 Deploying to the Web (Render)

This repository includes a `Dockerfile` and is pre-configured to deploy easily to **[Render](https://render.com/)** for free:

1. Create a free account on **Render** (sign in with GitHub).
2. Create a new **Web Service** and connect this repository.
3. Select the **Docker** runtime.
4. Choose the **Free** tier plan ($0/month).
5. Click **Deploy Web Service**.
6. Once deployed, run the setup script once at `https://<your-render-app-url>/setup_database.php` to initialize the database tables.
