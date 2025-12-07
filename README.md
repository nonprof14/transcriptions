# Transcriptions Sync

A WordPress plugin that manages musical transcriptions synced from Contentful via Make.com. Transcriptions are stored as WordPress pages with custom fields and organized by Maqam categories.

## Features

- 🎵 Store transcriptions as editable WordPress pages
- 🔄 REST API for syncing data from Contentful via Make.com
- 📊 List view with Maqam grouping via shortcode
- 📄 Beautiful single page templates with embedded PDF transcriptions
- ✏️ Fully editable in WordPress admin
- 🔐 Secure authentication via WordPress Application Passwords
- 📱 Responsive design for all devices

## Installation

### Manual Installation

1. Download the plugin folder
2. Upload the `transcriptions-sync` directory to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Transcriptions** in the admin menu to view your transcriptions

### Requirements

- WordPress 5.6 or higher (for Application Passwords)
- PHP 7.4 or higher
- HTTPS enabled (required for Application Passwords)

## Setup Guide

### 1. Generate Application Password for Make.com

To allow Make.com to sync data to your WordPress site, you need to create an Application Password:

1. Log in to your WordPress admin panel
2. Go to **Users** → **Profile**
3. Scroll down to the **Application Passwords** section
4. Enter a name for your application (e.g., "Make.com Contentful Sync")
5. Click **Add New Application Password**
6. Copy the generated password (it will only be shown once!)
7. Save this password securely - you'll need it for Make.com

**Important:** Your Application Password will look like: `xxxx xxxx xxxx xxxx xxxx xxxx`

### 2. Configure Make.com Scenario

Use these API credentials in your Make.com HTTP module:

- **Base URL:** `https://yoursite.com/wp-json/transcriptions/v1/`
- **Username:** Your WordPress username
- **Password:** The Application Password you generated above
- **Authentication Type:** Basic Auth

### 3. Add the List View to a Page

1. Create a new page or edit an existing page
2. Add the shortcode: `[transcriptions_list]`
3. Publish the page
4. The page will display a table of all transcriptions grouped by Maqam

### 4. Managing Maqam Categories

1. Go to **Pages** → **Maqams** in the WordPress admin
2. Add new Maqam categories as needed (e.g., "Rast", "Bayati", "Hijaz")
3. Maqams are hierarchical, so you can create parent/child relationships if desired

## REST API Documentation

All API endpoints require authentication via Application Passwords (Basic Auth).

### Base URL

```
https://yoursite.com/wp-json/transcriptions/v1/
```

### Authentication

Include Basic Auth headers with every request:

```
Authorization: Basic base64(username:application_password)
```

### Endpoints

#### 1. Create or Update Entry (POST)

**Endpoint:** `POST /entry`

Creates a new transcription or updates if `contentful_id` already exists (idempotent).

**Request Body:**

```json
{
  "contentful_id": "unique-contentful-id-123",
  "title": "Samai Rast",
  "composer": "Jamal Eddin Aladdin",
  "maqam": "Rast",
  "form": "Samai",
  "iqa_rhythm": "10/8",
  "pdf_url": "https://yoursite.com/wp-content/uploads/2024/01/samai-rast.pdf"
}
```

**Response (201 Created or 200 OK):**

```json
{
  "status": "created",
  "page_id": 123,
  "url": "https://yoursite.com/samai-rast/"
}
```

**Example cURL:**

```bash
curl -X POST https://yoursite.com/wp-json/transcriptions/v1/entry \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "contentful_id": "abc123",
    "title": "Samai Rast",
    "composer": "Jamal Eddin Aladdin",
    "maqam": "Rast",
    "form": "Samai",
    "iqa_rhythm": "10/8",
    "pdf_url": "https://yoursite.com/wp-content/uploads/2024/01/samai-rast.pdf"
  }'
```

#### 2. Update Entry (PUT)

**Endpoint:** `PUT /entry/{contentful_id}`

Updates an existing transcription by Contentful ID.

**Request Body:** Same as POST

**Response (200 OK):**

```json
{
  "status": "updated",
  "page_id": 123,
  "url": "https://yoursite.com/samai-rast/"
}
```

**Example cURL:**

```bash
curl -X PUT https://yoursite.com/wp-json/transcriptions/v1/entry/abc123 \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Samai Rast (Updated)",
    "composer": "Jamal Eddin Aladdin",
    "maqam": "Rast",
    "form": "Samai",
    "iqa_rhythm": "10/8",
    "pdf_url": "https://yoursite.com/wp-content/uploads/2024/01/samai-rast-v2.pdf"
  }'
```

#### 3. Get Entry (GET)

**Endpoint:** `GET /entry/{contentful_id}`

Retrieves transcription data by Contentful ID.

**Response (200 OK):**

```json
{
  "status": "success",
  "data": {
    "page_id": 123,
    "title": "Samai Rast",
    "url": "https://yoursite.com/samai-rast/",
    "composer": "Jamal Eddin Aladdin",
    "maqam": "Rast",
    "form": "Samai",
    "iqa_rhythm": "10/8",
    "pdf_url": "https://yoursite.com/wp-content/uploads/2024/01/samai-rast.pdf",
    "contentful_id": "abc123",
    "contentful_last_sync": "2024-01-15 10:30:00"
  }
}
```

**Example cURL:**

```bash
curl -X GET https://yoursite.com/wp-json/transcriptions/v1/entry/abc123 \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx"
```

#### 4. Delete Entry (DELETE)

**Endpoint:** `DELETE /entry/{contentful_id}`

Deletes a transcription by Contentful ID.

**Response (200 OK):**

```json
{
  "status": "deleted"
}
```

**Example cURL:**

```bash
curl -X DELETE https://yoursite.com/wp-json/transcriptions/v1/entry/abc123 \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx"
```

### Error Responses

**400 Bad Request:**

```json
{
  "status": "error",
  "message": "Missing required field: contentful_id"
}
```

**404 Not Found:**

```json
{
  "status": "error",
  "message": "Transcription not found"
}
```

**500 Internal Server Error:**

```json
{
  "status": "error",
  "message": "Internal server error"
}
```

## Usage Guide

### Displaying the List View

Use the `[transcriptions_list]` shortcode on any page or post:

```
[transcriptions_list]
```

This will display:
- A table with three columns: Maqam, Composer, Form
- Transcriptions grouped by Maqam category
- Each Maqam as a subheading with its entries listed below
- Clickable links to individual transcription pages

### Manually Editing Transcriptions

1. Go to **Pages** in the WordPress admin
2. Find the transcription page you want to edit
3. Click **Edit**
4. Scroll to the **Transcription Details** meta box
5. Edit any of the following fields:
   - Composer
   - Form
   - Iqa (Rhythm)
   - PDF URL (use the "Upload PDF" button to select from Media Library)
6. Edit the Maqam category in the right sidebar under **Maqams**
7. Click **Update** to save changes

**Note:** The Contentful ID is immutable and cannot be edited manually.

### Adding/Editing Maqam Categories

#### Via WordPress Admin:

1. Go to **Pages** → **Maqams**
2. To add a new Maqam:
   - Enter the name (e.g., "Rast", "Bayati")
   - Optionally add a slug and description
   - Click **Add New Maqam**
3. To edit an existing Maqam:
   - Click on the Maqam name in the list
   - Update the information
   - Click **Update**

#### Via API:

Maqams are created automatically when you send a transcription with a new Maqam name. If you send:

```json
{
  "maqam": "Hijaz"
}
```

And "Hijaz" doesn't exist, it will be created automatically.

### Uploading PDF Files

#### Method 1: Via WordPress Media Library

1. Go to **Media** → **Add New**
2. Upload your PDF file
3. After upload, click on the file to view details
4. Copy the **File URL**
5. Use this URL in the API request or paste it in the PDF URL field when editing a transcription

#### Method 2: Via Make.com

Send the full URL to the PDF file in your API request:

```json
{
  "pdf_url": "https://yoursite.com/wp-content/uploads/2024/01/transcription.pdf"
}
```

The plugin will embed this PDF on the transcription page.

### Customizing the Design

The plugin includes CSS that can be customized:

- **Frontend styles:** `assets/css/frontend.css`
- **Admin styles:** `assets/css/admin.css`

You can override these styles in your theme's CSS file or create a child theme.

## File Structure

```
transcriptions-sync/
├── transcriptions-sync.php       # Main plugin file
├── includes/
│   ├── class-database.php        # Database operations
│   ├── class-api.php             # REST API endpoints
│   ├── class-renderer.php        # Shortcode & template rendering
│   └── class-admin.php           # Admin interface
├── templates/
│   ├── list-view.php             # List view template
│   └── single-transcription.php  # Single transcription template
├── assets/
│   ├── css/
│   │   ├── admin.css             # Admin styles
│   │   └── frontend.css          # Frontend styles
│   └── js/
│       └── admin.js              # Admin JavaScript
└── README.md                      # This file
```

## Data Structure

Each transcription is stored as a WordPress page with the following custom fields:

| Field | Type | Description | Editable |
|-------|------|-------------|----------|
| `title` | Post Title | Transcription title | Yes |
| `composer` | Meta | Composer name | Yes |
| `maqam` | Taxonomy | Maqam category | Yes |
| `form` | Meta | Musical form | Yes |
| `iqa_rhythm` | Meta | Rhythmic pattern | Yes |
| `pdf_url` | Meta | URL to PDF file | Yes |
| `contentful_id` | Meta | Unique Contentful identifier | No (immutable) |
| `contentful_last_sync` | Meta | Last sync timestamp | Auto-updated |

## Troubleshooting

### API Returns 401 Unauthorized

- Verify your Application Password is correct
- Ensure you're using Basic Auth with `username:application_password`
- Check that HTTPS is enabled on your WordPress site
- Verify the user has `edit_pages` capability

### PDFs Not Displaying

- Ensure the PDF URL is accessible and valid
- Check your browser supports embedded PDFs
- Try the "Download PDF" fallback link
- Verify the PDF file exists in your Media Library

### Shortcode Shows No Results

- Verify you have published transcription pages
- Check that pages have the `contentful_id` meta field
- Ensure pages are assigned to at least one Maqam category
- Check page post status (should be "publish")

### Duplicate Entries Created

- Verify you're sending the correct `contentful_id`
- Check that the `contentful_id` is unique
- Use the POST endpoint for create/update (it's idempotent)

### Maqam Categories Not Showing

- Go to **Pages** → **Maqams** and verify categories exist
- Ensure transcription pages are assigned to a Maqam
- Check taxonomy registration is working (should see "Maqams" in sidebar when editing pages)

## WordPress Coding Standards

This plugin follows WordPress coding standards:

- ✅ Proper sanitization of all inputs
- ✅ Nonce verification for form submissions
- ✅ Capability checks for all admin actions
- ✅ Internationalization ready (translation-ready)
- ✅ Secure REST API with authentication
- ✅ XSS prevention via escaping outputs
- ✅ SQL injection prevention via WordPress APIs

## Security Features

- **Application Passwords:** Secure API authentication without exposing main password
- **Capability Checks:** Only users with `edit_pages` permission can use the API
- **Input Sanitization:** All data is sanitized before saving
- **Output Escaping:** All displayed data is escaped to prevent XSS
- **Nonce Verification:** Admin forms use nonces for CSRF protection
- **HTTPS Required:** Application Passwords require HTTPS

## Support & Contributing

For issues, questions, or contributions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review the [API Documentation](#rest-api-documentation)
3. Open an issue on the GitHub repository

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for managing musical transcriptions synced from Contentful.

---

**Version:** 1.0.0
**Requires WordPress:** 5.6+
**Requires PHP:** 7.4+
**Author:** Your Name
**License:** GPL v2 or later
