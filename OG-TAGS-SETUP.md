# GitHub Pages Configuration for Open Graph Preview

This directory contains Open Graph (OG) meta tags configuration for social media sharing.

## How It Works

When you share the repository link on WhatsApp, LinkedIn, X (Twitter), Facebook, or any other social platform, the social media crawler will:

1. Access this repository
2. Read the `index.html` file
3. Extract the Open Graph meta tags
4. Display the preview image and text

## Meta Tags Included

### Open Graph Tags
- `og:title` - Title displayed in preview
- `og:description` - Description text
- `og:image` - Preview image (1200x675px - Twitter optimized)
- `og:url` - Link to repository
- `og:type` - Content type (website)

### Twitter Card Tags
- `twitter:card` - Card type (summary_large_image)
- `twitter:image` - Image for Twitter
- `twitter:title` - Title for Twitter
- `twitter:description` - Description for Twitter

### Also Includes
- Facebook OG tags
- LinkedIn meta tags
- WhatsApp preview tags
- General SEO metadata

## Enabling GitHub Pages

### Step 1: Enable Pages in Repository Settings

1. Go to: `https://github.com/ybngf/WP-Auto-Content-Pro/settings/pages`
2. Under "Source", select:
   - Branch: `main`
   - Folder: `/ (root)`
3. Click "Save"
4. Wait 1-2 minutes for GitHub to deploy

### Step 2: Verify Deployment

1. GitHub will show a green checkmark: "Your site is live at..."
2. By default, it's: `https://ybngf.github.io/WP-Auto-Content-Pro/`

### Step 3: Test the Preview

Use one of these tools to verify the Open Graph tags:

- **Twitter Card Validator**: https://cards-dev.twitter.com/validator
- **Facebook Share Debugger**: https://developers.facebook.com/tools/debug/og/object
- **LinkedIn Post Inspector**: https://www.linkedin.com/feed/?uri=urn:li:linkedInLearningCourse:9999999
- **Open Graph Checker**: https://www.opengraphcheck.com/

Paste your GitHub Pages URL (or repo URL) and verify the preview image appears.

## File Structure

```
repo-root/
├── index.html              ← Open Graph meta tags (GitHub Pages entry point)
├── screenshot-twitter.png  ← OG Image (1200x675px) - used in preview
├── screenshot-full.png     ← Full dashboard screenshot
├── screenshot-linkedin.png ← LinkedIn-optimized version
└── screenshot-instagram.png ← Instagram-optimized version
```

## What Image Gets Used?

The social media preview uses this image:
- **File**: `screenshot-twitter.png` (in root directory)
- **Size**: 1200 x 675 pixels (16:9 ratio - optimal for all platforms)
- **Format**: PNG
- **URL in meta tag**: `https://raw.githubusercontent.com/ybngf/WP-Auto-Content-Pro/main/screenshot-twitter.png`

## Social Media Preview Preview

When shared, the preview will show:

**Title**: "WP Auto Content Pro v2.0.0 - AI Content Automation"

**Description**: "Automate WordPress blog posts & distribute to Twitter, Threads, Instagram, Facebook, TikTok, LinkedIn 24/7. Powered by OpenAI, Gemini, Claude, DeepSeek."

**Image**: Professional dashboard screenshot with:
- Statistics (247 posts, 1,489 shares)
- 6 key features
- Clear GitHub call-to-action

## Testing on Each Platform

### WhatsApp
1. Copy link from `https://github.com/ybngf/WP-Auto-Content-Pro`
2. Paste in WhatsApp chat
3. Wait for preview to load
4. You should see: Title, description, and image

### LinkedIn
1. Create new post
2. Paste repository URL
3. Click "Get preview"
4. Preview will show with image

### X (Twitter)
1. Compose new tweet
2. Paste repository URL
3. Twitter will expand with preview
4. Should show summary_large_image card

### Facebook
1. Compose post
2. Paste repository URL
3. Preview will appear automatically

## If Preview Doesn't Show

### Common Issues & Fixes

**Issue**: Image doesn't appear
- **Fix**: Wait 5-10 minutes for cache to clear
- **Fix**: Use Facebook Share Debugger to force refresh: https://developers.facebook.com/tools/debug/og/object

**Issue**: Old preview still showing
- **Fix**: Don't share from GitHub directly; share from pages.github.io subdomain
- **Fix**: Clear browser cache
- **Fix**: Use incognito mode to test

**Issue**: Words are cut off
- **Fix**: Shorten title/description
- **Fix**: Verify og:image dimensions are correct (1200x675)

## Advanced: Custom Meta Tags per Platform

The current `index.html` serves all platforms well. However, you can optimize per-platform by:

1. Creating platform-specific directories:
   - `/twitter/` → optimized for Twitter
   - `/linkedin/` → optimized for LinkedIn
   - `/whatsapp/` → optimized for WhatsApp

2. Each would have different `index.html` with unique og:image

3. Share platform-specific URLs instead

(Current setup is simpler and works well for all)

## Updating the Preview Image

To use a different image as the OG preview:

1. Create new image (1200x675px minimum)
2. Save as PNG in repository root
3. Update `og:image` in `index.html`:
   ```html
   <meta property="og:image" content="https://raw.githubusercontent.com/ybngf/WP-Auto-Content-Pro/main/YOUR-NEW-IMAGE.png">
   ```
4. Also update `twitter:image`
5. Commit and push
6. Force refresh in debuggers above

## Analytics

Track how many times your preview is shared:

1. Add UTM parameters to GitHub link:
   ```
   https://github.com/ybngf/WP-Auto-Content-Pro?utm_source=twitter&utm_medium=social&utm_campaign=launch
   ```

2. View clicks in GitHub's traffic analytics

## References

- [Open Graph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/og/object)
- [LinkedIn Share Inspector](https://www.linkedin.com/news/)

---

**Last Updated**: April 7, 2026  
**Plugin**: WP Auto Content Pro v2.0.0
