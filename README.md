# OGMS - Online Grade Monitoring System 🚀

**Lubo National High School** | Fully Functional Frontend Demo

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Static Frontend](https://img.shields.io/badge/Tech-HTML5%20CSS3%20VanillaJS-blue.svg)](https://github.com/)
[![Demo](https://img.shields.io/badge/Live%20Demo-local-green.svg)](http://localhost/OGMS-Lubo-National-High-School/)

## 📖 Overview

**OGMS** (Online Grade Monitoring System) is a modern web application designed for Lubo National High School to manage and monitor student grades online.

**Key Features:**

- ✅ Student Portal: View grades, analytics, reports, profile
- ✅ Admin Dashboard: Manage students/grades, analytics, reports, SMS simulation
- ✅ Responsive Design (Mobile/Desktop)
- ✅ Print-ready Reports
- ✅ Local data persistence (localStorage)

**Current Status**: Fully functional **static frontend demo** using mock data. Backend-ready (MySQL structure planned).

## ✨ Features

See [docs/FEATURES.md](docs/FEATURES.md) for complete list.

| Student                  | Admin                     |
| ------------------------ | ------------------------- |
| 📊 Dashboard & Analytics | 📈 Admin Dashboard        |
| 📚 Grades by Quarter     | ✏️ Manage Students/Grades |
| 🖨️ Printable Reports     | 📋 Reports & SMS          |
| 👤 Profile & Signup      | 🔐 Profile Management     |

## 🛠️ Tech Stack

Detailed breakdown: [docs/Techstacks.md](docs/Techstacks.md)

```
Frontend: HTML5 • CSS3 • Bootstrap 5.3 • Vanilla JS (ES6+) • Chart.js • Font Awesome
Data: localStorage • Mock JSON data
Dev: VSCode • XAMPP (Apache)
Future: PHP • MySQL
```

## 🚀 Quick Start (5 Minutes)

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache only needed)

### Run Locally

1. **Start XAMPP Apache** (MySQL optional for demo)
2. Open `http://localhost/OGMS-Lubo-National-High-School/` in browser
3. **Login** with demo credentials

### Demo Credentials

| Role        | Email                 | Password     |
| ----------- | --------------------- | ------------ |
| **Student** | `student@lnhs.edu.ph` | `student123` |
| **Admin**   | `admin@lnhs.edu.ph`   | `admin123`   |

## 📁 Project Structure

```
OGMS-Lubo-National-High-School/
├── index.html                 # Login page
├── assets/                    # CSS/JS
├── components/                # Reusable HTML
├── data/                      # Mock data
├── views/admin/               # Admin pages
├── views/student/             # Student pages
└── docs/                      # Documentation
   ├── PROJECT-STRUCTURE.md
   ├── FEATURES.md
   ├── USER-GUIDE.md
   └── Techstacks.md
```

Full structure: [docs/PROJECT-STRUCTURE.md](docs/PROJECT-STRUCTURE.md)

## 📚 Documentation

- [System Memory & Synchronization Matrix](SYSTEM-MEMORY.md)
- [User Guide](docs/USER-GUIDE.md)
- [Tech Stack](docs/Techstacks.md)
- [Features](docs/FEATURES.md)

## 🖼️ Screenshots

|               **Student Dashboard**                |              **Admin Analytics**               |
| :------------------------------------------------: | :--------------------------------------------: |
| ![Student](docs/screenshots/student-dashboard.png) | ![Admin](docs/screenshots/admin-dashboard.png) |

_(Add screenshots to `docs/screenshots/` folder)_

## 🔮 Future Roadmap

- [ ] PHP Backend + MySQL Integration
- [ ] Real-time Updates (WebSockets)
- [ ] SMS Integration (Twilio)
- [ ] Role-based Authentication
- [ ] Export to PDF/Excel

## 🤝 Contributing

1. Fork the repo
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is [MIT](LICENSE) licensed - see [LICENSE](LICENSE) file.

## 🙏 Acknowledgments

Built for **Lubo National High School**. Icons by Font Awesome. Charts by Chart.js.

---

⭐ **Star on GitHub** if useful! Questions? Open an [issue](https://github.com/YOUR-USERNAME/OGMS-Lubo-National-High-School/issues).
