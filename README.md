# WISHME API

Laravel REST API for WISHME, under the LIWAAS ecosystem.

- Local: `http://localhost:8000/api/wishme`
- Production (later): `api.liwaas.com/api/wishme`

```bash
php artisan serve
```

Health: `GET /api/wishme/health`

This phase is the backend foundation only: Sanctum, queues, policies folder, services folder, and SQLite. Auth, templates, projects, publishing, QR, and Razorpay are not implemented yet. 
