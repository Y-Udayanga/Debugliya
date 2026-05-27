<p align="center">
  <img src="edit_debug/logoo.jpg" alt="Debuglia Logo" width="120" style="border-radius: 50%;" />
</p>

<h1 align="center">🐛 Debuglia</h1>

<p align="center">
  <strong>A community-driven forum for developers to debug, discuss, and grow together.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/CSS3-Styling-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
  <img src="https://img.shields.io/badge/Bootstrap_Icons-Icons-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap Icons" />
  <img src="https://img.shields.io/badge/Chart.js-Analytics-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white" alt="Chart.js" />
</p>

---

## 📖 Overview

**Debuglia** is a dynamic web-based forum application inspired by platforms like StackOverflow and social media sites, designed to empower developers, IT professionals, and tech enthusiasts. Its primary purpose is to streamline software debugging by fostering collaboration and providing accessible tools and resources.

The goal is to create an intuitive, community-driven platform where users can share knowledge, solve technical challenges, and engage in meaningful discussions. Built by a team of 10 developers, Debuglia supports programming students and professionals in enhancing their debugging skills through interactive and user-friendly features.

### 🎯 Target Audience

| Audience | Description |
|----------|-------------|
| 🎓 **Programming Students** | Students looking to learn debugging techniques and engage with peers |
| 🚀 **Newbie Developers** | Junior developers starting their careers and seeking guidance |
| 💼 **IT Professionals** | Experienced practitioners solving complex technical issues and mentoring others |

---

## ✨ Features

### 🏗️ Core Layout & Navigation
- **Adaptive Navigation Bar** — Seamless access to Home, Profile, About, Forum, Resources, and theme customization settings
- **Left Sidebar** — User profile card, quick-access menu (Explore, Notifications, Trending Topics, Bookmarks, Analytics, Settings)
- **Right Sidebar** — Trending topics with search, community cards with "Join" buttons
- **Footer** — Quick links, social media icons (Twitter, GitHub, LinkedIn), contact info, and search bar

### 📝 Social Feeds & Interaction
- **Post Creation** — Rich post composer with category selection, image attachments (multiple), and emoji picker
- **Social Feed** — Chronological feed displaying posts with author info, timestamps, and category tags
- **Likes** — Toggle-based like system with real-time count updates
- **Comments** — Threaded commenting system with nested replies and comment likes
- **Bookmarks** — Save posts for later reading
- **Share** — Share posts with the community
- **Post Deletion** — Users can delete their own posts

### 👤 User Profile Page
- **Interactive Profile** — Dark visual theme with animated transitions and glowing circular profile picture
- **Profile Details** — Username, biography, phone number, email, location, and skills display
- **Social Links** — LinkedIn, GitHub, and Twitter profile integration
- **Edit Interface** — Modal-based profile editing with photo upload, bio, location, phone, skills, and social URLs
- **User Posts Grid** — View all posts by the user with full interaction capabilities

### 📊 Analytics Dashboard
- **Summary Cards** — Total posts, likes, and comments at a glance
- **Activity Chart** — Post activity visualization over the last 30 days (powered by Chart.js)
- **Category Breakdown** — Posts distribution across categories
- **Refresh Data** — Real-time analytics refresh

### 🔔 Notifications
- **Real-time Notifications** — Alerts for likes, comments, and follows
- **Read/Unread Status** — Track notification status
- **Actor Tracking** — See who interacted with your content

### 🔖 Bookmarks
- **Save Posts** — Bookmark posts for quick access later
- **Dedicated Page** — Browse all bookmarked posts in one place

### 🔥 Trending Topics
- **Hashtag Discovery** — Browse trending hashtags (#Technology, #Programming, #WebDevelopment, #AI, #CloudComputing)
- **Search** — Search within trending topics
- **Categories** — 10 curated categories for organized discussions

### 🌐 Explore
- **Content Discovery** — Explore posts and communities beyond your feed

### 🏠 Home / Landing Page
- **Hero Section** — Animated particle canvas background with typewriter effect welcome message
- **Community Grid** — Visual cards for each category with tilt effects
- **Featured Developer** — Spotlight on a community member
- **Recent Discussions** — Preview of the latest forum posts
- **Newsletter** — Email subscription form for updates

### ⚙️ Settings
- **Account Settings** — Update username, email, password, and profile photo
- **Privacy Controls** — Toggle profile and post visibility (public/private)
- **Notification Preferences** — Email and push notification toggles
- **Account Deletion** — Permanently delete account

### 🎨 Theme Customization
- **Font Size** — 5 adjustable levels (small to large)
- **Accent Colors** — 5 color palettes to choose from
- **Background Modes** — Light, Dim, and Lights Out (dark mode)
- **Theme Toggle** — Quick dark/light mode switch in the navbar

### 🔐 Authentication & Security
- **User Registration** — Username, email, and password with hashed storage (`password_hash`)
- **Login** — Username or email-based authentication with `password_verify`
- **Password Reset** — Forgot password flow with email token-based reset
- **CSRF Protection** — Token-based protection on all forms
- **Session Management** — Secure PHP sessions with auto-redirect for unauthenticated users
- **XSS Prevention** — `htmlspecialchars` escaping on all user-generated output
- **Prepared Statements** — PDO parameterized queries to prevent SQL injection

---

## 🗄️ Database Schema

The application uses a **MySQL** database (`debuglia`) with the following tables:

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│      users       │     │      posts       │     │   categories     │
├──────────────────┤     ├──────────────────┤     ├──────────────────┤
│ id (PK)          │◄────│ user_id (FK)     │     │ id (PK)          │
│ username (UQ)    │     │ id (PK)          │────►│ name             │
│ email (UQ)       │     │ content          │     └──────────────────┘
│ password         │     │ category_id (FK) │
│ profile_photo    │     │ created_at       │     ┌──────────────────┐
│ bio              │     └──────────────────┘     │   post_images    │
│ location         │                              ├──────────────────┤
│ phone            │     ┌──────────────────┐     │ id (PK)          │
│ skills           │     │      likes       │     │ post_id (FK)     │
│ linkedin_url     │     ├──────────────────┤     │ image            │
│ github_url       │     │ id (PK)          │     └──────────────────┘
│ twitter_url      │     │ user_id (FK)     │
│ created_at       │     │ post_id (FK)     │     ┌──────────────────┐
└──────────────────┘     │ created_at       │     │    bookmarks     │
                         └──────────────────┘     ├──────────────────┤
┌──────────────────┐                              │ user_id (FK, PK) │
│    comments      │     ┌──────────────────┐     │ post_id (FK, PK) │
├──────────────────┤     │  comment_likes   │     │ created_at       │
│ id (PK)          │     ├──────────────────┤     └──────────────────┘
│ post_id (FK)     │     │ id (PK)          │
│ user_id (FK)     │     │ user_id (FK)     │     ┌──────────────────┐
│ content          │     │ comment_id (FK)  │     │ password_resets  │
│ parent_id (FK)   │     │ created_at       │     ├──────────────────┤
│ created_at       │     └──────────────────┘     │ id (PK)          │
└──────────────────┘                              │ email (FK)       │
                         ┌──────────────────┐     │ token            │
                         │  notifications   │     │ expires_at       │
                         ├──────────────────┤     └──────────────────┘
                         │ id (PK)          │
                         │ user_id (FK)     │     ┌──────────────────┐
                         │ actor_id (FK)    │     │      views       │
                         │ type (ENUM)      │     ├──────────────────┤
                         │ post_id (FK)     │     │ id (PK)          │
                         │ comment_id (FK)  │     │ user_id (FK)     │
                         │ content          │     │ page             │
                         │ created_at       │     │ view_count       │
                         │ is_read          │     │ last_viewed      │
                         └──────────────────┘     └──────────────────┘
```

**10 Tables:** `users`, `posts`, `categories`, `likes`, `comments`, `comment_likes`, `bookmarks`, `notifications`, `password_resets`, `post_images`, `views`

---

## 📁 Project Structure

```
debuglia/
├── edit_debug/                    # Main application directory
│   ├── index.php                  # 🏠 Forum feed (main page)
│   ├── login.php                  # 🔑 User login
│   ├── register.php               # 📝 User registration
│   ├── logout.php                 # 🚪 Session logout
│   ├── db_connect.php             # 🗄️ Database connection (PDO)
│   ├── forgot_password.php        # 🔐 Password reset request
│   ├── reset_password.php         # 🔐 Password reset handler
│   ├── send_email.php             # 📧 Email sending utility
│   │
│   ├── post.php                   # 📤 Create new post
│   ├── post_display.php           # 📄 Single post display
│   ├── fetch_posts.php            # 📥 Fetch posts (AJAX)
│   ├── posts_by_category.php      # 🏷️ Filter posts by category
│   ├── delete_post.php            # 🗑️ Delete post
│   ├── like.php                   # ❤️ Like/unlike post
│   ├── comment.php                # 💬 Add comment
│   ├── comments.php               # 💬 Fetch comments
│   ├── fetch_notifications.php    # 🔔 Fetch notifications (AJAX)
│   ├── get_profile_photo.php      # 📷 Get user profile photo
│   │
│   ├── style.css                  # 🎨 Global stylesheet
│   ├── style_login.css            # 🎨 Login/Register styles
│   ├── style_create_post.css      # 🎨 Post creation modal styles
│   ├── script.js                  # ⚡ Main JavaScript (interactions)
│   ├── script_login.js            # ⚡ Login page scripts
│   ├── logoo.jpg                  # 🖼️ Application logo
│   │
│   ├── home/                      # Landing page
│   │   ├── home.php               #   Hero, communities, featured dev
│   │   ├── home.css               #   Landing page styles
│   │   └── home.js                #   Particle animations, typewriter
│   │
│   ├── profile/                   # User profile
│   │   ├── profile.php            #   Profile page with posts
│   │   ├── profile.css            #   Profile styles
│   │   ├── profile.js             #   Profile interactions
│   │   └── update_profile.php     #   Profile update handler
│   │
│   ├── about/                     # About page
│   │   ├── about.php              #   Team info, mission, contact
│   │   ├── about.css              #   About page styles
│   │   └── about.js               #   About page scripts
│   │
│   ├── resources/                 # Developer resources
│   │   ├── resources.php          #   Tutorials, tools, APIs
│   │   ├── resources.css          #   Resources styles
│   │   └── resources.js           #   Resources scripts
│   │
│   ├── notification/              # Notifications
│   │   ├── notification.php       #   Notification page
│   │   ├── notification.css       #   Notification styles
│   │   ├── notification.js        #   Notification scripts
│   │   └── create_notification.php #  Create notification handler
│   │
│   ├── bookmark/                  # Bookmarks
│   │   ├── bookmark.php           #   Bookmarks page
│   │   ├── bookmark.css           #   Bookmark styles
│   │   ├── bookmark.js            #   Bookmark scripts
│   │   └── bookmark_action.php    #   Bookmark toggle handler
│   │
│   ├── analytics/                 # Analytics dashboard
│   │   ├── analytics.php          #   Dashboard page
│   │   ├── analytics.css          #   Dashboard styles
│   │   ├── analytics.js           #   Chart.js visualizations
│   │   ├── analytics_connect.php  #   Analytics DB connection
│   │   └── analytics_data.php     #   Analytics data API
│   │
│   ├── trending_topic/            # Trending topics
│   │   ├── trending_topic.php     #   Trending topics page
│   │   ├── trending_topic.css     #   Trending styles
│   │   └── trending_topic.js      #   Trending interactions
│   │
│   ├── explora/                   # Explore / Discovery
│   │   ├── explora.php            #   Explore page
│   │   ├── explora.css            #   Explore styles
│   │   └── explora.js             #   Explore scripts
│   │
│   ├── setting/                   # Settings
│   │   ├── setting.php            #   Settings page (tabs)
│   │   ├── settings.css           #   Settings styles
│   │   ├── settings.js            #   Settings interactions
│   │   ├── update_settings.php    #   Settings update handler
│   │   └── delete_account.php     #   Account deletion handler
│   │
│   ├── uploads/                   # User-uploaded files
│   ├── logs/                      # Error logs
│   └── Website SQL code/          # Database setup
│       └── sql codes.txt          #   Full SQL schema
│
└── README.md                      # This file
```

---

## 🚀 Getting Started

### Prerequisites

| Requirement | Version |
|-------------|---------|
| **PHP** | 8.0 or higher |
| **MySQL** | 5.7 or higher |
| **Web Server** | Apache (XAMPP / WAMP / LAMP recommended) |
| **Browser** | Any modern browser (Chrome, Firefox, Edge, Safari) |

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Y-Udayanga/Debugliya.git
   cd Debugliya
   ```

2. **Set up the database**
   - Open phpMyAdmin or your MySQL client
   - Execute the SQL script located at:
     ```
     edit_debug/Website SQL code/sql codes.txt
     ```
   - This will create the `debuglia` database with all required tables and seed data for categories

3. **Configure the database connection**
   - Open `edit_debug/db_connect.php`
   - Update the credentials if they differ from your setup:
     ```php
     $host = 'localhost';
     $dbname = 'debuglia';
     $username = 'root';
     $password = '';
     ```

4. **Start the web server**
   - Place the project in your web server's document root (e.g., `htdocs` for XAMPP)
   - Start Apache and MySQL services

5. **Access the application**
   ```
   http://localhost/Debugliya/edit_debug/login.php
   ```

6. **Create an account and start exploring!**

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Backend** | PHP 8.x |
| **Database** | MySQL with PDO |
| **Icons** | Bootstrap Icons |
| **Fonts** | Poppins (Google Fonts) |
| **Charts** | Chart.js |
| **Security** | CSRF tokens, password hashing (bcrypt), prepared statements, XSS escaping |

---

## 📂 Post Categories

Debuglia comes pre-configured with **10 discussion categories**:

| # | Category |
|---|----------|
| 1 | 🧮 Algorithms and Data Structures |
| 2 | 💻 Programming Languages |
| 3 | 🐛 Debugging and Error Handling |
| 4 | 🏛️ System Design and Architecture |
| 5 | 🌐 Web Development |
| 6 | 📱 Mobile App Development |
| 7 | ⚙️ DevOps and Deployment |
| 8 | 🔒 Security and Cryptography |
| 9 | 🤖 Machine Learning and AI |
| 10 | 🏆 Competitive Programming |

---

## 🔒 Security Features

- ✅ **Password Hashing** — Passwords stored using PHP's `password_hash()` with bcrypt
- ✅ **CSRF Protection** — Unique tokens generated per session and verified on all form submissions
- ✅ **Prepared Statements** — All database queries use PDO parameterized queries
- ✅ **XSS Prevention** — All user content escaped with `htmlspecialchars()`
- ✅ **Session Security** — Server-side session management with authentication checks
- ✅ **Error Logging** — Errors logged to file (`logs/php_errors.log`) instead of displayed to users
- ✅ **File Upload Validation** — Accepts only JPEG, PNG, and GIF image formats

---

## 📬 Contact

- **Email:** [support@debuglia.com](mailto:support@debuglia.com)
- **Phone:** +94 71 123 4567

---

<p align="center">
  <strong>© 2025 Debuglia. All rights reserved.</strong>
</p>

<p align="center">
  Built with ❤️ by the Debuglia Team
</p>
