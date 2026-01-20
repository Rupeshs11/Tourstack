# 🏨 TourStacks - Hotel Management & Tourism Website

TourStacks is a full-featured **Hotel Management Website** with integrated **Tourism Packages** built using PHP, MySQL, Bootstrap, and core web techs. It includes a responsive **main user interface** for customers to book rooms and tours, and a powerful **admin panel** for hotel staff to manage bookings and stats.

---

## 🌐 Live Demo

👉 [Visit Website](https://tourstacks.rf.gd)

---

## ✨ Features

### 🧳 For Users:

- Browse and book tourism packages
- Reserve hotel rooms online
- Mobile-responsive & user-friendly design

### 🛠️ For Admins:

- Secure admin login panel
- View and manage all bookings
- Track real-time statistics and hotel data

---

## 🛠️ Tech Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Backend**: PHP
- **Database**: MySQL
- **Containerization**: Docker, Docker Compose
- **Web Server**: Nginx

---

## 🐳 Docker Deployment

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

### Quick Start

1. **Clone the repository**

   ```bash
   git clone https://github.com/yourusername/Tourstack.git
   cd Tourstack
   ```

2. **Start the containers**

   ```bash
   docker-compose up -d --build
   ```

3. **Import the database**
   - Open phpMyAdmin at [http://localhost:8080](http://localhost:8080)
   - Select the `tourstack` database
   - Click **Import** → Choose `tourstack.sql` → Click **Go**

4. **Access the application**
   - 🌐 Website: [http://localhost](http://localhost)
   - 🔧 phpMyAdmin: [http://localhost:8080](http://localhost:8080)

### Docker Services

| Service    | Container            | Port | Description   |
| ---------- | -------------------- | ---- | ------------- |
| Nginx      | tourstack-nginx      | 80   | Web server    |
| PHP-FPM    | tourstack-php        | 9000 | PHP processor |
| MySQL      | tourstack-mysql      | 3306 | Database      |
| phpMyAdmin | tourstack-phpmyadmin | 8080 | DB management |

### Database Credentials

| Setting       | Value              |
| ------------- | ------------------ |
| Host          | mysql              |
| Database      | tourstack          |
| Root Password | rootpassword       |
| User          | tourstack_user     |
| Password      | tourstack_password |

> ⚠️ **Note**: For production, change default passwords in `docker-compose.yml`

### Useful Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Rebuild containers
docker-compose up -d --build
```

---

## 🚀 Run Locally (Without Docker)

1. 📥 Download and install [XAMPP](https://www.apachefriends.org/index.html)
2. 🗂 Copy this project folder into `C:\xampp\htdocs\`
3. ▶ Start **Apache** and **MySQL** from XAMPP control panel
4. 🔧 Open **phpMyAdmin** at [localhost/phpmyadmin](http://localhost/phpmyadmin)
5. 🗃 Create a new database (e.g., `tourstack`) and import the provided `.sql` file
6. 🌍 Open browser and go to: [http://localhost/Tourstack](http://localhost/Tourstack)

---

## 📁 Project Structure

```
Tourstack/
├── docker/
│   ├── nginx/
│   │   └── default.conf      # Nginx configuration
│   └── php/
│       ├── Dockerfile        # PHP-FPM image
│       └── php.ini           # PHP settings
├── images/                   # Static images
├── includes/                 # PHP includes (header, footer)
├── js/                       # JavaScript files
├── docker-compose.yml        # Docker orchestration
├── index.php                 # Main entry point
├── style.css                 # Main stylesheet
└── tourstack.sql             # Database schema
```

---

## 🙌 Author

Made by **Knox**

---

## 📄 License

This project is open-source and free to use for learning purposes.
