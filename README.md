# Transcriptions Sync

A WordPress plugin that syncs musical transcriptions from Contentful to WordPress via Make.com. Creates individual transcription pages with embedded PDF viewer, displays filterable listings by Maqam/Composer/Form, and supports optional content sections (About, Text, Analysis).

## Features

### Core Features
- Automatic page creation for each transcription
- Custom REST API endpoints for Contentful integration
- PDF viewer with page navigation (PDF.js integration)
- Filterable listings page (Maqam, Composer, Form)
- Responsive design (mobile and desktop)
- Maqam taxonomy for categorization
- WordPress admin interface for manual editing
- Idempotent API (updates instead of duplicating)

### Optional Content Sections
- **About section** - Centered text, no heading, appears after composer
- **Text section** - Centered, supports Arabic/RTL text with heading
- **Translation section** - Left-aligned English translation with heading
- **Analysis section** - Left-aligned commentary with heading

### Sorting & Organization
- Composers sorted by last name
- Alphabetical sorting within categories
- Mobile-friendly dropdown filters

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

## Data Fields

### Required Fields
| Field | Type | Description |
|-------|------|-------------|
| `contentful_id` | string | Unique Contentful identifier |
| `title` | string | Transcription title |

### Optional Fields
| Field | Type | Description |
|-------|------|-------------|
| `composer` | string | Composer full name |
| `maqam` | string | Maqam category |
| `form` | string | Musical form (Samai, Longa, etc.) |
| `iqa_rhythm` | string | Rhythmic pattern |
| `pdf_url` | string | URL to PDF file |
| `about` | text | Optional description (displayed centered, no heading) |
| `text` | text | Optional lyrics/text (often Arabic, displayed centered) |
| `translation` | text | Optional English translation (displayed left-aligned) |
| `analysis` | text | Optional musical analysis (displayed left-aligned) |

**Note:** All fields except `contentful_id` and `title` are optional. Sections only appear on the page if data is provided.

## Setup Guide

### 1. Generate Application Password for Make.com

1. Log in to your WordPress admin panel
2. Go to **Users** → **Profile**
3. Scroll down to the **Application Passwords** section
4. Enter a name for your application (e.g., "Make.com Contentful Sync")
5. Click **Add New Application Password**
6. Copy the generated password (it will only be shown once!)
7. Save this password securely

### 2. Configure Make.com Scenario

Use these API credentials in your Make.com HTTP module:

- **Base URL:** `https://yoursite.com/wp-json/transcriptions/v1/`
- **Username:** Your WordPress username
- **Password:** The Application Password you generated
- **Authentication Type:** Basic Auth

### 3. Add the List View to a Page

1. Create a new page or edit an existing page
2. Add the shortcode: `[transcriptions_list]`
3. Publish the page
4. The page will display a filterable list of all transcriptions

## REST API Documentation

All API endpoints require authentication via Application Passwords (Basic Auth).

### Base URL

```
https://yoursite.com/wp-json/transcriptions/v1/
```

### Authentication

```
Authorization: Basic base64(username:application_password)
```

### Create or Update Entry (POST)

**Endpoint:** `POST /entry`

Creates a new transcription or updates if `contentful_id` already exists.

**Full Request Body Example:**

```json
{
  "contentful_id": "abc123xyz",
  "title": "Sama'i Ajam",
  "composer": "Wanees Wartanian",
  "maqam": "Ajam",
  "form": "Sama'i",
  "iqa_rhythm": "Sama'i Thaqil 10/8",
  "pdf_url": "https://yoursite.com/wp-content/uploads/2025/11/samai-ajam.pdf",
  "about": "This composition represents the classical Syrian Sama'i tradition.",
  "text": "سماعي عجم\nوانيس ورتانيان",
  "translation": "Sama'i Ajam\nBy Wanees Wartanian",
  "analysis": "The piece follows the traditional Sama'i structure with four Khanas and a Taslim that returns between each section."
}
```

**Minimum Required Request Body:**

```json
{
  "contentful_id": "abc123xyz",
  "title": "Sama'i Ajam"
}
```

**Response (201 Created or 200 Updated):**

```json
{
  "status": "created",
  "page_id": 123,
  "url": "https://yoursite.com/samai-ajam/"
}
```

### Update Entry (PUT)

**Endpoint:** `PUT /entry/{contentful_id}`

Updates an existing transcription by Contentful ID.

**Behavior:**
- Overwrites all provided fields
- Empty/omitted fields will not display their sections
- Use this for syncing updates from Contentful

### Get Entry (GET)

**Endpoint:** `GET /entry/{contentful_id}`

**Response:**

```json
{
  "status": "success",
  "data": {
    "page_id": 123,
    "title": "Sama'i Ajam",
    "url": "https://yoursite.com/samai-ajam/",
    "composer": "Wanees Wartanian",
    "maqam": "Ajam",
    "form": "Sama'i",
    "iqa_rhythm": "Sama'i Thaqil 10/8",
    "pdf_url": "https://yoursite.com/wp-content/uploads/2025/11/samai-ajam.pdf",
    "about": "This composition...",
    "text": "سماعي عجم...",
    "translation": "Sama'i Ajam...",
    "analysis": "The piece follows...",
    "contentful_id": "abc123xyz",
    "contentful_last_sync": "2025-12-07 10:30:00"
  }
}
```

### Delete Entry (DELETE)

**Endpoint:** `DELETE /entry/{contentful_id}`

**Response:**

```json
{
  "status": "deleted"
}
```

### Error Responses

**400 Bad Request:**
```json
{
  "code": "missing_field",
  "message": "contentful_id is required",
  "data": { "status": 400 }
}
```

**404 Not Found:**
```json
{
  "status": "error",
  "message": "Transcription not found"
}
```

## Single Page Layout

```
┌─────────────────────────────────────┐
│         Title (H1, centered)        │
│       Composer (H2, centered)       │
├─────────────────────────────────────┤
│  About text (if provided)           │
│  Centered, no heading, thin font    │
├─────────────────────────────────────┤
│ Maqam                               │
│ ─────────────────────────────────   │
│ Ajam                                │
├─────────────────────────────────────┤
│ Iqa (Rhythm)                        │
│ ─────────────────────────────────   │
│ Sama'i Thaqil 10/8                  │
├─────────────────────────────────────┤
│ Transcription                       │
│ ─────────────────────────────────   │
│ [PDF Viewer with Navigation]        │
│ [Previous] [Next]                   │
│ [Download PDF]                      │
├─────────────────────────────────────┤
│ Text (if provided)                  │
│ ─────────────────────────────────   │
│ Arabic/Syrian text centered         │
├─────────────────────────────────────┤
│ Translation (if provided)           │
│ ─────────────────────────────────   │
│ Left-aligned English translation    │
├─────────────────────────────────────┤
│ Analysis (if provided)              │
│ ─────────────────────────────────   │
│ Left-aligned analysis text          │
└─────────────────────────────────────┘
```

## Shortcode Usage

**Main Listings Page:**

Add this shortcode to any WordPress page:

```
[transcriptions_list]
```

**Features:**
- Desktop: Three-column filter buttons (Maqam | Composer | Form)
- Mobile: Single column with dropdown filter
- Clickable filter buttons to sort by Maqam, Composer, or Form
- Composers sorted by last name (e.g., Ajjan, Darwish, Farran)

## WordPress Admin Usage

### Editing Transcriptions

1. Go to **Pages** in WordPress admin
2. Find your transcription page
3. Edit the following custom fields via meta boxes:
   - Composer
   - Form
   - Iqa (Rhythm)
   - PDF URL
   - About (optional)
   - Text (optional, supports Arabic)
   - Translation (optional)
   - Analysis (optional)
4. Edit the Maqam category in the right sidebar
5. Click **Update** to save

### Managing Maqam Categories

1. Go to **Pages** → **Maqams**
2. Add/edit/delete Maqam categories as needed
3. Assign transcriptions to Maqam categories

## File Structure

```
transcriptions-sync/
├── transcriptions-sync.php          # Main plugin file
├── README.md                         # This file
├── includes/
│   ├── class-database.php           # Page creation/updates
│   ├── class-api.php                # REST API endpoints
│   ├── class-renderer.php           # Shortcode logic
│   └── class-admin.php              # Meta boxes, admin UI
├── templates/
│   ├── list-view.php                # Main listings page
│   └── single-transcription.php     # Individual transcription page
└── assets/
    ├── css/
    │   ├── admin.css                # Admin styles
    │   └── frontend.css             # Public-facing styles
    └── js/
        ├── frontend.js              # Filter logic
        ├── admin.js                 # PDF uploader
        └── pdf-handler.js           # PDF.js integration
```

## Troubleshooting

### API Returns 401 Unauthorized
- Verify your Application Password is correct
- Ensure you're using Basic Auth with `username:application_password`
- Check that HTTPS is enabled
- Verify the user has `edit_pages` capability

### PDF Not Loading on First Visit
- Caused by some theme AJAX navigation
- Plugin forces full page reload on transcription links
- PDF loads from CDN (cloudflare)

### Arabic Text Not Displaying
- Ensure UTF-8 encoding in database
- Plugin supports RTL (right-to-left) text automatically
- Use the Text field for Arabic content

### Sections Not Appearing
- Sections only appear if data is provided
- Check the field is not empty in admin or API request
- Verify data was saved correctly

### Composer Sorting
- Composers are sorted by last name (last word in the name)
- Example order: Ajjan, Darwish, Farran (not Ali Darwish, Ali Farran, Mahmoud Ajjan)

## Technical Notes

### PDF Viewer
- Uses PDF.js 3.11.174
- Worker loaded from CDN
- Page-by-page navigation
- Responsive canvas rendering
- High-DPI support for retina displays

### Filtering
- Client-side JavaScript (no page reload)
- Three filter modes: Maqam, Composer, Form
- Composer sorting by last name
- Mobile dropdown interface

### Data Storage
- Pages stored in WordPress posts table (type: 'page')
- Custom fields stored in post meta
- Maqam stored as custom taxonomy
- Contentful ID used for idempotent updates

## Security Features

- **Application Passwords:** Secure API authentication
- **Capability Checks:** Only `edit_pages` users can use API
- **Input Sanitization:** All data sanitized before saving
- **Output Escaping:** All displayed data escaped (XSS prevention)
- **Nonce Verification:** Admin forms use nonces (CSRF protection)
- **HTTPS Required:** Application Passwords require HTTPS

## License

This plugin is licensed under the GPL v2 or later.

---

**Version:** 1.2.0
**Requires WordPress:** 5.6+
**Requires PHP:** 7.4+
**License:** GPL v2 or later
