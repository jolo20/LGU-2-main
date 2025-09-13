# 📂 LGU-2 Main Project

![PHP](https://img.shields.io/badge/PHP-8-blue?logo=php&logoColor=white)  
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?logo=mysql&logoColor=white)  
![phpMyAdmin](https://img.shields.io/badge/phpMyAdmin-Tool-brightgreen?logo=phpmyadmin&logoColor=white)  
![Laragon](https://img.shields.io/badge/Laragon-Local%20Server-lightblue)  
![VSCode](https://img.shields.io/badge/VSCode-Editor-blue?logo=visualstudiocode&logoColor=white)  
![GitHub](https://img.shields.io/badge/GitHub-Version%20Control-black?logo=github)

**LGU Document Tracking & Management System**

This repository contains all modules and submodules for the LGU Document Tracking & Management System.  
All feature code is organized under the `contents/` folder — pick your module there and create submodules as needed.

---

# 🚀 Getting Started

## 1. Clone the repository
```bash
git clone https://github.com/LGU2.git
cd LGU2
```

## 2. Import the database
1. Open **phpMyAdmin** (or MySQL client).  
2. Import `login.sql` from the project root: **Import → Choose file → Go**.  
3. Confirm the database and required tables are created.

> If you use Laragon/XAMPP: place the project in `C:\laragon\www\` or `C:\xampp\htdocs\`.

## 3. Run locally
- Start Apache & MySQL in Laragon or XAMPP.  
- Visit:
```
http://localhost/LGU2
```

---

# 🛠️ Tech Stack

- **Backend:** PHP 8+  
- **Database:** MySQL / MariaDB  
- **DB Tool:** phpMyAdmin  
- **Local Server:** Laragon / XAMPP (Apache + MySQL)  
- **Frontend:** HTML, CSS, JavaScript  
- **Version Control:** Git & GitHub  
- **IDE:** Visual Studio Code

---

# 🗂 Repository Structure (recommended)

```
/ (repo root)
├─ contents/                     # All modules & submodules live here
│  ├─ module-a/
│  └─ module-b/
├─ uploads/
├─ includes/
├─ login.sql
├─ README.md
└─ .gitignore
```

---

# 🛠 Development Guidelines

- All **modules and submodules** go under `contents/`.  
- When adding features:
  - Create or use the appropriate folder under `contents/`.
  - Follow existing code style and naming conventions.  
  - Test locally before pushing.

---

# ⚠️ PLEASE DO NOT EDIT (unless absolutely necessary)

- `auth.php`  
- `login.php`

> `profile.php` may be modified only if you are confident with the authentication flow and related fixes. Coordinate major changes with the team lead.

---

# 🎨 Contributing & Design Changes

- To propose design or text changes:
  1. Fork the repo / create a branch: `git checkout -b feature/your-change`
  2. Make changes locally and test them.
  3. Push branch and open a Pull Request for review.

- For UI/UX updates: provide screenshots in the PR and a short description of what changed.

---

# 📚 Git & GitHub Workflow (easy)

1. Pull latest:
```bash
git pull origin main
```
2. Create feature branch:
```bash
git checkout -b feature/awesome-change
```
3. Stage & commit:
```bash
git add .
git commit -m "feat(auth): add user access and permissions"
git push origin feature/awesome-change
```
4. Open a Pull Request on GitHub.

---

# ✅ Commit Message Guidelines

Use conventional-style messages for clarity:

```
<type>(<scope>): <short summary>

<more detailed description, bullet points if needed>
```

Examples:
- `feat(auth): add user access and role checks`
- `fix(db): correct SQL queries for measure module`
- `refactor: restructure handlers and error handling`

---

# 🧪 Testing

- Manual testing approach:
  - Use **Browser DevTools** (console, network, Lighthouse) for frontend checks.  
  - Use **phpMyAdmin** to verify DB operations and data integrity.  
  - Review **PHP error logs** and Apache logs for server errors.

- If you add automated tests in future, store them under `tests/`.

---

# 🚚 Deployment

- For local -> production:
  - Export DB using phpMyAdmin (`Export` → SQL).  
  - Upload PHP files to production server and import SQL.  
  - Update database credentials in your production `connection.php` (do not commit credentials).
  - Ensure proper file/folder permissions and secure `.env` or config files.

---

# 🖥️ Operate & Monitor

- Run-time stack:
  - **Apache** serves PHP code (Laragon/XAMPP in development).  
  - **MySQL** handles data persistence.

- Monitoring:
  - Regularly check PHP and Apache error logs.
  - Use phpMyAdmin to inspect slow queries and DB health.
  - Add logging (files or database) for critical flows (auth, uploads, notifications).

---

# 🔐 Security Notes

- Never commit passwords or credentials to Git. Use environment variables or config files excluded by `.gitignore`.  
- Validate and sanitize all user inputs. Use prepared statements for DB queries.  
- Limit file upload types/sizes and store uploads outside the webroot or with safe names.

---

# ❓ Troubleshooting

**403 / Forbidden:** add `Require all granted` in Apache `<Directory>` config and restart Apache.  
**DB remote connection errors:** use `localhost` for in-server connections or create a dedicated DB user with appropriate host privileges for remote access.  
**Headers already sent:** avoid `echo`ing content before `header("Location: ...")`.

