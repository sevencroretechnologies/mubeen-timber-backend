# Swagger API Documentation Setup

This document explains how Swagger UI and API documentation was implemented in the WorkDo HRMS project.

## Overview

The API documentation uses two complementary tools:

1. **Scramble** - Laravel package that auto-generates OpenAPI 3.0 specifications from your code
2. **Swagger UI** - Interactive API documentation viewer

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Laravel Backend                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Controllers & Routes ───► Scramble ───► OpenAPI JSON Spec  │
│                              │                │              │
│                              ▼                ▼              │
│                    /docs/api (Stoplight)  /docs/api.json    │
│                                                │              │
│                                                ▼              │
│                                    /swagger.html (Swagger UI)│
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Step-by-Step Implementation

### Step 1: Install Scramble Package

```bash
# Download Composer if not available
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php

# Install Scramble
php composer.phar require dedoc/scramble
```

**What Scramble Does:**

- Analyzes your Laravel routes and controllers
- Reads PHPDoc comments and type hints
- Auto-generates OpenAPI 3.0 specification
- Provides Stoplight Elements UI at `/docs/api`
- Serves JSON spec at `/docs/api.json`

### Step 2: Configure Scramble in AppServiceProvider

**File:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Configure Scramble API documentation
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                // Add JWT Bearer authentication scheme
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });
    }
}
```

**Configuration Options:**

- `SecurityScheme::http('bearer', 'JWT')` - Adds "Authorize" button for JWT tokens
- You can also add API key auth, OAuth2, etc.

### Step 3: Create Swagger UI Page

**File:** `public/swagger.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WorkDo HRMS - Swagger API Documentation</title>
  
  <!-- Swagger UI CSS from CDN -->
  <link rel="stylesheet" type="text/css" 
        href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
  
  <style>
    html {
      box-sizing: border-box;
      overflow: -moz-scrollbars-vertical;
      overflow-y: scroll;
    }
    *, *:before, *:after {
      box-sizing: inherit;
    }
    body {
      margin: 0;
      background: #fafafa;
    }
    /* Custom branding - blue topbar */
    .swagger-ui .topbar {
      background-color: #2563eb;
    }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  
  <!-- Swagger UI JavaScript from CDN -->
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
  
  <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        // Points to Scramble's OpenAPI JSON endpoint
        url: "/docs/api.json",
        
        // DOM element to render into
        dom_id: '#swagger-ui',
        
        // Enable deep linking for sharing specific endpoints
        deepLinking: true,
        
        // Use standard presets
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        
        // Enable URL download plugin
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        
        // Standalone layout with topbar
        layout: "StandaloneLayout",
        
        // Remember auth tokens across page reloads
        persistAuthorization: true,
        
        // Show request duration
        displayRequestDuration: true,
        
        // Enable filtering/searching endpoints
        filter: true,
        
        // Show vendor extensions
        showExtensions: true,
        showCommonExtensions: true
      });
      
      // Expose UI instance globally for debugging
      window.ui = ui;
    };
  </script>
</body>
</html>
```

**Key Configuration Options:**

| Option | Description |
|--------|-------------|
| `url` | Path to OpenAPI JSON spec (uses relative path to avoid CORS) |
| `deepLinking` | Enables sharing URLs to specific endpoints |
| `persistAuthorization` | Saves JWT token in localStorage |
| `displayRequestDuration` | Shows how long each request took |
| `filter` | Adds search box to filter endpoints |

### Step 4: Access the Documentation

| URL | Description |
|-----|-------------|
| `http://localhost:8000/swagger.html` | **Swagger UI** - Classic interactive docs |
| `http://localhost:8000/docs/api` | **Stoplight Elements** - Modern alternative |
| `http://localhost:8000/docs/api.json` | **Raw OpenAPI JSON** - For import into other tools |

## How Scramble Auto-Generates Documentation

Scramble reads your code and generates documentation automatically:

### From Route Definitions

```php
// routes/api.php
Route::apiResource('staff-members', StaffMemberController::class);
Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
```

### From Controller Methods

```php
/**
 * Get all staff members.
 * 
 * @queryParam status string Filter by status (active, inactive). Example: active
 * @queryParam division_id int Filter by division. Example: 1
 */
public function index(Request $request): JsonResponse
{
    // Scramble infers response type from JsonResponse
}
```

### From Request Validation

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:staff_members',
        'hire_date' => 'required|date',
    ]);
    // Scramble auto-documents required fields from validation rules
}
```

### From Model Relationships

```php
class StaffMember extends Model
{
    // Scramble detects and documents relationships
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
```

## Troubleshooting

### CORS Issues

If accessing Swagger UI from a different origin:

**Problem:** "Possible cross-origin (CORS) issue"

**Solution:** Place `swagger.html` in Laravel's `public/` folder so it's served from the same origin as the API.

### Missing Endpoints

**Problem:** Some endpoints don't appear in docs

**Solutions:**

1. Ensure routes are in `routes/api.php` (not `web.php`)
2. Check that controllers have proper return type hints
3. Run `php artisan route:clear` to clear route cache

### Authentication Not Working

**Problem:** "Authorize" button doesn't appear

**Solution:** Ensure SecurityScheme is configured in AppServiceProvider:

```php
Scramble::configure()
    ->withDocumentTransformers(function (OpenApi $openApi) {
        $openApi->secure(SecurityScheme::http('bearer', 'JWT'));
    });
```

## File Structure

```
hrms/
├── app/
│   └── Providers/
│       └── AppServiceProvider.php    # Scramble configuration
├── public/
│   └── swagger.html                  # Swagger UI page
├── vendor/
│   └── dedoc/
│       └── scramble/                 # Scramble package
└── composer.json                     # Package dependency
```

## Summary

| Component | Purpose | Technology |
|-----------|---------|------------|
| Scramble | Auto-generates OpenAPI spec from Laravel code | PHP Package |
| Swagger UI | Interactive API docs viewer | JavaScript (CDN) |
| OpenAPI 3.0 | Standard API specification format | JSON |

The beauty of this setup is that **documentation stays in sync with code automatically** - no need to manually update API docs when you change routes or add new endpoints. Scramble reads your code and generates fresh documentation on every request.
