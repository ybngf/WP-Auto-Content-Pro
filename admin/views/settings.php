<?php
/**
 * Admin Settings view.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$categories = get_categories( array( 'hide_empty' => false ) );
$timezones  = DateTimeZone::listIdentifiers();

$platforms = array(
    'twitter'   => array( 'label' => 'Twitter / X',   'icon' => '&#120143;' ),
    'threads'   => array( 'label' => 'Threads',        'icon' => '&#9679;'   ),
    'instagram' => array( 'label' => 'Instagram',      'icon' => '&#128247;' ),
    'facebook'  => array( 'label' => 'Facebook',       'icon' => 'f'         ),
    'tiktok'    => array( 'label' => 'TikTok',         'icon' => '&#9836;'   ),
    'linkedin'  => array( 'label' => 'LinkedIn',       'icon' => 'in'        ),
);
?>
<div class="wrap wpac-wrap">
    <div class="wpac-page-header">
        <h1 class="wpac-page-title"><?php esc_html_e( 'Settings', 'wp-auto-content-pro' ); ?></h1>
    </div>

    <div id="wpac-toast-container"></div>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="wpac-notice wpac-notice-success"><p><?php esc_html_e( 'Settings saved successfully.', 'wp-auto-content-pro' ); ?></p></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="wpac-tabs">
        <nav class="wpac-tab-nav">
            <button class="wpac-tab-btn wpac-tab-active" data-tab="ai-settings">
                &#129504; <?php esc_html_e( 'AI Settings', 'wp-auto-content-pro' ); ?>
            </button>
            <button class="wpac-tab-btn" data-tab="social-media">
                &#128279; <?php esc_html_e( 'Social Media', 'wp-auto-content-pro' ); ?>
            </button>
            <button class="wpac-tab-btn" data-tab="schedule">
                &#128336; <?php esc_html_e( 'Schedule', 'wp-auto-content-pro' ); ?>
            </button>
            <button class="wpac-tab-btn" data-tab="advanced">
                &#9881; <?php esc_html_e( 'Advanced', 'wp-auto-content-pro' ); ?>
            </button>
        </nav>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wpac-settings-form">
        <?php wp_nonce_field( 'wpac_save_settings', 'wpac_settings_nonce' ); ?>
        <input type="hidden" name="action" value="wpac_save_settings">

        <!-- Tab: AI Settings -->
        <div class="wpac-tab-content wpac-tab-active" id="tab-ai-settings">

            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'AI Provider', 'wp-auto-content-pro' ); ?></h2>
                <p class="wpac-section-desc"><?php esc_html_e( 'Select your primary AI provider. Others will be used as fallback if the primary fails.', 'wp-auto-content-pro' ); ?></p>

                <div class="wpac-provider-grid">
                    <?php
                    $providers = array(
                        'openai'   => array( 'label' => 'OpenAI GPT-4o',       'icon' => '&#129504;' ),
                        'gemini'   => array( 'label' => 'Google Gemini',        'icon' => '&#127774;' ),
                        'claude'   => array( 'label' => 'Anthropic Claude',     'icon' => '&#129302;' ),
                        'deepseek' => array( 'label' => 'DeepSeek',             'icon' => '&#128269;' ),
                    );
                    $current_provider = get_option( 'wpac_ai_provider', 'openai' );
                    foreach ( $providers as $slug => $info ) :
                    ?>
                    <label class="wpac-provider-card <?php echo $current_provider === $slug ? 'wpac-provider-selected' : ''; ?>">
                        <input type="radio" name="wpac_ai_provider" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $current_provider, $slug ); ?>>
                        <span class="wpac-provider-icon"><?php echo $info['icon']; ?></span>
                        <span class="wpac-provider-label"><?php echo esc_html( $info['label'] ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- OpenAI Settings -->
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'OpenAI Settings', 'wp-auto-content-pro' ); ?></h2>
                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'API Key', 'wp-auto-content-pro' ); ?></label>
                        <input type="password" name="wpac_openai_api_key" value="<?php echo esc_attr( get_option( 'wpac_openai_api_key', '' ) ); ?>" class="wpac-form-control" placeholder="sk-...">
                        <div class="wpac-field-actions">
                            <button type="button" class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-ai" data-provider="openai">
                                <?php esc_html_e( 'Test Connection', 'wp-auto-content-pro' ); ?>
                            </button>
                        </div>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'GPT Model', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_openai_model" class="wpac-form-control">
                            <?php foreach ( array( 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4' ) as $m ) : ?>
                                <option value="<?php echo esc_attr( $m ); ?>" <?php selected( get_option( 'wpac_openai_model', 'gpt-4o' ), $m ); ?>><?php echo esc_html( $m ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Gemini Settings -->
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'Google Gemini Settings', 'wp-auto-content-pro' ); ?></h2>
                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'API Key', 'wp-auto-content-pro' ); ?></label>
                        <input type="password" name="wpac_gemini_api_key" value="<?php echo esc_attr( get_option( 'wpac_gemini_api_key', '' ) ); ?>" class="wpac-form-control" placeholder="AIza...">
                        <div class="wpac-field-actions">
                            <button type="button" class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-ai" data-provider="gemini">
                                <?php esc_html_e( 'Test Connection', 'wp-auto-content-pro' ); ?>
                            </button>
                        </div>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Gemini Model', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_gemini_model" class="wpac-form-control">
                            <?php foreach ( array( 'gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro' ) as $m ) : ?>
                                <option value="<?php echo esc_attr( $m ); ?>" <?php selected( get_option( 'wpac_gemini_model', 'gemini-1.5-pro' ), $m ); ?>><?php echo esc_html( $m ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Claude Settings -->
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'Anthropic Claude Settings', 'wp-auto-content-pro' ); ?></h2>
                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'API Key', 'wp-auto-content-pro' ); ?></label>
                        <input type="password" name="wpac_claude_api_key" value="<?php echo esc_attr( get_option( 'wpac_claude_api_key', '' ) ); ?>" class="wpac-form-control" placeholder="sk-ant-...">
                        <div class="wpac-field-actions">
                            <button type="button" class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-ai" data-provider="claude">
                                <?php esc_html_e( 'Test Connection', 'wp-auto-content-pro' ); ?>
                            </button>
                        </div>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Claude Model', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_claude_model" class="wpac-form-control">
                            <?php foreach ( array( 'claude-opus-4-6', 'claude-sonnet-4-6', 'claude-3-opus-20240229', 'claude-3-sonnet-20240229' ) as $m ) : ?>
                                <option value="<?php echo esc_attr( $m ); ?>" <?php selected( get_option( 'wpac_claude_model', 'claude-opus-4-6' ), $m ); ?>><?php echo esc_html( $m ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- DeepSeek Settings -->
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'DeepSeek Settings', 'wp-auto-content-pro' ); ?></h2>
                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'API Key', 'wp-auto-content-pro' ); ?></label>
                        <input type="password" name="wpac_deepseek_api_key" value="<?php echo esc_attr( get_option( 'wpac_deepseek_api_key', '' ) ); ?>" class="wpac-form-control" placeholder="sk-...">
                        <div class="wpac-field-actions">
                            <button type="button" class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-ai" data-provider="deepseek">
                                <?php esc_html_e( 'Test Connection', 'wp-auto-content-pro' ); ?>
                            </button>
                        </div>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'DeepSeek Model', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_deepseek_model" class="wpac-form-control">
                            <?php foreach ( array( 'deepseek-chat', 'deepseek-coder' ) as $m ) : ?>
                                <option value="<?php echo esc_attr( $m ); ?>" <?php selected( get_option( 'wpac_deepseek_model', 'deepseek-chat' ), $m ); ?>><?php echo esc_html( $m ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Content Settings -->
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'Content Settings', 'wp-auto-content-pro' ); ?></h2>
                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Content Language', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_content_language" class="wpac-form-control">
                            <?php
                            $languages = array(
                                'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
                                'it' => 'Italian', 'pt-br' => 'Portuguese (Brazil)', 'pt' => 'Portuguese (Portugal)',
                                'nl' => 'Dutch', 'pl' => 'Polish',
                                'ru' => 'Russian', 'ja' => 'Japanese', 'zh' => 'Chinese', 'ko' => 'Korean',
                                'ar' => 'Arabic', 'hi' => 'Hindi', 'tr' => 'Turkish', 'sv' => 'Swedish',
                                'da' => 'Danish', 'no' => 'Norwegian', 'fi' => 'Finnish', 'cs' => 'Czech',
                                'th' => 'Thai', 'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay',
                                'uk' => 'Ukrainian', 'ro' => 'Romanian', 'hu' => 'Hungarian', 'el' => 'Greek',
                                'he' => 'Hebrew',
                            );
                            $current_lang = get_option( 'wpac_content_language', 'en' );
                            foreach ( $languages as $code => $name ) :
                            ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_lang, $code ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Article Length', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_article_length" class="wpac-form-control">
                            <option value="short" <?php selected( get_option( 'wpac_article_length', 'medium' ), 'short' ); ?>><?php esc_html_e( 'Short (600-900 words)', 'wp-auto-content-pro' ); ?></option>
                            <option value="medium" <?php selected( get_option( 'wpac_article_length', 'medium' ), 'medium' ); ?>><?php esc_html_e( 'Medium (900-1400 words)', 'wp-auto-content-pro' ); ?></option>
                            <option value="long" <?php selected( get_option( 'wpac_article_length', 'medium' ), 'long' ); ?>><?php esc_html_e( 'Long (1400-2200 words)', 'wp-auto-content-pro' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="wpac-form-group">
                    <label class="wpac-toggle-label">
                        <div class="wpac-toggle-switch">
                            <input type="checkbox" name="wpac_include_images" value="1" id="wpac-include-images"
                                <?php checked( get_option( 'wpac_include_images', '1' ), '1' ); ?>>
                            <span class="wpac-toggle-slider"></span>
                        </div>
                        <?php esc_html_e( 'Include Featured Images', 'wp-auto-content-pro' ); ?>
                    </label>
                </div>

                <div id="wpac-image-settings">
                    <div class="wpac-settings-grid">
                        <div class="wpac-form-group">
                            <label><?php esc_html_e( 'Image Source', 'wp-auto-content-pro' ); ?></label>
                            <select name="wpac_image_source" class="wpac-form-control">
                                <option value="dalle" <?php selected( get_option( 'wpac_image_source', 'dalle' ), 'dalle' ); ?>><?php esc_html_e( 'DALL-E (OpenAI)', 'wp-auto-content-pro' ); ?></option>
                                <option value="unsplash" <?php selected( get_option( 'wpac_image_source', 'dalle' ), 'unsplash' ); ?>><?php esc_html_e( 'Unsplash', 'wp-auto-content-pro' ); ?></option>
                            </select>
                        </div>
                        <div class="wpac-form-group">
                            <label><?php esc_html_e( 'DALL-E Model', 'wp-auto-content-pro' ); ?></label>
                            <select name="wpac_dalle_model" class="wpac-form-control">
                                <option value="dall-e-3" <?php selected( get_option( 'wpac_dalle_model', 'dall-e-3' ), 'dall-e-3' ); ?>>DALL-E 3</option>
                                <option value="dall-e-2" <?php selected( get_option( 'wpac_dalle_model', 'dall-e-3' ), 'dall-e-2' ); ?>>DALL-E 2</option>
                            </select>
                        </div>
                        <div class="wpac-form-group">
                            <label><?php esc_html_e( 'Image Size', 'wp-auto-content-pro' ); ?></label>
                            <select name="wpac_dalle_size" class="wpac-form-control">
                                <option value="1792x1024" <?php selected( get_option( 'wpac_dalle_size', '1792x1024' ), '1792x1024' ); ?>>1792x1024 (Landscape)</option>
                                <option value="1024x1024" <?php selected( get_option( 'wpac_dalle_size', '1792x1024' ), '1024x1024' ); ?>>1024x1024 (Square)</option>
                                <option value="1024x1792" <?php selected( get_option( 'wpac_dalle_size', '1792x1024' ), '1024x1792' ); ?>>1024x1792 (Portrait)</option>
                            </select>
                        </div>
                        <div class="wpac-form-group">
                            <label><?php esc_html_e( 'Unsplash Access Key', 'wp-auto-content-pro' ); ?></label>
                            <input type="password" name="wpac_unsplash_access_key" value="<?php echo esc_attr( get_option( 'wpac_unsplash_access_key', '' ) ); ?>" class="wpac-form-control" placeholder="<?php esc_attr_e( 'Optional - for Unsplash API', 'wp-auto-content-pro' ); ?>">
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- #tab-ai-settings -->

        <!-- Tab: Social Media -->
        <div class="wpac-tab-content" id="tab-social-media">
            <?php foreach ( $platforms as $slug => $info ) :
                $enabled = get_option( 'wpac_' . $slug . '_enabled', '0' ) === '1';
            ?>
            <div class="wpac-settings-section">
                <div class="wpac-section-header-row">
                    <div class="wpac-section-title-group">
                        <span class="wpac-platform-icon-lg"><?php echo $info['icon']; ?></span>
                        <h2><?php echo esc_html( $info['label'] ); ?></h2>
                    </div>
                    <label class="wpac-toggle-label">
                        <div class="wpac-toggle-switch">
                            <input type="checkbox" name="wpac_<?php echo esc_attr( $slug ); ?>_enabled" value="1"
                                <?php checked( $enabled ); ?>>
                            <span class="wpac-toggle-slider"></span>
                        </div>
                        <?php esc_html_e( 'Enable', 'wp-auto-content-pro' ); ?>
                    </label>
                </div>

                <div class="wpac-platform-fields">
                    <?php if ( 'twitter' === $slug ) : ?>
                        <div class="wpac-notice wpac-notice-info" style="margin-bottom:16px;padding:12px 16px;background:#e8f4fd;border-left:4px solid #2196F3;border-radius:4px;font-size:13px;">
                            <strong><?php esc_html_e( 'OAuth 1.0a User Context required', 'wp-auto-content-pro' ); ?></strong><br>
                            <?php esc_html_e( 'Twitter/X requires all four OAuth 1.0a credentials to post tweets. Bearer Token (Application-Only) is NOT supported for posting.', 'wp-auto-content-pro' ); ?>
                            <br><a href="https://developer.twitter.com/en/portal/projects-and-apps" target="_blank"><?php esc_html_e( 'Get credentials at developer.twitter.com →', 'wp-auto-content-pro' ); ?></a>
                        </div>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'API Key (Consumer Key)', 'wp-auto-content-pro' ); ?> <span style="color:red">*</span></label>
                                <input type="password" name="wpac_twitter_api_key" value="<?php echo esc_attr( get_option( 'wpac_twitter_api_key', '' ) ); ?>" class="wpac-form-control" placeholder="Xxxxxxxxxxxxxxxxxxxxxxxx">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'API Secret (Consumer Secret)', 'wp-auto-content-pro' ); ?> <span style="color:red">*</span></label>
                                <input type="password" name="wpac_twitter_api_secret" value="<?php echo esc_attr( get_option( 'wpac_twitter_api_secret', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Access Token', 'wp-auto-content-pro' ); ?> <span style="color:red">*</span></label>
                                <input type="password" name="wpac_twitter_access_token" value="<?php echo esc_attr( get_option( 'wpac_twitter_access_token', '' ) ); ?>" class="wpac-form-control" placeholder="000000000-Xxxxxxxxxxxxxxxxx">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Access Token Secret', 'wp-auto-content-pro' ); ?> <span style="color:red">*</span></label>
                                <input type="password" name="wpac_twitter_access_secret" value="<?php echo esc_attr( get_option( 'wpac_twitter_access_secret', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Your Twitter Username (optional)', 'wp-auto-content-pro' ); ?></label>
                                <input type="text" name="wpac_twitter_username" value="<?php echo esc_attr( get_option( 'wpac_twitter_username', '' ) ); ?>" class="wpac-form-control" placeholder="username (without @)">
                                <small><?php esc_html_e( 'Used to build the tweet URL. Auto-filled on "Test Connection".', 'wp-auto-content-pro' ); ?></small>
                            </div>
                        </div>
                        <div class="wpac-form-group">
                            <label><?php esc_html_e( 'Post Template', 'wp-auto-content-pro' ); ?></label>
                            <input type="text" name="wpac_twitter_template" value="<?php echo esc_attr( get_option( 'wpac_twitter_template', '' ) ); ?>" class="wpac-form-control" placeholder="{title} {url} {hashtags}">
                            <small><?php esc_html_e( 'Placeholders: {title}, {url}, {hashtags}. Leave blank for auto format.', 'wp-auto-content-pro' ); ?></small>
                        </div>

                    <?php elseif ( 'threads' === $slug ) : ?>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Access Token', 'wp-auto-content-pro' ); ?></label>
                                <input type="password" name="wpac_threads_access_token" value="<?php echo esc_attr( get_option( 'wpac_threads_access_token', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'User ID', 'wp-auto-content-pro' ); ?></label>
                                <input type="text" name="wpac_threads_user_id" value="<?php echo esc_attr( get_option( 'wpac_threads_user_id', '' ) ); ?>" class="wpac-form-control">
                            </div>
                        </div>

                    <?php elseif ( 'instagram' === $slug ) : ?>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Page Access Token', 'wp-auto-content-pro' ); ?></label>
                                <input type="password" name="wpac_instagram_access_token" value="<?php echo esc_attr( get_option( 'wpac_instagram_access_token', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Instagram Business Account ID', 'wp-auto-content-pro' ); ?></label>
                                <input type="text" name="wpac_instagram_account_id" value="<?php echo esc_attr( get_option( 'wpac_instagram_account_id', '' ) ); ?>" class="wpac-form-control">
                            </div>
                        </div>

                    <?php elseif ( 'facebook' === $slug ) : ?>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Page Access Token', 'wp-auto-content-pro' ); ?></label>
                                <input type="password" name="wpac_facebook_page_access_token" value="<?php echo esc_attr( get_option( 'wpac_facebook_page_access_token', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Page ID', 'wp-auto-content-pro' ); ?></label>
                                <input type="text" name="wpac_facebook_page_id" value="<?php echo esc_attr( get_option( 'wpac_facebook_page_id', '' ) ); ?>" class="wpac-form-control">
                            </div>
                        </div>

                    <?php elseif ( 'tiktok' === $slug ) : ?>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Access Token', 'wp-auto-content-pro' ); ?></label>
                                <input type="password" name="wpac_tiktok_access_token" value="<?php echo esc_attr( get_option( 'wpac_tiktok_access_token', '' ) ); ?>" class="wpac-form-control">
                                <small><?php esc_html_e( 'Obtain via TikTok for Developers OAuth flow.', 'wp-auto-content-pro' ); ?></small>
                            </div>
                        </div>

                    <?php elseif ( 'linkedin' === $slug ) : ?>
                        <div class="wpac-settings-grid">
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Access Token', 'wp-auto-content-pro' ); ?></label>
                                <input type="password" name="wpac_linkedin_access_token" value="<?php echo esc_attr( get_option( 'wpac_linkedin_access_token', '' ) ); ?>" class="wpac-form-control">
                            </div>
                            <div class="wpac-form-group">
                                <label><?php esc_html_e( 'Author URN (Auto-detected)', 'wp-auto-content-pro' ); ?></label>
                                <input type="text" name="wpac_linkedin_author_urn" value="<?php echo esc_attr( get_option( 'wpac_linkedin_author_urn', '' ) ); ?>" class="wpac-form-control" placeholder="urn:li:person:XXXX">
                                <small><?php esc_html_e( 'Leave blank to auto-detect from your profile.', 'wp-auto-content-pro' ); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="wpac-field-actions">
                        <button type="button" class="wpac-btn wpac-btn-sm wpac-btn-outline wpac-test-social" data-platform="<?php echo esc_attr( $slug ); ?>">
                            &#9989; <?php esc_html_e( 'Test Connection', 'wp-auto-content-pro' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div><!-- #tab-social-media -->

        <!-- Tab: Schedule -->
        <div class="wpac-tab-content" id="tab-schedule">
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'Automation Schedule', 'wp-auto-content-pro' ); ?></h2>

                <div class="wpac-form-group">
                    <label class="wpac-toggle-label">
                        <div class="wpac-toggle-switch">
                            <input type="checkbox" name="wpac_schedule_enabled" value="1" id="wpac-schedule-enabled"
                                <?php checked( get_option( 'wpac_schedule_enabled', '0' ), '1' ); ?>>
                            <span class="wpac-toggle-slider"></span>
                        </div>
                        <?php esc_html_e( 'Enable Automated Posting', 'wp-auto-content-pro' ); ?>
                    </label>
                    <small><?php esc_html_e( 'When enabled, the plugin will automatically generate and post content based on your schedule.', 'wp-auto-content-pro' ); ?></small>
                </div>

                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label for="wpac-posts-per-day"><?php esc_html_e( 'Posts Per Day', 'wp-auto-content-pro' ); ?> <span class="wpac-val-display" id="wpac-ppd-val"><?php echo esc_html( get_option( 'wpac_posts_per_day', 3 ) ); ?></span></label>
                        <input type="range" name="wpac_posts_per_day" id="wpac-posts-per-day" min="1" max="24" step="1"
                            value="<?php echo esc_attr( get_option( 'wpac_posts_per_day', 3 ) ); ?>"
                            class="wpac-slider">
                    </div>

                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Default Post Status', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_default_post_status" class="wpac-form-control">
                            <option value="publish" <?php selected( get_option( 'wpac_default_post_status', 'publish' ), 'publish' ); ?>><?php esc_html_e( 'Publish Immediately', 'wp-auto-content-pro' ); ?></option>
                            <option value="draft" <?php selected( get_option( 'wpac_default_post_status', 'publish' ), 'draft' ); ?>><?php esc_html_e( 'Save as Draft', 'wp-auto-content-pro' ); ?></option>
                            <option value="pending" <?php selected( get_option( 'wpac_default_post_status', 'publish' ), 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'wp-auto-content-pro' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Posting Window - Start Hour', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_posting_time_from" class="wpac-form-control">
                            <?php for ( $h = 0; $h < 24; $h++ ) : ?>
                                <option value="<?php echo esc_attr( $h ); ?>" <?php selected( (int) get_option( 'wpac_posting_time_from', 8 ), $h ); ?>>
                                    <?php echo esc_html( sprintf( '%02d:00', $h ) ); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Posting Window - End Hour', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_posting_time_to" class="wpac-form-control">
                            <?php for ( $h = 0; $h < 24; $h++ ) : ?>
                                <option value="<?php echo esc_attr( $h ); ?>" <?php selected( (int) get_option( 'wpac_posting_time_to', 20 ), $h ); ?>>
                                    <?php echo esc_html( sprintf( '%02d:00', $h ) ); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Timezone', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_timezone" class="wpac-form-control">
                            <?php
                            $current_tz = get_option( 'wpac_timezone', get_option( 'timezone_string', 'UTC' ) );
                            foreach ( $timezones as $tz ) :
                            ?>
                                <option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $current_tz, $tz ); ?>><?php echo esc_html( $tz ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Default Category', 'wp-auto-content-pro' ); ?></label>
                        <select name="wpac_default_category" class="wpac-form-control">
                            <option value=""><?php esc_html_e( 'AI Decides (Recommended)', 'wp-auto-content-pro' ); ?></option>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( get_option( 'wpac_default_category', '' ), $cat->term_id ); ?>>
                                    <?php echo esc_html( $cat->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div><!-- #tab-schedule -->

        <!-- Tab: Advanced -->
        <div class="wpac-tab-content" id="tab-advanced">
            <div class="wpac-settings-section">
                <h2><?php esc_html_e( 'Advanced Settings', 'wp-auto-content-pro' ); ?></h2>

                <div class="wpac-form-group">
                    <label class="wpac-toggle-label">
                        <div class="wpac-toggle-switch">
                            <input type="checkbox" name="wpac_debug_mode" value="1"
                                <?php checked( get_option( 'wpac_debug_mode', '0' ), '1' ); ?>>
                            <span class="wpac-toggle-slider"></span>
                        </div>
                        <?php esc_html_e( 'Debug Mode', 'wp-auto-content-pro' ); ?>
                    </label>
                    <small><?php esc_html_e( 'Logs detailed errors to error_log. Disable in production.', 'wp-auto-content-pro' ); ?></small>
                </div>

                <div class="wpac-settings-grid">
                    <div class="wpac-form-group">
                        <label><?php esc_html_e( 'Webhook URL', 'wp-auto-content-pro' ); ?></label>
                        <input type="url" name="wpac_webhook_url" value="<?php echo esc_attr( get_option( 'wpac_webhook_url', '' ) ); ?>" class="wpac-form-control" placeholder="https://hooks.example.com/notify">
                        <small><?php esc_html_e( 'Receive a POST notification when new content is published. Leave blank to disable.', 'wp-auto-content-pro' ); ?></small>
                    </div>
                </div>

                <div class="wpac-info-box">
                    <h3><?php esc_html_e( 'WP-Cron Status', 'wp-auto-content-pro' ); ?></h3>
                    <?php $next_run = wp_next_scheduled( WPAC_Scheduler::CRON_HOOK ); ?>
                    <?php if ( $next_run ) : ?>
                        <p><?php printf( esc_html__( 'Next scheduled run: %s', 'wp-auto-content-pro' ), '<strong>' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run ) ) . '</strong>' ); ?></p>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No scheduled run found. Enable the schedule above and save settings.', 'wp-auto-content-pro' ); ?></p>
                    <?php endif; ?>
                    <p><?php esc_html_e( 'For reliable cron, consider disabling WP-Cron and setting up a real server cron job:', 'wp-auto-content-pro' ); ?></p>
                    <code>*/30 * * * * wget -q -O - <?php echo esc_url( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?> > /dev/null 2>&1</code>
                </div>
            </div>
        </div><!-- #tab-advanced -->

        <div class="wpac-settings-footer">
            <button type="submit" class="wpac-btn wpac-btn-primary wpac-btn-lg">
                &#128190; <?php esc_html_e( 'Save All Settings', 'wp-auto-content-pro' ); ?>
            </button>
            <button type="button" id="wpac-save-ajax" class="wpac-btn wpac-btn-secondary wpac-btn-lg">
                &#9889; <?php esc_html_e( 'Save (AJAX)', 'wp-auto-content-pro' ); ?>
            </button>
        </div>

    </form>

    <div id="wpac-toast-container"></div>
</div><!-- .wpac-wrap -->

<script>
jQuery(document).ready(function($) {
    // Tab switching.
    $('.wpac-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        $('.wpac-tab-btn').removeClass('wpac-tab-active');
        $(this).addClass('wpac-tab-active');
        $('.wpac-tab-content').removeClass('wpac-tab-active');
        $('#tab-' + tab).addClass('wpac-tab-active');
    });

    // Restore last active tab from URL hash.
    var hash = window.location.hash.replace('#', '');
    if (hash && $('#tab-' + hash).length) {
        $('[data-tab="' + hash + '"]').trigger('click');
    }

    // Update tab hash on click.
    $('.wpac-tab-btn').on('click', function() {
        window.location.hash = $(this).data('tab');
    });

    // Slider live display.
    $('#wpac-posts-per-day').on('input', function() {
        $('#wpac-ppd-val').text($(this).val());
    });

    // Provider card selection.
    $('input[name="wpac_ai_provider"]').on('change', function() {
        $('.wpac-provider-card').removeClass('wpac-provider-selected');
        $(this).closest('.wpac-provider-card').addClass('wpac-provider-selected');
    });

    // Test AI connection.
    $('.wpac-test-ai').on('click', function() {
        var btn = $(this);
        var provider = btn.data('provider');
        btn.text('Testing...').prop('disabled', true);

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_test_ai',
            nonce: wpac_ajax.nonce,
            provider: provider
        }, function(response) {
            btn.text('Test Connection').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });

    // Test social connection.
    $('.wpac-test-social').on('click', function() {
        var btn = $(this);
        var platform = btn.data('platform');
        btn.text('Testing...').prop('disabled', true);

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_test_social',
            nonce: wpac_ajax.nonce,
            platform: platform
        }, function(response) {
            btn.text('Test Connection').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });

    // AJAX save settings.
    $('#wpac-save-ajax').on('click', function() {
        var btn = $(this);
        btn.text('Saving...').prop('disabled', true);

        var formData = $('#wpac-settings-form').serialize();

        $.post(wpac_ajax.ajax_url, formData + '&action=wpac_save_settings_ajax&nonce=' + wpac_ajax.nonce, function(response) {
            btn.html('&#9889; Save (AJAX)').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        }).fail(function() {
            btn.html('&#9889; Save (AJAX)').prop('disabled', false);
            wpacShowToast('error', wpac_ajax.strings.error);
        });
    });
});
</script>
