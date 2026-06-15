# 🚀 DEV DOCS — WA GATEWAY DEPLOYMENT STRATEGY UPDATE
## Analisis Pull Terbaru & Multi-Platform Deployment Support

**Tanggal:** 15 Juni 2026
**Waktu (Asia/Jakarta):** 14:33 WIB
**Agent:** Arena Agent Mode (claude-sonnet)
**Trigger:** User pull ulang dari `simt-wa-gateway` setelah update deployment strategy

> **Dokumen ini menganalisis 7 commit terbaru dari user.**
> Perubahan signifikan: **Serverless dihapus**, diganti dengan **multi-platform support** (Railway, Render, Fly.io, Docker, PM2) + **Prisma ORM** untuk database logging.

---

## 📑 DAFTAR TOPIK

1. [Ringkasan 7 Commit Baru](#-1-ringkasan-7-commit-baru)
2. [Perubahan Strategis: Serverless → PaaS](#-2-perubahan-strategis-serverless--paas)
3. [Multi-Platform Deployment Support](#-3-multi-platform-deployment-support)
4. [Database Logging dengan Prisma ORM](#-4-database-logging-dengan-prisma-orm)
5. [Deployment Guide (295 baris)](#-5-deployment-guide-295-baris)
6. [Verifikasi File Deployment Config](#-6-verifikasi-file-deployment-config)
7. [Rekomendasi Saya](#-7-rekomendasi-saya)

---

## ✅ 1. Ringkasan 7 Commit Baru

| # | Commit | Waktu | Pesan |
|---|---|---|---|
| 1 | `b4bb845` | 08:04 | docs: add database logging design and project development report |
| 2 | `a3bbc88` | 08:08 | feat: integrate Prisma ORM for database logging + db management scripts |
| 3 | `8dc3cde` | 08:13 | chore: **remove platform-specific deployment** (serverless dihapus!) |
| 4 | `1c944f4` | 08:14 | chore: **remove serverless-http** + add railway configuration |
| 5 | `6566085` | 08:14 | feat: add Render configuration for deployment via Docker |
| 6 | `85ad1f2` | 08:14 | chore: add Docker Compose, Fly.io, and PM2 deployment |
| 7 | `3e34a12` | **14:26** | docs: add deployment guide (295 baris!) + PM2 config |

## 📂 Struktur Repo (29 file)

```
simt-wa-gateway/
├── .dockerignore
├── .env.example
├── .gitignore
├── DEV_DOCS/                              (5 file)
│   ├── 001_implementation_plan.md
│   ├── 002_deploy_vercel_netlify.md       ⚠️ Historical (serverless dihapus)
│   ├── 003_database_logging_prisma.md     🆕 Prisma design
│   ├── 004_dev_report.md                  🆕 Dev report lengkap
│   └── 005_deployment_guide.md             🆕 Deployment guide 295 baris
├── Dockerfile
├── docker-compose.yml                     🆕 Orchestration
├── ecosystem.config.js                    🆕 PM2 config
├── fly.toml                               🆕 Fly.io config
├── railway.json                           🆕 Railway config
├── render.yaml                            🆕 Render config (with persistent disk!)
├── package-lock.json
├── package.json                           🆕 + Prisma scripts (db:migrate, db:studio, dll)
├── tsconfig.json
└── src/
    ├── index.ts, app.ts, config.ts, types.ts
    ├── middlewares/auth.ts
    ├── routes/index.ts                    (127 baris)
    ├── services/whatsapp.ts               (203 baris)
    └── utils/logger.ts, webhook.ts
```

---

## ✅ 2. Perubahan Strategis: Serverless → PaaS

### 🚨 Yang Dihapus User

User mengikuti rekomendasi saya! Berikut yang **DIHAPUS**:

```
❌ HAPUS: netlify.toml               (commit 8dc3cde)
❌ HAPUS: netlify/functions/api.ts   (commit 8dc3cde)
❌ HAPUS: vercel.json                (commit 8dc3cde + 1c944f4)
❌ HAPUS: serverless-http dependency (commit 1c944f4)
```

**Doc 002 (`002_deploy_vercel_netlify.md`)** tetap ada sebagai **historical reference** — didokumentasikan kenapa serverless TIDAK COCOK untuk WA Gateway.

### ✅ Yang Ditambahkan sebagai Pengganti

| Platform | File Config | Status |
|---|---|---|
| **Railway** | `railway.json` | 🆕 PaaS termudah, free $5/bln |
| **Render** | `render.yaml` | 🆕 Free 750 jam/bln, support persistent disk |
| **Fly.io** | `fly.toml` | 🆕 Persistent volume, region Singapore |
| **VPS + Docker** | `docker-compose.yml` + `Dockerfile` | 🆕 Production serius |
| **VPS + PM2** | `ecosystem.config.js` | 🆕 Full control |

### Kutipan dari Deployment Guide tentang Serverless

```markdown
> [!IMPORTANT]
> WA Gateway berbasis Baileys **membutuhkan** server yang berjalan terus-menerus (*always-on*) 
> dengan filesystem persisten. Platform serverless (Vercel/Netlify) **tidak cocok** untuk aplikasi ini.
```

✅ **User mengikuti rekomendasi saya tentang serverless!**

---

## ✅ 3. Multi-Platform Deployment Support

### Tabel Platform (dari Deployment Guide)

| Platform | Harga | Kesulitan | Cocok Untuk |
|---|---|---|---|
| **Railway** | Free $5 credit/bln | ⭐ Mudah | Coba-coba & kecil |
| **Render** | Free 750 jam/bln | ⭐⭐ Mudah | Development & staging |
| **Fly.io** | Free 3 shared VM | ⭐⭐ Sedang | Production ringan |
| **VPS + Docker** | ~$4–6/bln | ⭐⭐⭐ Sedang | Production serius |
| **VPS + PM2** | ~$4–6/bln | ⭐⭐⭐ Sedang | Full control |

### Detail Setiap Config

#### 1. `docker-compose.yml` 🆕

```yaml
version: '3.8'

services:
  wa-gateway:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: simt-wa-gateway
    restart: unless-stopped
    ports:
      - "8081:8081"
    env_file:
      - .env
    environment:
      - NODE_ENV=production
    volumes:
      - ./sessions:/app/sessions      # ✅ Persistent!
      - ./logs:/app/logs
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", 
             "http://localhost:8081/api/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 30s
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "5"
    networks:
      - gateway-net

networks:
  gateway-net:
    driver: bridge
```

#### 2. `ecosystem.config.js` (PM2) 🆕

```js
module.exports = {
  apps: [{
    name: 'simt-wa-gateway',
    script: 'dist/index.js',
    instances: 1,                    // ✅ WA Gateway harus 1 instance (stateful!)
    autorestart: true,
    watch: false,
    max_memory_restart: '512M',
    env: { NODE_ENV: 'development', PORT: 8081 },
    env_production: { NODE_ENV: 'production', PORT: 8081 },
    log_date_format: 'YYYY-MM-DD HH:mm:ss',
    error_file: './logs/pm2-error.log',
    out_file: './logs/pm2-out.log',
    merge_logs: true,
    exp_backoff_restart_delay: 100,
    max_restarts: 10,
    restart_delay: 5000,
  }],
};
```

**Insight:** `instances: 1` — penting karena session Map harus singleton!

#### 3. `fly.toml` 🆕

```toml
app = "simt-wa-gateway"
primary_region = "sin"   # Singapore — terdekat untuk Indonesia

[build]
  dockerfile = "Dockerfile"

[env]
  PORT = "8081"
  NODE_ENV = "production"

[http_service]
  internal_port = 8081
  force_https = true
  min_machines_running = 1

  [http_service.concurrency]
    type = "connections"
    hard_limit = 100
    soft_limit = 80

[[vm]]
  memory = "512mb"
  cpu_kind = "shared"
  cpus = 1

[mounts]                              # ✅ Persistent volume
  source = "wa_sessions"
  destination = "/app/sessions"
```

#### 4. `railway.json` 🆕

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  },
  "deploy": {
    "startCommand": "node dist/index.js",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 5
  }
}
```

#### 5. `render.yaml` 🆕 (PALING LENGKAP)

```yaml
services:
  - type: web
    name: simt-wa-gateway
    runtime: docker
    dockerfilePath: ./Dockerfile
    plan: free
    envVars:
      - key: PORT
        value: 8081
      - key: NODE_ENV
        value: production
      - key: WA_GATEWAY_API_KEY
        sync: false
      - key: LARAVEL_WEBHOOK_URL
        sync: false
      - key: WA_CALLBACK_SECRET
        sync: false
    disk:                             # ✅ Persistent disk!
      name: sessions
      mountPath: /app/sessions
      sizeGB: 1
    healthCheckPath: /api/health
```

---

## ✅ 4. Database Logging dengan Prisma ORM

### Tujuan (Doc 003)

```
TABEL YANG AKAN DIBUAT:
├── tenants          — Metadata semua tenant terdaftar
├── session_logs     — Log event sesi (connected, disconnected, qr_generated)
├── message_logs     — Log pesan terkirim & masuk
├── webhook_logs     — Log callback ke Laravel (success/fail)
└── api_request_logs — Log semua request API masuk ke gateway
```

### Skema Prisma (Belum Ada File — Hanya Plan)

User sudah buat **design doc** (`003_database_logging_prisma.md`) dan update `package.json` dengan scripts:

```json
"scripts": {
  "postinstall": "npx prisma generate --no-engine 2>nul || exit 0",
  "prebuild": "npx prisma generate --no-engine 2>nul || exit 0",
  "build": "tsc",
  "db:migrate": "prisma migrate deploy",
  "db:migrate:dev": "prisma migrate dev",
  "db:studio": "prisma studio",
  "db:generate": "prisma generate",
  "db:reset": "prisma migrate reset"
}
```

⚠️ **Catatan:** `package.json` punya `prisma` scripts, tapi Prisma sendiri belum di-install (`@prisma/client` dan `prisma` tidak ada di dependencies). Implementasi masih TODO.

### Prisma Schema Design (dari Doc 003)

```prisma
// prisma/schema.prisma
datasource db {
  provider = "sqlite"   // default, override via DATABASE_URL
  url      = env("DATABASE_URL")
}

generator client {
  provider = "prisma-client-js"
}

model Tenant {
  id            String         @id
  name          String?
  sessionLogs   SessionLog[]
  messageLogs   MessageLog[]
  webhookLogs   WebhookLog[]
  apiLogs       ApiRequestLog[]
  createdAt     DateTime       @default(now())
}

model SessionLog {
  id        Int      @id @default(autoincrement())
  tenantId  String
  event     String   // 'connected', 'disconnected', 'qr_generated'
  status    String?
  number    String?
  tenant    Tenant   @relation(fields: [tenantId], references: [id])
  createdAt DateTime @default(now())
}

model MessageLog {
  id          Int      @id @default(autoincrement())
  tenantId    String
  to          String?
  from        String?
  text        String?
  messageId   String?
  referenceId String?
  direction   String   // 'outgoing' | 'incoming'
  tenant      Tenant   @relation(fields: [tenantId], references: [id])
  createdAt   DateTime @default(now())
}

model WebhookLog {
  id        Int      @id @default(autoincrement())
  tenantId  String
  event     String
  payload   String   // JSON
  statusCode Int?
  success   Boolean
  tenant    Tenant   @relation(fields: [tenantId], references: [id])
  createdAt DateTime @default(now())
}

model ApiRequestLog {
  id         Int      @id @default(autoincrement())
  method     String
  path       String
  tenantId   String?
  statusCode Int
  durationMs Int
  tenant     Tenant?  @relation(fields: [tenantId], references: [id])
  createdAt  DateTime @default(now())
}
```

⚠️ **Catatan penting:** Doc 003 sangat bagus, tapi belum ada kode implementasi (Prisma belum ter-install di package.json).

---

## ✅ 5. Deployment Guide (295 baris)

### File: `DEV_DOCS/005_deployment_guide.md` 🆕

User buat panduan deployment lengkap dengan 5 platform:

1. **Railway** (Termudah) - free $5/bln
2. **Render** (Mudah) - free 750 jam/bln
3. **Fly.io** (Sedang) - region Singapore, persistent volume
4. **VPS + Docker** (Sedang) - $4-6/bln
5. **VPS + PM2** (Sedang) - $4-6/bln, full control

### Kutipan Penting dari Guide

```markdown
## 1. Railway (Termudah)

### Prasyarat
- Akun [railway.app](https://railway.app)
- GitHub repo sudah push

### Langkah Deploy

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link ke project baru
railway init

# Deploy
railway up
```

### Set Environment Variables di Dashboard

```
Railway Dashboard → Project → Variables → Add:
  PORT=8081
  NODE_ENV=production
  WA_GATEWAY_API_KEY=<api-key-anda>
  LARAVEL_WEBHOOK_URL=<url-laravel-anda>
  WA_CALLBACK_SECRET=<secret-anda>
```

> [!NOTE]
> Railway mendukung **persistent volumes** untuk menyimpan data sesi WA.
> Railway Dashboard → Project → Volumes → Create Volume → mount ke `/app/sessions`
```
```

---

## ✅ 6. Verifikasi File Deployment Config

### Semua File Deployment Config (Baru)

| File | Platform | Status | Key Insight |
|---|---|---|---|
| `Dockerfile` | Docker | ✅ Multi-stage build | Image kecil |
| `docker-compose.yml` | Docker Compose | ✅ Healthcheck + restart | Production-ready |
| `ecosystem.config.js` | PM2 | ✅ instances:1 | Statefullness |
| `fly.toml` | Fly.io | ✅ Persistent volume | region sin |
| `railway.json` | Railway | ✅ Docker builder | Free $5/bln |
| `render.yaml` | Render | ✅ **Persistent disk 1GB** | Mount /app/sessions |

### File yang Dihapus (Serverless)

| File | Platform | Alasan Dihapus |
|---|---|---|
| `netlify.toml` | Netlify | Serverless tidak cocok untuk WA Gateway |
| `netlify/functions/api.ts` | Netlify | Fungsi serverless terbatas |
| `vercel.json` | Vercel | Execution timeout + ephemeral disk |

### File yang Tetap (Historical)

| File | Tujuan |
|---|---|
| `DEV_DOCS/002_deploy_vercel_netlify.md` | Dokumentasi kenapa serverless TIDAK cocok |

---

## ✅ 7. Rekomendasi Saya

### Verdict Final Update Ini

| Aspek | Status |
|---|---|
| Menghapus serverless (Netlify/Vercel) | ✅ **Sangat Bagus** — sesuai rekomendasi saya |
| Menambah multi-platform deployment (5 opsi) | ✅ **Sangat Bagus** — fleksibel |
| Menambah Dockerfile multi-stage | ✅ **Production-ready** |
| Menambah docker-compose.yml dengan healthcheck | ✅ **Production-grade** |
| Menambah PM2 config dengan `instances:1` | ✅ **Penting** untuk statefulness |
| Menambah persistent disk di Render.yaml | ✅ **Critical** untuk session persistence |
| Menambah deployment guide 295 baris | ✅ **Onboarding-friendly** |
| Menambah dev report lengkap | ✅ **Accountability** |
| Prisma ORM design (Doc 003) | ✅ **Bagus** tapi belum implementasi |
| Prisma belum di-install di package.json | ⚠️ **TODO** — implementasi belum lengkap |

### ⚠️ PRISMA ORM: Design tapi Belum Implementasi

User punya design bagus tapi **kode belum ditulis**:

```bash
# Belum ada:
❌ prisma/schema.prisma (hanya di doc 003)
❌ src/lib/db.ts
❌ src/services/logService.ts
❌ src/utils/prisma-client.ts

# package.json punya scripts db:* tapi @prisma/client belum di-install
```

**Rekomendasi:** Lanjut implementasi Prisma ATAU skip dulu (logging penting tapi bukan blocker untuk MVP).

---

## 🎯 NEXT STEPS

Setelah pull ini, **WA Gateway sudah sangat mature**. Yang masih kurang:

### Recommended Path Forward

- **(A)** Lanjut **Phase 2 — Portal Next.js** (Sprint 5) — backend sudah siap semua
- **(B)** Implementasi Prisma (Doc 003) — sesuai design yang ada (~4-6 jam)
- **(C)** Buat **docker-compose multi-service** untuk 3 codebase (Laravel + WA Gateway + Next.js nanti)
- **(D)** Buat **integration test** end-to-end Laravel ↔ WA Gateway (~2 jam)
- **(E)** Live deploy WA Gateway ke Railway/Render/Fly.io untuk testing

### Mana yang Anda Pilih?

---

## 📂 LOKASI DOKUMEN INI

| Lokasi |
|---|
| `/home/user/69_DEV_DOCS_WA_GATEWAY_DEPLOYMENT_STRATEGY_2026-06-15_14-33.md` |
| `/home/user/DEV_DOCS/docs_sim/69_DEV_DOCS_WA_GATEWAY_DEPLOYMENT_STRATEGY_2026-06-15_14-33.md` |

---

## 📋 DOKUMEN TERKAIT

| File | Sumber |
|---|---|
| `68_DEV_DOCS_INTEGRASI_SIMT_WA_GATEWAY_2026-06-15_07-45.md` | Analisis integrasi WA Gateway (sebelumnya) |
| `67_DEV_REPORT_SPRINT5_PHASE1_BACKEND_FINANCE_2026-06-14_20-50.md` | Dev Report Phase 1 Backend Finance |
| `66_ANALISIS_MULTI_CODEBASE_ARCHITECTURE_2026-06-14_19-48.md` | Analisis multi-codebase |

---

*Dokumen ini disusun 15 Juni 2026 14:33 WIB oleh Agent Arena Mode. Setiap klaim diverifikasi dengan clone repo + git log + cat file langsung. Disimpan dengan format `xx_namafile_date_time.md` per konvensi.*
