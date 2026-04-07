<?php
/**
 * Twitter/X API integration class.
 *
 * Posts content to Twitter/X using API v2 with OAuth 1.0a User Context.
 *
 * IMPORTANT: POST /2/tweets and GET /2/users/me both require OAuth 1.0a
 * User Context (or OAuth 2.0 User Context with PKCE). Bearer Token
 * (Application-Only) is forbidden for these endpoints.
 *
 * Required credentials in WordPress options:
 *  - wpac_twitter_api_key        → Consumer Key (API Key)
 *  - wpac_twitter_api_secret     → Consumer Secret (API Secret)
 *  - wpac_twitter_access_token   → Access Token  (generated for your account)
 *  - wpac_twitter_access_secret  → Access Token Secret
 *  - wpac_twitter_username       → Twitter handle without @ (optional, for URL)
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Twitter
 *
 * Handles posting to Twitter/X via API v2 using OAuth 1.0a User Context.
 */
class WPAC_Twitter {

    /**
     * Twitter API v2 base URL.
     *
     * @var string
     */
    const API_BASE = 'https://api.twitter.com/2';

    /**
     * Twitter Media Upload endpoint (v1.1, still required for media).
     *
     * @var string
     */
    const MEDIA_UPLOAD_URL = 'https://upload.twitter.com/1.1/media/upload.json';

    // -------------------------------------------------------------------------
    // Public methods
    // -------------------------------------------------------------------------

    /**
     * Post content to Twitter using OAuth 1.0a User Context.
     *
     * @param array $content {
     *     @type string $text      Tweet text (will be truncated to 280 chars).
     *     @type string $image_url Optional image URL to attach.
     * }
     * @return array {
     *     @type bool   $success          Whether the post succeeded.
     *     @type string $message          Human-readable result or error.
     *     @type string $platform_post_id Tweet ID (on success).
     *     @type string $platform_url     Full URL to tweet (on success).
     * }
     */
    public function post( $content ) {
        $creds = $this->get_credentials();

        if ( is_wp_error( $creds ) ) {
            return array( 'success' => false, 'message' => $creds->get_error_message() );
        }

        $tweet_text = isset( $content['text'] ) ? $content['text'] : '';
        $tweet_text = $this->truncate_tweet( $tweet_text, 280 );

        $body = array( 'text' => $tweet_text );

        // Attach image if provided.
        if ( ! empty( $content['image_url'] ) ) {
            $media_id = $this->upload_media( $content['image_url'], $creds );
            if ( $media_id ) {
                $body['media'] = array( 'media_ids' => array( $media_id ) );
            }
        }

        $endpoint = self::API_BASE . '/tweets';
        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => $this->oauth1_header( 'POST', $endpoint, array(), $creds ),
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 201 === $code ) {
            $tweet_id = $data['data']['id'] ?? '';
            $username = get_option( 'wpac_twitter_username', '' );
            $url      = $username ? "https://x.com/{$username}/status/{$tweet_id}" : '';

            return array(
                'success'          => true,
                'message'          => __( 'Posted to Twitter/X successfully.', 'wp-auto-content-pro' ),
                'platform_post_id' => $tweet_id,
                'platform_url'     => $url,
            );
        }

        return array(
            'success' => false,
            'message' => $this->extract_error( $data, $code ),
        );
    }

    /**
     * Test the Twitter API connection (requires OAuth 1.0a).
     *
     * Calls GET /2/users/me which returns the authenticated user's profile.
     *
     * @return array { success: bool, message: string }
     */
    public function test_connection() {
        $creds = $this->get_credentials();

        if ( is_wp_error( $creds ) ) {
            return array( 'success' => false, 'message' => $creds->get_error_message() );
        }

        $endpoint = self::API_BASE . '/users/me';
        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => $this->oauth1_header( 'GET', $endpoint, array(), $creds ),
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $code ) {
            $username = $data['data']['username'] ?? 'Unknown';
            // Cache username for URL building.
            if ( $username && 'Unknown' !== $username ) {
                update_option( 'wpac_twitter_username', sanitize_text_field( $username ) );
            }
            return array(
                'success' => true,
                /* translators: %s: Twitter @handle */
                'message' => sprintf( __( 'Twitter/X connected as @%s', 'wp-auto-content-pro' ), $username ),
            );
        }

        return array(
            'success' => false,
            'message' => $this->extract_error( $data, $code ),
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Read and validate OAuth 1.0a credentials from WordPress options.
     *
     * @return array|WP_Error Credentials array or WP_Error on missing fields.
     */
    private function get_credentials() {
        $creds = array(
            'api_key'       => get_option( 'wpac_twitter_api_key', '' ),
            'api_secret'    => get_option( 'wpac_twitter_api_secret', '' ),
            'access_token'  => get_option( 'wpac_twitter_access_token', '' ),
            'access_secret' => get_option( 'wpac_twitter_access_secret', '' ),
        );

        foreach ( array( 'api_key', 'api_secret', 'access_token', 'access_secret' ) as $field ) {
            if ( empty( $creds[ $field ] ) ) {
                return new WP_Error(
                    'missing_credentials',
                    sprintf(
                        /* translators: %s: credential field name */
                        __( 'Twitter credential missing: %s. All four OAuth 1.0a fields are required (API Key, API Secret, Access Token, Access Token Secret).', 'wp-auto-content-pro' ),
                        $field
                    )
                );
            }
        }

        return $creds;
    }

    /**
     * Build a complete OAuth 1.0a Authorization header.
     *
     * @param string $method     HTTP method (GET|POST).
     * @param string $url        Full request URL (no query string).
     * @param array  $req_params Additional request parameters to include in signature base.
     * @param array  $creds      Credentials from get_credentials().
     * @return string Full value for the Authorization header, e.g. "OAuth oauth_consumer_key=...".
     */
    private function oauth1_header( $method, $url, $req_params, $creds ) {
        // 1. Build base OAuth params (without signature).
        $oauth = array(
            'oauth_consumer_key'     => $creds['api_key'],
            'oauth_nonce'            => $this->generate_nonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => (string) time(),
            'oauth_token'            => $creds['access_token'],
            'oauth_version'          => '1.0',
        );

        // 2. Merge all params for signature base string.
        $all_params = array_merge( $oauth, $req_params );
        ksort( $all_params );

        // 3. Build percent-encoded parameter string.
        $param_pairs = array();
        foreach ( $all_params as $k => $v ) {
            $param_pairs[] = rawurlencode( $k ) . '=' . rawurlencode( $v );
        }
        $param_string = implode( '&', $param_pairs );

        // 4. Build signature base string.
        $base_string = implode( '&', array(
            strtoupper( $method ),
            rawurlencode( $url ),
            rawurlencode( $param_string ),
        ) );

        // 5. Build signing key.
        $signing_key = rawurlencode( $creds['api_secret'] ) . '&' . rawurlencode( $creds['access_secret'] );

        // 6. Compute HMAC-SHA1 signature.
        $oauth['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) );

        // 7. Build Authorization header value (only oauth_ keys, quoted).
        $header_parts = array();
        foreach ( $oauth as $k => $v ) {
            $header_parts[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
        }

        return 'OAuth ' . implode( ', ', $header_parts );
    }

    /**
     * Upload an image to Twitter and return its media_id_string.
     *
     * Uses the v1.1 media/upload endpoint (still required even for API v2 tweets).
     *
     * @param string $image_url Remote image URL.
     * @param array  $creds     OAuth 1.0a credentials.
     * @return string|false media_id_string or false on failure.
     */
    private function upload_media( $image_url, $creds ) {
        // Download image bytes.
        $image_response = wp_remote_get( $image_url, array( 'timeout' => 30 ) );
        if ( is_wp_error( $image_response ) || 200 !== wp_remote_retrieve_response_code( $image_response ) ) {
            return false;
        }

        $image_data   = wp_remote_retrieve_body( $image_response );
        $base64_image = base64_encode( $image_data );

        $url    = self::MEDIA_UPLOAD_URL;
        $params = array( 'media_data' => $base64_image );

        // For form-encoded requests, params ARE part of the signature base.
        $response = wp_remote_post(
            $url,
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => $this->oauth1_header( 'POST', $url, $params, $creds ),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body' => http_build_query( $params ),
            )
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['media_id_string'] ?? false;
    }

    /**
     * Truncate text to fit Twitter's character limit.
     *
     * URLs are counted as 23 characters by Twitter regardless of length,
     * but for simplicity we apply a raw mb_strlen check here.
     *
     * @param string $text  Original text.
     * @param int    $limit Character limit (280 for tweets).
     * @return string Possibly truncated text.
     */
    private function truncate_tweet( $text, $limit ) {
        if ( mb_strlen( $text ) <= $limit ) {
            return $text;
        }
        return mb_substr( $text, 0, $limit - 4 ) . '...';
    }

    /**
     * Generate a unique OAuth nonce.
     *
     * @return string 32-character alphanumeric string.
     */
    private function generate_nonce() {
        return substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 32 );
    }

    /**
     * Extract a human-readable error message from a Twitter API response.
     *
     * @param array|null $data Decoded response body.
     * @param int        $code HTTP status code.
     * @return string Error message.
     */
    private function extract_error( $data, $code ) {
        if ( is_array( $data ) ) {
            // API v2 error format.
            if ( ! empty( $data['detail'] ) ) {
                return $data['detail'];
            }
            if ( ! empty( $data['title'] ) ) {
                return $data['title'];
            }
            // API v1.1 error format.
            if ( ! empty( $data['errors'][0]['message'] ) ) {
                return $data['errors'][0]['message'];
            }
        }
        return 'Twitter API error: HTTP ' . $code;
    }
}
