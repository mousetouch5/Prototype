# Deployment Guide for ClickUp Sync Dashboard

## Environment Variables for Render

### Backend (Laravel) Environment Variables

Set these in your Render Web Service environment variables:

```bash
# Application Configuration
APP_NAME="ClickUp Sync Dashboard"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

# Database (PostgreSQL provided by Render)
DB_CONNECTION=pgsql
DB_HOST=YOUR_POSTGRES_HOST
DB_PORT=5432
DB_DATABASE=YOUR_POSTGRES_DB
DB_USERNAME=YOUR_POSTGRES_USER
DB_PASSWORD=YOUR_POSTGRES_PASSWORD

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# CORS Configuration
FRONTEND_URL=https://your-frontend-app.onrender.com

# Sanctum Configuration (adjust domains for production)
SANCTUM_STATEFUL_DOMAINS=your-app-name.onrender.com,your-frontend-app.onrender.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Frontend (React) Environment Variables

Set these in your Render Static Site environment variables:

```bash
# API Backend URL
REACT_APP_API_URL=https://your-backend-app.onrender.com

# Environment
NODE_ENV=production
```

## Render Services Setup

### 1. Backend Web Service

- **Service Type**: Web Service
- **Build Command**: `./build.sh`
- **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`
- **Environment**: Node 18 (for frontend build)
- **Instance Type**: Starter (upgradeable)

### 2. Frontend Static Site

- **Service Type**: Static Site
- **Build Command**: `cd frontend && npm ci && npm run build`
- **Publish Directory**: `frontend/build`
- **Auto-Deploy**: Yes

### 3. PostgreSQL Database

- Create a PostgreSQL database on Render
- Note down the connection details for environment variables

## Manual Deployment Steps

1. **Create Backend Web Service**
   - Connect your GitHub repository
   - Set branch to `main`
   - Add all backend environment variables
   - Deploy

2. **Create Frontend Static Site**
   - Same GitHub repository
   - Set branch to `main`
   - Add frontend environment variables
   - Set build command and publish directory
   - Deploy

3. **Create Database**
   - Create PostgreSQL database
   - Update backend environment variables with database connection details

## Security Considerations

- Never commit `.env` files to version control
- Use strong, unique `APP_KEY` (generate with `php artisan key:generate`)
- Set `APP_DEBUG=false` in production
- Configure proper CORS origins
- Use HTTPS in production URLs

## Troubleshooting

- Check logs in Render dashboard
- Ensure all environment variables are set correctly
- Verify database connection
- Check CORS configuration if API calls fail