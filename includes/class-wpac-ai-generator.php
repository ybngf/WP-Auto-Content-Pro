<?php
/**
 * AI Content Generator class.
 *
 * Handles communication with multiple AI providers to generate
 * articles and tutorials including SEO metadata and social captions.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_AI_Generator
 *
 * Generates article content using OpenAI, Google Gemini, Claude AI, or DeepSeek.
 */
class WPAC_AI_Generator {

    /**
     * Currently selected AI provider.
     *
     * @var string
     */
    private $provider;

    /**
     * Content language code.
     *
     * @var string
     */
    private $language;

    /**
     * Article length preference.
     *
     * @var string
     */
    private $article_length;

    /**
     * Word count map by length preference.
     *
     * @var array
     */
    private $word_counts = array(
        'short'  => '600-900',
        'medium' => '900-1400',
        'long'   => '1400-2200',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->provider       = get_option( 'wpac_ai_provider', 'openai' );
        $this->language       = get_option( 'wpac_content_language', 'en' );
        $this->article_length = get_option( 'wpac_article_length', 'medium' );
    }

    /**
     * Generate a complete article or tutorial for a given topic.
     *
     * Attempts primary provider first, then falls back to available providers.
     *
     * @param string $topic The topic to write about.
     * @param string $type  Content type: 'article' or 'tutorial'.
     * @return array|WP_Error Content array with keys: title, content, excerpt, tags, category,
     *                        meta_description, social_caption, image_prompt. WP_Error on total failure.
     */
    public function generate_article( $topic, $type = 'article' ) {
        $providers_order = $this->get_providers_order();

        foreach ( $providers_order as $provider ) {
            $api_key = get_option( 'wpac_' . $provider . '_api_key', '' );
            if ( empty( $api_key ) ) {
                continue;
            }

            $result = $this->call_provider( $provider, $topic, $type );
            if ( ! is_wp_error( $result ) ) {
                return $result;
            }

            if ( get_option( 'wpac_debug_mode' ) === '1' ) {
                error_log( 'WPAC: Provider ' . $provider . ' failed: ' . $result->get_error_message() );
            }
        }

        return new WP_Error(
            'wpac_no_provider',
            __( 'All AI providers failed or have no API keys configured.', 'wp-auto-content-pro' )
        );
    }

    /**
     * Get providers in order: primary first, then fallbacks.
     *
     * @return array Ordered array of provider slugs.
     */
    private function get_providers_order() {
        $all_providers = array( 'openai', 'gemini', 'claude', 'deepseek' );
        $primary       = $this->provider;

        $ordered = array( $primary );
        foreach ( $all_providers as $p ) {
            if ( $p !== $primary ) {
                $ordered[] = $p;
            }
        }

        return $ordered;
    }

    /**
     * Route call to the appropriate provider method.
     *
     * @param string $provider Provider slug.
     * @param string $topic    Topic to generate.
     * @param string $type     Content type.
     * @return array|WP_Error Generated content or error.
     */
    private function call_provider( $provider, $topic, $type ) {
        switch ( $provider ) {
            case 'openai':
                return $this->generate_with_openai( $topic, $type );
            case 'gemini':
                return $this->generate_with_gemini( $topic, $type );
            case 'claude':
                return $this->generate_with_claude( $topic, $type );
            case 'deepseek':
                return $this->generate_with_deepseek( $topic, $type );
            default:
                return new WP_Error( 'wpac_unknown_provider', __( 'Unknown AI provider.', 'wp-auto-content-pro' ) );
        }
    }

    /**
     * Build the system and user prompt for content generation.
     *
     * @param string $topic Topic to write about.
     * @param string $type  Content type.
     * @return array Array with 'system' and 'user' keys.
     */
    private function build_prompt( $topic, $type ) {
        $word_count    = $this->word_counts[ $this->article_length ] ?? '900-1400';
        $language_name = $this->get_language_name( $this->language );

        $type_instructions = ( 'tutorial' === $type )
            ? 'Write a step-by-step tutorial with numbered steps. Include code blocks using <pre><code> tags where applicable. Each step should have a clear heading.'
            : 'Write a comprehensive, informative article with clear H2 and H3 subheadings, bullet points, and well-structured paragraphs.';

        $system = "You are an expert content writer and SEO specialist. You write high-quality, engaging, and SEO-optimized content in {$language_name}. Always respond with valid JSON only, no additional text.";

        $user = "Generate a complete {$type} about: \"{$topic}\"

Requirements:
- Language: {$language_name}
- Word count: {$word_count} words
- {$type_instructions}
- Include H2 and H3 headings using proper HTML tags
- Use <p>, <ul>, <ol>, <li>, <strong>, <em> tags for formatting
- Make content SEO-optimized naturally

Return a JSON object with exactly these fields:
{
    \"title\": \"SEO-optimized title (50-60 characters)\",
    \"content\": \"Full HTML content with proper formatting\",
    \"excerpt\": \"Engaging excerpt of 2-3 sentences (150-200 characters)\",
    \"tags\": [\"tag1\", \"tag2\", \"tag3\", \"tag4\", \"tag5\"],
    \"category\": \"Single most relevant category name\",
    \"meta_description\": \"SEO meta description exactly 150-155 characters\",
    \"social_caption\": \"Engaging social media caption with relevant hashtags (max 280 chars)\",
    \"image_prompt\": \"Detailed DALL-E image generation prompt for a featured image\"
}";

        return array(
            'system' => $system,
            'user'   => $user,
        );
    }

    /**
     * Generate content using OpenAI GPT-4o.
     *
     * @param string $topic Topic to generate.
     * @param string $type  Content type.
     * @return array|WP_Error Generated content or error.
     */
    private function generate_with_openai( $topic, $type ) {
        $api_key = get_option( 'wpac_openai_api_key', '' );
        $model   = get_option( 'wpac_openai_model', 'gpt-4o' );
        $prompt  = $this->build_prompt( $topic, $type );

        $body = wp_json_encode( array(
            'model'           => $model,
            'messages'        => array(
                array( 'role' => 'system', 'content' => $prompt['system'] ),
                array( 'role' => 'user', 'content' => $prompt['user'] ),
            ),
            'temperature'     => 0.7,
            'max_tokens'      => 4096,
            'response_format' => array( 'type' => 'json_object' ),
        ) );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'OpenAI API error: ' . $code;
            return new WP_Error( 'wpac_openai_error', $message );
        }

        $content_raw = $data['choices'][0]['message']['content'] ?? '';
        return $this->parse_ai_response( $content_raw, 'openai' );
    }

    /**
     * Generate content using Google Gemini.
     *
     * @param string $topic Topic to generate.
     * @param string $type  Content type.
     * @return array|WP_Error Generated content or error.
     */
    private function generate_with_gemini( $topic, $type ) {
        $api_key = get_option( 'wpac_gemini_api_key', '' );
        $model   = get_option( 'wpac_gemini_model', 'gemini-1.5-pro' );
        $prompt  = $this->build_prompt( $topic, $type );

        $full_prompt = $prompt['system'] . "\n\n" . $prompt['user'];

        $body = wp_json_encode( array(
            'contents'         => array(
                array(
                    'parts' => array(
                        array( 'text' => $full_prompt ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature'     => 0.7,
                'maxOutputTokens' => 4096,
            ),
        ) );

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $response = wp_remote_post(
            $url,
            array(
                'timeout' => 120,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Gemini API error: ' . $code;
            return new WP_Error( 'wpac_gemini_error', $message );
        }

        $content_raw = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $this->parse_ai_response( $content_raw, 'gemini' );
    }

    /**
     * Generate content using Anthropic Claude.
     *
     * @param string $topic Topic to generate.
     * @param string $type  Content type.
     * @return array|WP_Error Generated content or error.
     */
    private function generate_with_claude( $topic, $type ) {
        $api_key = get_option( 'wpac_claude_api_key', '' );
        $model   = get_option( 'wpac_claude_model', 'claude-opus-4-6' );
        $prompt  = $this->build_prompt( $topic, $type );

        $body = wp_json_encode( array(
            'model'      => $model,
            'max_tokens' => 4096,
            'system'     => $prompt['system'],
            'messages'   => array(
                array( 'role' => 'user', 'content' => $prompt['user'] ),
            ),
        ) );

        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 120,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Claude API error: ' . $code;
            return new WP_Error( 'wpac_claude_error', $message );
        }

        $content_raw = $data['content'][0]['text'] ?? '';
        return $this->parse_ai_response( $content_raw, 'claude' );
    }

    /**
     * Generate content using DeepSeek.
     *
     * @param string $topic Topic to generate.
     * @param string $type  Content type.
     * @return array|WP_Error Generated content or error.
     */
    private function generate_with_deepseek( $topic, $type ) {
        $api_key = get_option( 'wpac_deepseek_api_key', '' );
        $model   = get_option( 'wpac_deepseek_model', 'deepseek-chat' );
        $prompt  = $this->build_prompt( $topic, $type );

        $body = wp_json_encode( array(
            'model'       => $model,
            'messages'    => array(
                array( 'role' => 'system', 'content' => $prompt['system'] ),
                array( 'role' => 'user', 'content' => $prompt['user'] ),
            ),
            'temperature' => 0.7,
            'max_tokens'  => 4096,
            'stream'      => false,
        ) );

        $response = wp_remote_post(
            'https://api.deepseek.com/v1/chat/completions',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'DeepSeek API error: ' . $code;
            return new WP_Error( 'wpac_deepseek_error', $message );
        }

        $content_raw = $data['choices'][0]['message']['content'] ?? '';
        return $this->parse_ai_response( $content_raw, 'deepseek' );
    }

    /**
     * Parse and validate the raw AI JSON response.
     *
     * @param string $raw_content Raw JSON string from AI.
     * @param string $provider    Provider name for error context.
     * @return array|WP_Error Parsed content array or error.
     */
    private function parse_ai_response( $raw_content, $provider ) {
        // Strip potential markdown code fences.
        $raw_content = preg_replace( '/^```(?:json)?\s*/m', '', $raw_content );
        $raw_content = preg_replace( '/\s*```$/m', '', $raw_content );
        $raw_content = trim( $raw_content );

        $data = json_decode( $raw_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'wpac_json_parse_error',
                sprintf(
                    /* translators: 1: provider name, 2: JSON error message */
                    __( '%1$s returned invalid JSON: %2$s', 'wp-auto-content-pro' ),
                    $provider,
                    json_last_error_msg()
                )
            );
        }

        $required_fields = array( 'title', 'content', 'excerpt', 'tags', 'category', 'meta_description', 'social_caption' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error(
                    'wpac_missing_field',
                    sprintf(
                        /* translators: 1: field name, 2: provider name */
                        __( 'Missing required field "%1$s" in %2$s response.', 'wp-auto-content-pro' ),
                        $field,
                        $provider
                    )
                );
            }
        }

        return array(
            'title'            => sanitize_text_field( $data['title'] ),
            'content'          => wp_kses_post( $data['content'] ),
            'excerpt'          => sanitize_textarea_field( $data['excerpt'] ),
            'tags'             => is_array( $data['tags'] ) ? array_map( 'sanitize_text_field', $data['tags'] ) : array(),
            'category'         => sanitize_text_field( $data['category'] ),
            'meta_description' => sanitize_text_field( $data['meta_description'] ),
            'social_caption'   => sanitize_textarea_field( $data['social_caption'] ),
            'image_prompt'     => sanitize_textarea_field( $data['image_prompt'] ?? '' ),
            'provider'         => $provider,
        );
    }

    /**
     * Generate an image prompt for DALL-E based on a topic.
     *
     * @param string $topic Topic for image generation.
     * @return string Generated image prompt.
     */
    public function generate_image_prompt( $topic ) {
        $default_prompt = "A professional, high-quality featured image for a blog post about: {$topic}. Photorealistic, modern design, clean composition, vibrant colors, 16:9 aspect ratio.";

        $api_key = get_option( 'wpac_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return $default_prompt;
        }

        $body = wp_json_encode( array(
            'model'           => 'gpt-4o',
            'messages'        => array(
                array(
                    'role'    => 'user',
                    'content' => "Generate a concise, detailed DALL-E 3 image prompt (max 200 chars) for a blog featured image about: {$topic}. The image should be professional, modern, and visually appealing. Return only the prompt text, nothing else.",
                ),
            ),
            'max_tokens'      => 200,
            'temperature'     => 0.8,
        ) );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $default_prompt;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return $default_prompt;
        }

        $data    = json_decode( wp_remote_retrieve_body( $response ), true );
        $content = $data['choices'][0]['message']['content'] ?? '';

        return ! empty( $content ) ? sanitize_textarea_field( $content ) : $default_prompt;
    }

    /**
     * Test an API connection by making a minimal request.
     *
     * @param string $provider Provider slug to test.
     * @return array Result with 'success' (bool) and 'message' (string) keys.
     */
    public function test_connection( $provider ) {
        $api_key = get_option( 'wpac_' . $provider . '_api_key', '' );

        if ( empty( $api_key ) ) {
            return array(
                'success' => false,
                'message' => __( 'No API key configured for this provider.', 'wp-auto-content-pro' ),
            );
        }

        switch ( $provider ) {
            case 'openai':
                return $this->test_openai( $api_key );
            case 'gemini':
                return $this->test_gemini( $api_key );
            case 'claude':
                return $this->test_claude( $api_key );
            case 'deepseek':
                return $this->test_deepseek( $api_key );
            default:
                return array( 'success' => false, 'message' => __( 'Unknown provider.', 'wp-auto-content-pro' ) );
        }
    }

    /**
     * Test OpenAI API connection.
     *
     * @param string $api_key API key.
     * @return array Result array.
     */
    private function test_openai( $api_key ) {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'timeout' => 15,
                'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            return array( 'success' => true, 'message' => __( 'OpenAI connection successful.', 'wp-auto-content-pro' ) );
        }

        $data    = json_decode( wp_remote_retrieve_body( $response ), true );
        $message = $data['error']['message'] ?? 'HTTP ' . $code;
        return array( 'success' => false, 'message' => $message );
    }

    /**
     * Test Google Gemini API connection.
     *
     * @param string $api_key API key.
     * @return array Result array.
     */
    private function test_gemini( $api_key ) {
        $response = wp_remote_get(
            "https://generativelanguage.googleapis.com/v1beta/models?key={$api_key}",
            array( 'timeout' => 15 )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            return array( 'success' => true, 'message' => __( 'Google Gemini connection successful.', 'wp-auto-content-pro' ) );
        }

        $data    = json_decode( wp_remote_retrieve_body( $response ), true );
        $message = $data['error']['message'] ?? 'HTTP ' . $code;
        return array( 'success' => false, 'message' => $message );
    }

    /**
     * Test Anthropic Claude API connection.
     *
     * @param string $api_key API key.
     * @return array Result array.
     */
    private function test_claude( $api_key ) {
        $model = get_option( 'wpac_claude_model', 'claude-opus-4-6' );

        $body = wp_json_encode( array(
            'model'      => $model,
            'max_tokens' => 10,
            'messages'   => array(
                array( 'role' => 'user', 'content' => 'Hi' ),
            ),
        ) );

        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 15,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            return array( 'success' => true, 'message' => __( 'Claude connection successful.', 'wp-auto-content-pro' ) );
        }

        $data    = json_decode( wp_remote_retrieve_body( $response ), true );
        $message = $data['error']['message'] ?? 'HTTP ' . $code;
        return array( 'success' => false, 'message' => $message );
    }

    /**
     * Test DeepSeek API connection.
     *
     * @param string $api_key API key.
     * @return array Result array.
     */
    private function test_deepseek( $api_key ) {
        $response = wp_remote_get(
            'https://api.deepseek.com/v1/models',
            array(
                'timeout' => 15,
                'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            return array( 'success' => true, 'message' => __( 'DeepSeek connection successful.', 'wp-auto-content-pro' ) );
        }

        $data    = json_decode( wp_remote_retrieve_body( $response ), true );
        $message = $data['error']['message'] ?? 'HTTP ' . $code;
        return array( 'success' => false, 'message' => $message );
    }

    /**
     * Get language full name from code.
     *
     * @param string $code Language code (e.g., 'en', 'es').
     * @return string Full language name.
     */
    private function get_language_name( $code ) {
        $languages = array(
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese (Brazil)',
            'pt-pt' => 'Portuguese (Portugal)',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'zh' => 'Chinese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'tr' => 'Turkish',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
            'cs' => 'Czech',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'uk' => 'Ukrainian',
            'ro' => 'Romanian',
            'hu' => 'Hungarian',
            'el' => 'Greek',
            'he' => 'Hebrew',
        );

        return $languages[ $code ] ?? 'English';
    }
}
