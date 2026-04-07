# Changelog

All notable changes to WP Auto Content Pro will be documented in this file.

## [2.0.0] - 2024

### Added
- Support for 4 AI providers: OpenAI GPT-4o, Google Gemini, Anthropic Claude, DeepSeek
- Automatic fallback system between AI providers
- Retry logic (up to 2 attempts) for content generation
- 6 social media integrations: Twitter/X, Threads, Instagram, Facebook, TikTok, LinkedIn
- Rate limiting (1s delay) between social media posts
- 3 image sources with fallback chain: DALL-E 3 → Unsplash → Picsum
- MIME-type detection for downloaded images
- 10MB file size validation for image downloads
- Randomized filenames for sideloaded images
- Alt text automatically set on featured images
- 30 languages supported for content generation
- SEO title compatibility with Yoast SEO and RankMath
- Generation timestamp metadata on posts
- Webhook notifications with HMAC SHA-256 signature support
- Webhook URL validation and User-Agent header
- CSV import with file type and size validation (max 2MB)
- Database version tracking with upgrade detection
- Utility methods: `get_log_count()`, `get_success_rate()`, `needs_upgrade()`
- Complete data cleanup on uninstall (tables, options, transients, post meta, cron)
- Dashboard quick link in plugins list
- Plugin row meta links (Settings, Support)
- Debug mode with detailed error logging
- Comprehensive README in Portuguese and English

### Changed
- Plugin author updated to Autometa (autometa.com.br)
- Version bumped to 2.0.0
- Minimum requirements: PHP 7.4+, WordPress 5.8+
- Improved image sideload with 60s download timeout
- Refactored image fallback from embedded to clean 3-tier chain
- Unsplash download returns WP_Error on failure instead of silent fallthrough
- Enhanced webhook payload with `site_url` field

### Fixed
- Claude API test now uses configured model instead of hardcoded value
- Orphaned duplicate code in scheduler class cleaned up

## [1.0.0]

### Added
- Initial release
