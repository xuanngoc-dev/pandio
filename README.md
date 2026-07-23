# Pandio — Laravel API + Vue 3 SPA

Mono-repo gồm:
- `backend/` — Laravel 13 (API thuần) + Laravel Sanctum
- `frontend/` — Vue 3 + Vite + Pinia + Axios + Element Plus

## Yêu cầu

- PHP 8.2+ / Composer
- Node.js 18+ / npm
- SQLite (mặc định) hoặc MySQL

---

## 1. Setup Backend (Laravel)

```bash
cd backend

# Cài dependency (đã có sẵn nếu clone project này)
composer install

# Copy env nếu chưa có
cp .env.example .env
php artisan key:generate

# Tạo DB SQLite (nếu dùng SQLite)
touch database/database.sqlite

# Chạy migration (users + personal_access_tokens)
php artisan migrate

# Chạy API server
php artisan serve
# → http://localhost:8000
```

### API endpoints

| Method | URL | Auth | Mô tả |
|--------|-----|------|--------|
| POST | `/api/auth/register` | Không | Đăng ký |
| POST | `/api/auth/login` | Không | Đăng nhập |
| POST | `/api/auth/logout` | Bearer | Đăng xuất |
| GET | `/api/user` | Bearer | User hiện tại |

**Register body:**
```json
{
  "name": "Nguyen Van A",
  "email": "a@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Login body:**
```json
{
  "email": "a@example.com",
  "password": "password"
}
```

Response trả về `token` (Sanctum Personal Access Token) — frontend gửi header:
```
Authorization: Bearer {token}
```

---

## 2. Setup Frontend (Vue 3)

```bash
cd frontend

npm install

# Kiểm tra .env
# VITE_API_BASE_URL=http://localhost:8000/api

npm run dev
# → http://localhost:5173
```

Build production:
```bash
npm run build
npm run preview
```

---

## 3. Cấu trúc quan trọng

```
pandio/
├── backend/
│   ├── app/Http/Controllers/Api/AuthController.php
│   ├── app/Models/User.php          # HasApiTokens
│   ├── config/cors.php
│   ├── config/sanctum.php
│   └── routes/api.php
└── frontend/
    ├── .env
    ├── vite.config.js
    └── src/
        ├── api/axios.js             # Interceptors + 401
        ├── stores/auth.js           # Pinia
        ├── router/index.js          # beforeEach guard
        ├── layouts/MainLayout.vue   # Sidebar + Header
        └── views/                   # Home, Login, Register, Dashboard
```

---

## 4. Ghi chú kỹ thuật

- **Auth:** Sanctum Bearer Token (phù hợp SPA chạy port khác với API).
- **CORS:** `config/cors.php` cho phép `FRONTEND_URL` (mặc định `http://localhost:5173`).
- **401:** Axios interceptor xoá token + chuyển về `/login`.
- **Dark mode:** toggle trên Header; hoặc `VITE_DARK_MODE=true` trong `.env`.
- **Element Plus:** global import trong `main.js` + on-demand resolver trong Vite.
- **Vue:** Composition API (`<script setup>`) theo best practices.

---

## 5. Chạy đồng thời (2 terminal)

```bash
# Terminal 1
cd backend && php artisan serve

# Terminal 2
cd frontend && npm run dev
```

Mở trình duyệt: http://localhost:5173
