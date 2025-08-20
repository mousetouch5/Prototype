# ClickUp Sync Dashboard

A full-stack web application for syncing and automating data between ClickUp workspaces, spaces, and projects. This application provides a comprehensive dashboard for managing multiple ClickUp accounts and configuring automated data synchronization.

## Features

- **User Authentication**: Secure registration, login, and logout with Laravel Sanctum
- **ClickUp Account Management**: Connect multiple ClickUp accounts using Personal Access Tokens
- **Flexible Sync Configuration**: Set up one-way or two-way syncs between ClickUp lists
- **Automated Scheduling**: Configure manual or interval-based sync schedules
- **Comprehensive Sync Options**: 
  - Sync tasks with custom fields
  - Copy comments between tasks
  - Handle attachments (optional)
  - Conflict resolution strategies
- **Sync History & Logs**: Track all sync operations and their results

## Tech Stack

- **Backend**: Laravel 11 with Sanctum authentication
- **Frontend**: React with TypeScript
- **Database**: SQLite (development) / PostgreSQL (production)
- **API Integration**: ClickUp API v2
- **Deployment**: Render.com ready with automatic CI/CD

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 16+
- npm or yarn

### Backend Setup

1. Navigate to the project directory:
```bash
cd clickup-sync-app
```

2. Install PHP dependencies:
```bash
composer install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up the database:
```bash
touch database/database.sqlite
php artisan migrate
```

5. Start the Laravel development server:
```bash
php artisan serve
```

The backend will be available at `http://localhost:8000`

### Frontend Setup

1. Navigate to the frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Start the React development server:
```bash
npm start
```

The frontend will be available at `http://localhost:3000`

## Deployment

This application is ready for deployment on Render.com. See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

### Quick Deployment

1. Push your code to GitHub
2. Connect your GitHub repository to Render
3. Use the provided `render.yaml` for automatic service configuration
4. Set the required environment variables
5. Deploy!

## Getting Started

### 1. Get ClickUp Personal Access Token

1. Log in to your ClickUp account
2. Go to Settings → Apps
3. Click on "Generate" under Personal Token
4. Copy the token (starts with `pk_`)

### 2. Register an Account

1. Navigate to `http://localhost:3000`
2. Click "Register" and create your account
3. You'll be automatically logged in

### 3. Connect ClickUp Accounts

1. In the Dashboard, go to "ClickUp Accounts" tab
2. Click "Add Account"
3. Enter a name for the account and paste your Personal Access Token
4. Click "Add Account" to save

### 4. Create a Sync Configuration

1. Go to "Sync Configurations" tab
2. Click "Create Sync Configuration"
3. Configure:
   - **Name**: Give your sync a descriptive name
   - **Source**: Select account, workspace, space, and list
   - **Target**: Select destination account, workspace, space, and list
   - **Settings**: Choose sync direction, conflict resolution, and what to sync
   - **Schedule**: Set to manual or automatic interval
4. Click "Create Configuration"

### 5. Run a Sync

1. Find your configuration in the list
2. Click "Sync Now" to start the sync process
3. View logs to see the results

## API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get current user

### ClickUp Accounts
- `GET /api/clickup-accounts` - List all accounts
- `POST /api/clickup-accounts` - Add new account
- `DELETE /api/clickup-accounts/{id}` - Delete account
- `POST /api/clickup-accounts/{id}/test` - Test connection
- `GET /api/clickup-accounts/{id}/workspaces` - Get workspaces
- `GET /api/clickup-accounts/{id}/workspaces/{workspaceId}/spaces` - Get spaces
- `GET /api/clickup-accounts/{id}/lists` - Get lists

### Sync Configurations
- `GET /api/sync-configurations` - List all configurations
- `POST /api/sync-configurations` - Create configuration
- `PUT /api/sync-configurations/{id}` - Update configuration
- `DELETE /api/sync-configurations/{id}` - Delete configuration
- `POST /api/sync-configurations/{id}/sync` - Trigger sync
- `GET /api/sync-configurations/{id}/logs` - Get sync logs

## Example ClickUp API Integration

### Creating a Task in ClickUp

```php
// In ClickUpService.php
public function createTask($listId, array $taskData)
{
    $response = $this->client->post("/list/{$listId}/task", [
        'json' => [
            'name' => $taskData['name'],
            'description' => $taskData['description'],
            'status' => $taskData['status'],
            'priority' => $taskData['priority'],
            'due_date' => $taskData['due_date'],
            'assignees' => $taskData['assignees'],
            'tags' => $taskData['tags'],
            'custom_fields' => $taskData['custom_fields'] ?? []
        ]
    ]);
    
    return json_decode($response->getBody()->getContents(), true);
}
```

### Fetching Tasks from ClickUp

```php
// In ClickUpService.php
public function getTasks($listId, $page = 0)
{
    $response = $this->client->get("/list/{$listId}/task", [
        'query' => [
            'archived' => 'false',
            'page' => $page,
            'order_by' => 'created',
            'include_closed' => 'true',
        ]
    ]);
    
    return json_decode($response->getBody()->getContents(), true);
}
```

### Syncing Tasks Between Lists

```php
// In SyncService.php
public function syncTasks(SyncConfiguration $config)
{
    // Fetch all tasks from source
    $sourceTasks = $this->fetchAllTasks($config);
    
    foreach ($sourceTasks as $sourceTask) {
        // Check if task exists in target
        $targetTask = $this->findExistingTask($sourceTask, $config);
        
        if ($targetTask) {
            // Update existing task
            $this->updateTask($targetTask['id'], $sourceTask, $config);
        } else {
            // Create new task
            $this->createTask($sourceTask, $config);
        }
        
        // Sync comments if enabled
        if ($config->sync_comments) {
            $this->syncComments($sourceTask['id'], $targetTask['id'], $config);
        }
    }
}
```

## Security Considerations

- All ClickUp tokens are encrypted before storage using Laravel's encryption
- API authentication uses Laravel Sanctum with bearer tokens
- CORS is configured for local development
- All API endpoints are protected with authentication middleware

## Troubleshooting

### Backend Issues

1. **Database connection errors**: Ensure SQLite file exists and has proper permissions
2. **Token validation errors**: Check that your ClickUp Personal Access Token is valid
3. **CORS errors**: Make sure the frontend proxy is configured correctly in package.json

### Frontend Issues

1. **API connection errors**: Verify backend is running on port 8000
2. **Authentication issues**: Clear localStorage and re-login
3. **Build errors**: Delete node_modules and reinstall dependencies

## Future Enhancements

- OAuth 2.0 support for ClickUp authentication
- Webhook support for real-time syncing
- Advanced field mapping and transformation rules
- Bulk operations and batch processing
- Export/import sync configurations
- Email notifications for sync status
- Advanced scheduling with cron expressions
- Two-way sync with conflict detection

## License

This project is open source and available under the MIT License.