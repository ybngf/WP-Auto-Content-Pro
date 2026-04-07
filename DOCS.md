# WP Auto Content Pro - Technical Documentation

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [API Providers](#api-providers)
- [Social Media Setup](#social-media-setup)
- [Image Generation](#image-generation)
- [Scheduling & Automation](#scheduling--automation)
- [Database Schema](#database-schema)
- [Webhooks](#webhooks)
- [Troubleshooting](#troubleshooting)
- [Development](#development)

---

## Installation

### System Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher (with `json` and `curl` extensions)
- **MySQL**: 5.6+ or MariaDB equivalent
- **Web Server**: Any PHP-compatible server (Apache, Nginx, etc.)

### Step-by-Step Installation

#### 1. Install the Plugin

**Option A: Via Git**
```bash
cd wp-content/plugins/
git clone https://github.com/ybngf/WP-Auto-Content-Pro.git
cd wp-auto-content-pro
```

**Option B: Via ZIP**
1. Download latest release: https://github.com/ybngf/WP-Auto-Content-Pro/releases
2. In WordPress admin: **Plugins → Add New → Upload Plugin**
3. Select ZIP file and click "Install Now"

**Option C: Manual Upload**
1. Extract the ZIP file on your computer
2. Upload `wp-auto-content-pro` folder to `wp-content/plugins/`
3. Done!

#### 2. Activate the Plugin

1. Go to **WordPress Admin → Plugins**
2. Find **WP Auto Content Pro** in the list
3. Click **Activate**
4. You should see **"WP Auto Content"** menu in the left sidebar

#### 3. Check Database Tables

The plugin automatically creates 3 custom tables on activation:
- `wpac_topics` — Stores content topics
- `wpac_posts_log` — Records all generated posts
- `wpac_social_log` — Records all social media shares

To verify:
```sql
SELECT * FROM wp_wpac_topics;
SELECT * FROM wp_wpac_posts_log;
SELECT * FROM wp_wpac_social_log;
```

---

## Configuration

### Initial Setup (First 5 Minutes)

1. **Go to Dashboard**: Click "WP Auto Content → Dashboard" from the menu
2. **Check Permissions**: Ensure you have "Manage Options" capability
3. **Set AI Provider**: Go to "Settings → AI" and configure at least one provider
4. **Test Connection**: Click "Test Connection" button to verify API key works
5. **Create First Topic**: Go to "Topics" and add your first content topic

### Basic Settings

#### AI Configuration (Settings → AI Tab)

Each AI provider has 3 required fields:

**Provider Selection**
- Choose primary AI provider (fallback to others if primary fails)

**API Key**
- Password for the AI service
- Store securely; never share in code commits

**Model Selection**
- Different providers have different models available
- GPT-4o for OpenAI, Gemini-1.5-pro for Google, etc.

#### Content Settings (Settings → Content Tab)

| Setting | Purpose | Default |
|---------|---------|---------|
| Article Length | Size of generated content | Medium (900-1400 words) |
| Default Category | If topic has no category | Uncategorized |
| Post Status | Publish, Draft, or Pending | Publish |
| Post Author | Who creates the posts | Plugin Admin |

#### Image Settings (Settings → Image Tab)

| Setting | Purpose | Default |
|---------|---------|---------|
| Image Provider | DALL-E 3, Unsplash, or Both | Both (with fallback) |
| Image Size | 1792x1024, 1024x1024, etc. | 1792x1024 (landscape) |

#### Schedule Settings (Settings → Schedule Tab)

| Setting | Purpose | Default |
|---------|---------|---------|
| Enable Scheduling | Turn automation on/off | Disabled |
| Posts per Day | How many to generate | 1 |
| Posting Window | Time range (e.g., 08:00-20:00) | 00:00-23:59 (full day) |
| Timezone | Your server timezone | UTC |

#### Social Media (Settings → Social Media Tab)

For each platform, enter:
- **Enable**: Checkbox to turn on/off
- **Credentials**: API keys, access tokens, user IDs (specific per platform)

See [Social Media Setup](#social-media-setup) section below for details.

#### Advanced Settings (Settings → Advanced Tab)

| Setting | Purpose | Default |
|---------|---------|---------|
| Debug Mode | Log detailed information | Off |
| Webhook URL | Where to send notifications | Empty |
| Webhook Secret | HMAC signature key | Empty |
| Delete Data on Uninstall | Clean removal | On |

### Environment Configuration

For increased security, you can store API keys in `wp-config.php`:

```php
// In wp-config.php
define('WPAC_OPENAI_KEY', 'sk-...');
define('WPAC_GEMINI_KEY', 'AIza...');
define('WPAC_CLAUDE_KEY', 'sk-ant-...');
define('WPAC_DEEPSEEK_KEY', 'sk-...');
```

Then in plugin settings, leave those fields empty and they'll use environment values.

---

## API Providers

### OpenAI (GPT-4o Recommended)

**Cost**: ~$0.003 per 1K input tokens

**Available Models**:
- `gpt-4o` — Latest, best quality, balanced cost
- `gpt-4o-mini` — Faster, cheaper, still high quality
- `gpt-4-turbo` — Older model, less recommended
- `gpt-4` — Legacy, not recommended

**Setup**:
1. Visit https://platform.openai.com/account/api-keys
2. Create new secret key
3. Copy and paste into plugin settings → AI → OpenAI API Key
4. Set model (recommend: gpt-4o)
5. Click "Test Connection"

**Image Generation**:
- OpenAI also handles DALL-E 3 image generation
- Requires same API key
- Cost: ~$0.04 per image at 1792x1024

**Pricing Tip**: Create separate API keys for production (with usage limits) vs. testing.

---

### Google Gemini

**Cost**: ~$0.00075 per 1K input tokens (cheapest option)

**Available Models**:
- `gemini-1.5-pro` (recommended)
- `gemini-1.5-flash` (faster, cheaper)
- `gemini-pro` (older)

**Setup**:
1. Visit https://aistudio.google.com/app/apikey
2. Click "Create API Key"
3. Copy into plugin settings → AI → Gemini API Key
4. Set model
5. Test connection

**Advantages**:
- Cheapest option
- Good quality
- No rate limiting for free tier
- 30K requests/day free tier available

---

### Anthropic Claude

**Cost**: ~$0.003 per 1K input tokens

**Available Models**:
- `claude-opus-4-6` (best quality)
- `claude-sonnet-4-6` (balanced)
- `claude-3-opus-20240229` (older)
- `claude-3-sonnet-20240229` (older)

**Setup**:
1. Visit https://console.anthropic.com/settings/keys
2. Create API key
3. Copy to Anthropic API Key field
4. Select model
5. Test

**Why Use Claude**:
- Excellent writing quality
- Strong reasoning
- Good for technical content
- Fallback option if OpenAI is down

---

### DeepSeek

**Cost**: ~$0.00015 per 1K input tokens (very cheap)

**Available Models**:
- `deepseek-chat` (general purpose)
- `deepseek-coder` (technical/code content)

**Setup**:
1. Visit https://platform.deepseek.com/api_keys
2. Create API key
3. Paste into DeepSeek field
4. Select model
5. Test

**Best Use**:
- Cost optimization
- High-volume content
- Fallback when budgets are tight
- Testing and development

---

### Fallback System Explained

When you configure multiple providers:

```
User generates content
    ↓
Try Primary Provider (e.g., OpenAI)
    ↓ (If fails or times out)
Try Secondary Provider (e.g., Gemini)
    ↓ (If fails)
Try Third Provider (e.g., Claude)
    ↓ (If fails)
Try Fourth Provider (e.g., DeepSeek)
    ↓ (If all fail)
LOG ERROR - Manual retry needed
```

**Benefits**:
- Automatic redundancy
- No failed posts
- Cost optimization
- Prevents downtime

---

## Social Media Setup

### Twitter / X

**API Type**: OAuth 1.0a (User Context)

**Get Credentials**:
1. Visit https://developer.twitter.com/en/portal/dashboard
2. Create new App (or use existing)
3. Go to **Settings → Keys and tokens**
4. Under "Keys" section:
   - Copy **API Key** (Consumer Key)
   - Copy **API Secret** (Consumer Secret)
5. Under "Tokens" section:
   - Copy **Access Token**
   - Copy **Access Token Secret**

**Enter in Settings**:
- `API Key (Consumer Key)` field
- `API Secret (Consumer Secret)` field
- `Access Token` field
- `Access Token Secret` field

**Post Template**:
- Default: `{title}\n\nRead more: {url}\n\n{hashtags}`
- Available variables: `{title}`, `{url}`, `{hashtags}`

**Limits**:
- 300 posts per 3-hour window (v2 API)
- Images must be <5MB
- Plugin adds 1-second delay between posts to prevent rate limiting

---

### Threads (Meta)

**API Type**: Meta Graph API

**Requirements**:
- Meta Business Account
- Instagram Professional Account
- Threads Account connected to Instagram

**Get Credentials**:
1. Visit https://developers.facebook.com/apps
2. Create/select your App
3. Add **Threads Graph API** product
4. Go to **Settings → Basic** and copy **App ID** and **App Secret**
5. Generate Access Token with scopes:
   - `threads_basic_info`
   - `threads_content_publish`
   - `threads_manage_metadata`
6. Copy Access Token into plugin

**What Gets Posted**:
- Text from social caption
- Featured image if available
- Link to post URL

**Limits**:
- 200 posts per day
- 10-minute processing time per post

---

### Instagram (Business)

**API Type**: Meta Graph API v19.0

**Requirements**:
- Business/Creator Instagram Account
- NOT personal account
- Meta Business Manager setup

**Get Credentials**:
1. https://business.facebook.com → Settings → Users
2. Create system user with required permissions
3. In Developer Platform, create access token
4. Get **Instagram Business Account ID** from:
   - Account → Settings → Basic Info → Instagram Account ID

**Enter**:
- `Page Access Token` field
- `Instagram Business Account ID` field

**What Gets Posted**:
- Caption text
- Featured image (REQUIRED - fails without image)
- Hashtags in caption

**Image Requirements**:
- Format: JPG, PNG
- Min 320x320px
- Max 1920px width
- No visible borders/frame

---

### Facebook Pages

**API Type**: Meta Graph API

**Get Credentials**:
1. https://developers.facebook.com/apps
2. Generate Access Token with scopes:
   - `pages_manage_posts`
   - `pages_manage_engagement`
3. Get **Page ID**:
   - Facebook Page → About → Page ID (or use https://lookup-id.com/)

**Enter**:
- `Page Access Token`
- `Page ID`

**What Gets Posted**:
- Title and excerpt as main text
- Featured image (if available)
- Link preview auto-generated

**Posting Options**:
- Link posts (with preview)
- Photo posts (with caption)
- Video posts (not currently supported)

---

### TikTok

**API Type**: Content Posting API v2

**Get Credentials**:
1. Visit https://developers.tiktok.com/
2. Create Business Account
3. Apply for Content Posting API access
4. Create OAuth app for your domain
5. Authenticate and get Access Token

**Enter**:
- `Access Token` in settings

**Limitations**:
- Requires business account approval
- 5-10 business day review period
- One video per 3-hour window
- Videos must be 15sec - 10min

**Output**:
- Creates TikTok video using featured image + text overlay
- Caption in description
- Watermark added by TikTok

---

### LinkedIn

**API Type**: UGC Posts API v2

**Get Credentials**:
1. Visit https://www.linkedin.com/developers/
2. Create new app in your organization
3. Request access to **UGC Posts** product
4. Generate OAuth token with scopes:
   - `w_member_social`
   - `w_organization_social`

**Enter**:
- `Access Token`
- `Author URN` (auto-detected, or manually enter as `urn:li:person:123456789`)

**What Gets Posted**:
- Title
- Article URL with preview
- Featured image
- Description

**Best For**:
- B2B content
- Articles and thought leadership
- Professional audience
- Long-form content

---

## Image Generation

### DALL-E 3 (Primary - Requires OpenAI Key)

**Characteristics**:
- AI-generated images
- Custom prompts from article content
- High quality
- Consistent style
- 60-second generation time

**Configuration**:
- Model: DALL-E 3
- Sizes: 1792x1024, 1024x1024, 1024x1792
- Quality: Standard (HD not available in this API version)

**Cost**: ~$0.04 per image (1792x1024)

**Example Generated Prompt**:
```
Create a professional blog header image about "10 WordPress Security Tips". 
Style: modern, clean, professional. Color scheme: blue and white.
```

---

### Unsplash (Secondary - Free 50req/hour)

**Characteristics**:
- Real photography
- Free, high-quality library
- Searches by article topic
- No AI involved

**Setup**:
1. Visit https://unsplash.com/developers
2. Create developer account
3. Create Application
4. Copy **Access Key** from app details
5. Enter in plugin settings → Image → Unsplash API Key

**How It Works**:
- Plugin extracts keywords from article title
- Searches Unsplash library
- Returns best matching image
- Falls back to Picsum if no results

**Advantages**:
- Real photos (often better than AI)
- Photographers get credited
- Free
- Large, curated database

**Limits**:
- 50 requests/hour free tier
- Requires crediting photographer (plugin auto-credits)

---

### Picsum (Tertiary - Fallback, Always Works)

**Characteristics**:
- Random images
- No API key required
- Always available
- Free
- Generic/stock style

**How It Works**:
- Simple URL: `https://picsum.photos/1792/1024`
- Plugin automatically resizes and optimizes
- No setup needed

**When Used**:
- DALL-E fails or is disabled
- Unsplash search returns no results
- Unsplash API key invalid
- Fallback chain always ensures images

**Advantages**:
- Zero configuration
- 100% uptime (backed by Picsum.photos)
- Perfect for fallback
- Randomness prevents repetition

---

## Scheduling & Automation

### How WP-Cron Works

The plugin uses **WordPress Cron** (WP-Cron), which triggers on page visits:

```
Page Visit → WP checks scheduled tasks → Plugin checks topics
         → If topic due for scheduling, creates new post → Done
```

**Advantages**:
- No server setup needed
- Works on shared hosting
- Automatic

**Disadvantages**:
- Depends on site traffic
- Can be delayed if site is quiet
- Not precise for off-peak hours

### Enable Real Server Cron (Recommended)

For more reliable scheduling, use system cron:

#### Linux/Unix Servers

**1. Disable WP-Cron**
Add to `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

**2. Add to Crontab**
Run: `crontab -e`
Add line:
```bash
*/30 * * * * wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

This runs every 30 minutes.

#### Windows Servers

Use **Task Scheduler**:
1. Open Task Scheduler
2. Create Basic Task
3. Set to run every 30 minutes
4. Action: Start Program
5. Program: `c:\curl\curl.exe`
6. Arguments: `https://yourdomain.com/wp-cron.php?doing_wp_cron`

### Topic Scheduling

Each topic has a **Frequency** setting:

| Frequency | Description | Cron Interval |
|-----------|-------------|---------------|
| Hourly | Every hour | 1 hour |
| Every 2h | Every 2 hours | 2 hours |
| Every 6h | Twice daily | 6 hours |
| Daily | Once per day | 24 hours |
| Weekly | Once per week | 7 days |

**How Priority is Calculated**:
1. System looks at each enabled topic
2. Calculates time since last post for each
3. If `(current_time - last_post_time) >= frequency`, topic is due
4. Among due topics, picks one with:
   - Longest time since last post
   - If tied, picks randomly

### Posting Window

**Posting Window** restricts when content is published:

**Example**: Window 08:00 - 20:00
- Posts generated: YES
- Posts published: 08:00 - 20:00
- Posts marked for later: YES (auto-published when window opens)

**Timezone**: 
- Respects WordPress timezone setting
- Configurable in Settings → Schedule

**Use Cases**:
- Only publish during business hours
- Avoid middle-of-night posts
- Align with audience activity

---

## Database Schema

### wpac_topics Table

```sql
CREATE TABLE {$wpdb->prefix}wpac_topics (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  topic VARCHAR(255) NOT NULL,
  type ENUM('article', 'tutorial') DEFAULT 'article',
  frequency VARCHAR(20) DEFAULT 'daily',
  category_id BIGINT UNSIGNED,
  enabled TINYINT DEFAULT 1,
  last_generated DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_topic (topic)
);
```

**Columns**:
- `topic`: What to write about
- `type`: Article or Tutorial format
- `frequency`: How often to generate
- `category_id`: WordPress category (optional)
- `enabled`: Is this topic active
- `last_generated`: When was last post created
- `created_at` / `updated_at`: Timestamps

---

### wpac_posts_log Table

```sql
CREATE TABLE {$wpdb->prefix}wpac_posts_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED,
  topic_id BIGINT UNSIGNED,
  ai_provider VARCHAR(50),
  status VARCHAR(20) DEFAULT 'success',
  error_message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (topic_id) REFERENCES {$wpdb->prefix}wpac_topics(id)
);
```

**Columns**:
- `post_id`: WordPress post ID generated
- `topic_id`: Which topic generated it
- `ai_provider`: Which AI (openai, gemini, claude, deepseek)
- `status`: success, error, failed_retry
- `error_message`: If failed, why

---

### wpac_social_log Table

```sql
CREATE TABLE {$wpdb->prefix}wpac_social_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED,
  platform VARCHAR(50),
  status VARCHAR(20) DEFAULT 'success',
  platform_post_id VARCHAR(255),
  platform_url TEXT,
  error_message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES {$wpdb->posts}(ID)
);
```

**Columns**:
- `post_id`: Which WordPress post was shared
- `platform`: twitter, threads, instagram, facebook, tiktok, linkedin
- `status`: success or error
- `platform_post_id`: ID on the social platform
- `platform_url`: Direct link to post on platform
- `error_message`: If failed

---

## Webhooks

### Webhook Events

Currently supports: `post_published`

### Webhook Payload

```json
{
  "event": "post_published",
  "timestamp": "2024-01-15T10:30:00-03:00",
  "post_id": 123,
  "post_title": "Amazing Article Title",
  "post_url": "https://example.com/amazing-article/",
  "post_excerpt": "Excerpt of the post...",
  "provider": "openai",
  "site_url": "https://example.com",
  "topic": "WordPress Tips"
}
```

### HTTP Headers

```
POST /webhook-endpoint HTTP/1.1
Content-Type: application/json
X-WPAC-Event: post_published
User-Agent: WPAutoContentPro/2.0.0
X-WPAC-Signature: sha256=abcd1234...
```

### HMAC Signature Verification

If you set a **Webhook Secret** in plugin settings:

**Generation**:
```php
$signature = hash_hmac(
  'sha256',
  json_encode($payload),
  'your-webhook-secret'
);
```

**Verification (in your webhook handler)**:
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WPAC_SIGNATURE'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, 'your-webhook-secret');

if ($signature === $expected) {
  // Valid webhook
} else {
  // Invalid or tampered
  http_response_code(401);
  exit;
}
```

---

## Troubleshooting

### Post Generation Fails

**Check these in order**:

1. **AI Provider Connection**
   - Go to Settings → AI
   - Click "Test Connection" button
   - Verify API key is correct and not expired
   - Check account has remaining credits

2. **Topic Configuration**
   - Topic must be enabled
   - Topic shouldn't have an error in previous attempt
   - Click "Generate Now" button for instant test

3. **Check Logs**
   - Go to Logs page
   - Look for error messages
   - Red entries show what went wrong

4. **Check Database**
   - Verify `wp_wpac_topics` table exists
   - Run: `SELECT COUNT(*) FROM wp_wpac_topics WHERE enabled=1;`
   - If 0, no topics enabled

5. **Check Cron**
   - Add `define('WP_DEBUG_LOG', true);` to `wp-config.php`
   - Look in `/wp-content/debug.log` for cron execution
   - Search for "wpac" or "wp_schedule_event"

### Social Media Not Posting

**For Twitter**:
- Verify OAuth tokens not expired (regenerate periodically)
- Check Developer Account suspended status at Twitter
- Ensure featured image exists (some platforms require it)
- Check rate limits

**For Instagram**:
- Verify Business Account (not personal)
- Check Instagram/Facebook hasn't changed required scopes
- Ensure featured image < 8MB
- Verify Business Instagram account is linked to Meta Business

**For All Platforms**:
- Go to Settings → Social Media
- Click "Test Connection" for each platform
- If red, credentials are invalid
- Check Logs page → Social Media Log tab

### Images Not Generating

```
DALL-E not loading?
↓
Check OpenAI API key valid
↓
Try Unsplash:
  - Verify Unsplash API key in settings
  - Check rate limits (50/hour)
↓
Picsum always works:
  - No setup needed
  - Free tier always available
```

### Database or Table Issues

**Symptoms:**
- "Table doesn't exist" errors
- Posts/logs not saving

**Fix**:
1. Go to Settings → Advanced
2. Click "Reset Database Tables"
3. Wait for confirmation message
4. Try generating post again

---

## Development

### File Structure

```
wp-auto-content-pro/
├── wp-auto-content-pro.php       # Main plugin file
├── admin/
│   ├── class-wpac-admin.php      # Admin handling & AJAX
│   └── views/
│       ├── dashboard.php
│       ├── settings.php
│       ├── topics.php
│       └── logs.php
├── includes/
│   ├── class-wpac-ai-generator.php           # AI calls
│   ├── class-wpac-database.php               # DB access
│   ├── class-wpac-post-creator.php           # Create posts
│   ├── class-wpac-scheduler.php              # Cron & automation
│   ├── class-wpac-social-media.php           # Social orchestrator
│   └── social/
│       ├── class-wpac-twitter.php
│       ├── class-wpac-threads.php
│       ├── class-wpac-instagram.php
│       ├── class-wpac-facebook.php
│       ├── class-wpac-tiktok.php
│       └── class-wpac-linkedin.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
├── uninstall.php
├── LICENSE
├── CHANGELOG.md
└── README.md
```

### Coding Standards

- **WordPress Coding Standards**
- **OOP**: All core logic in classes
- **Security**: nonces, sanitization, escaping
- **Localization**: All UI strings use `__()` / `_e()`
- **PHP**: 7.4+ compatible

### Adding New AI Provider

1. Create `includes/ai/class-wpac-[provider].php`
2. Extend base class with `generate_content()` and `generate_image()` methods
3. Register in `class-wpac-ai-generator.php`
4. Add settings in `admin/views/settings.php`

### Adding New Social Platform

1. Create `includes/social/class-wpac-[platform].php`
2. Implement `publish()` and `get_profile_info()` methods
3. Register in `class-wpac-social-media.php` `$platforms` array
4. Add settings UI in `settings.php`

---

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [AI Provider APIs](https://platform.openai.com/docs/)
- [GitHub Issues](https://github.com/ybngf/WP-Auto-Content-Pro/issues)
