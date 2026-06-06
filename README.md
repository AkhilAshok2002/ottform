# 🎬 OTT Streaming Platform

A full-stack OTT (Over-The-Top) Streaming Platform developed using PHP, MySQL, JavaScript, HTML, and CSS. 
The platform allows users to browse, search, and stream movies and TV series, manage watch history and favorites, and subscribe to premium plans. 
It also includes a comprehensive admin panel for content and user management.

---

## 📌 Project Overview

This project simulates a modern OTT streaming service where users can:

- Register and login securely
- Browse movies and TV series
- Search content instantly
- Stream videos online
- Manage watch history
- Add content to favorites
- Purchase subscription plans
- Manage personal profiles

Administrators can:

- Upload and manage videos
- Manage users
- Create and manage categories
- Monitor subscriptions
- Generate reports

---

## 🚀 Features

### User Module
- User Registration & Login
- Secure Authentication
- Browse Movies & Series
- Category-wise Filtering
- Search Functionality
- Video Streaming
- Watch History Tracking
- Favorites (My List)
- User Profile Management
- Subscription Plans

### Admin Module
- Admin Dashboard
- Video Upload & Management
- Category Management
- User Management
- Subscription Monitoring
- Reports & Analytics

---

## 🛠️ Technologies Used

### Frontend
- HTML5
- CSS3
- JavaScript
- AJAX

### Backend
- PHP

### Database
- MySQL

### Server
- Apache (XAMPP/WAMP)

---

## 📂 Project Structure

```text
ott-platform/
│
├── admin/
│   ├── dashboard.php
│   ├── upload-video.php
│   ├── manage-videos.php
│   ├── manage-users.php
│   ├── subscriptions.php
│
├── ajax/
│   ├── search.php
│   ├── toggle-favorite.php
│   ├── update-watch-time.php
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── uploads/
│
├── includes/
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│
├── sql/
│   └── database.sql
│
├── index.php
├── movies.php
├── series.php
├── watch.php
├── login.php
├── register.php
├── profile.php
└── subscription.php
```

---

## ⚙️ Installation

### Step 1: Clone Repository

```bash
git clone https://github.com/yourusername/ott-streaming-platform.git
```

### Step 2: Move Project

Copy the project folder to:

```text
xampp/htdocs/
```

### Step 3: Start Services

Start:

- Apache
- MySQL

using XAMPP Control Panel.

### Step 4: Create Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create database:

```sql
ott_platform
```

### Step 5: Import SQL File

Import:

```text
sql/database.sql
```

### Step 6: Configure Database

Update database credentials in:

```php
includes/config.php
```

### Step 7: Run Project

```text
http://localhost/ott-platform
```

---

## 🎯 Key Functionalities

### Authentication System
- User Registration
- Login & Logout
- Session Management

### Video Management
- Upload Videos
- Edit Video Details
- Delete Videos
- Category Assignment

### Streaming Features
- Online Video Playback
- Watch Progress Tracking
- Watch History Management

### Subscription System
- Free Plan
- Premium Plan
- Subscription Management

### Search Engine
- Real-time Content Search
- Category Filtering

---

## 📸 Screens

- Home Page
- Movies Page
- Series Page
- Video Player
- Login Page
- Registration Page
- User Profile
- My List
- Watch History
- Admin Dashboard

---

## 🔒 Security Features

- Password Hashing
- Session Authentication
- Role-Based Access Control
- Protected Admin Routes
- Input Validation

---

## 🔮 Future Enhancements

- Payment Gateway Integration
- Movie Recommendation System
- AI-Based Suggestions
- Mobile App Support
- Live Streaming
- Multi-Language Support
- Email Notifications
- Social Media Login

---

## 💼 Resume Description

Developed a full-stack OTT Streaming Platform that enables users to browse, search, and stream movies and TV series through a subscription-based model. Implemented secure authentication, watch history tracking, favorites management, category filtering, and an admin dashboard for content, user, and subscription management using PHP, MySQL, JavaScript, HTML, and CSS.

---

## 📈 Project Highlights

✔ Full-Stack Web Application

✔ User & Admin Modules

✔ Video Streaming System

✔ Subscription Management

✔ AJAX-Based Search

✔ Responsive UI Design

✔ MySQL Database Integration

✔ Secure Authentication System

---

## 👨‍💻 Author

**Your Name**

GitHub: https://github.com/yourusername

LinkedIn: https://linkedin.com/in/yourprofile
