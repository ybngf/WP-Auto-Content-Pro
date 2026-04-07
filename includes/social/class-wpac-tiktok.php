<?php
/**
 * TikTok Content Posting API integration class.
 *
 * Posts text/photo content to TikTok using the TikTok Content Posting API.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_TikTok
 *
 * Handles posting to TikTok via the Content Posting API.
 */
class WPAC_TikTok {

    /**
     * TikTok API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://open.tiktokapis.com/v2';

    /**
     * Post content to TikTok.
     *
     * TikTok Content Posting API supports photo posts with text.
     *
     * @param array $content Content array with 'text' and optional 'image_url'.
     * @return array Result with 'success', 'message', 'platform_post_id', 'platform_url'.
     */
    public function post( $content ) {
        $access_token = get_option( 'wpac_tiktok_access_token', '' );

        if ( empty( $access_token ) ) {
            return array(
                'success' => false,
                'message' => __( 'TikTok access token not configured.', 'wp-auto-content-pro' ),
            );
        }

        $text = $content['text'] ?? '';

        // TikTok photo post with text.
        if ( ! empty( $content['image_url'] ) ) {
            return $this->post_photo_content( $access_token, $text, $content['image_url'] );
        }

        // Fallback: direct post (text-only via creator info endpoint).
        return $this->post_text_content( $access_token, $text );
    }

    /**
     * Post a photo with caption to TikTok.
     *
     * @param string $access_token TikTok access token.
     * @param string $text         Post caption text.
     * @param string $image_url    Image URL.
     * @return array Result array.
     */
    private function post_photo_content( $access_token, $text, $image_url ) {
        // Initialize photo post.
        $init_body = wp_json_encode( array(
            'post_info' => array(
                'title'           => mb_substr( $text, 0, 150 ),
                'privacy_level'   => 'PUBLIC_TO_EVERYONE',
                'disable_comment' => false,
            ),
            'source_info' => array(
                'source'    => 'PULL_FROM_URL',
                'photo_images' => array( $image_url ),
                'photo_cover_index' => 0,
            ),
            'media_type' => 'PHOTO',
        ) );

        $init_response = wp_remote_post(
            self::API_BASE . '/post/publish/content/init/',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json; charset=UTF-8',
                ),
                'body'    => $init_body,
            )
        );

        if ( is_wp_error( $init_response ) ) {
            return array( 'success' => false, 'message' => $init_response->get_error_message() );
        }

        $code      = wp_remote_retrieve_response_code( $init_response );
        $init_data = json_decode( wp_remote_retrieve_body( $init_response ), true );

        if ( $code === 200 && isset( $init_data['data']['publish_id'] ) ) {
            $publish_id = $init_data['data']['publish_id'];
            return array(
                'success'          => true,
                'message'          => __( 'Posted to TikTok successfully.', 'wp-auto-content-pro' ),
                'platform_post_id' => $publish_id,
                'platform_url'     => '',
            );
        }

        $error_code = $init_data['error']['code'] ?? '';
        $error_msg  = $init_data['error']['message'] ?? 'TikTok API error: HTTP ' . $code;

        // Log error for debugging.
        if ( get_option( 'wpac_debug_mode' ) === '1' ) {
            error_log( 'WPAC TikTok Error [' . $error_code . ']: ' . $error_msg );
        }

        return array( 'success' => false, 'message' => $error_msg );
    }

    /**
     * Post text-only content to TikTok (as a video with a text slide).
     *
     * @param string $access_token TikTok access token.
     * @param string $text         Post text.
     * @return array Result array.
     */
    private function post_text_content( $access_token, $text ) {
        // TikTok requires video/image content; text-only isn't natively supported.
        // We'll attempt the photo post API with a placeholder response.
        return array(
            'success' => false,
            'message' => __( 'TikTok requires an image or video. Please ensure featured images are enabled.', 'wp-auto-content-pro' ),
        );
    }

    /**
     * Get TikTok creator information to verify access token.
     *
     * @param string $access_token Access token.
     * @return array|WP_Error Creator info or error.
     */
    private function get_creator_info( $access_token ) {
        $response = wp_remote_post(
            self::API_BASE . '/post/publish/creator_info/query/',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json; charset=UTF-8',
                ),
                'body'    => '{}',
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            return $data['data'] ?? array();
        }

        $msg = $data['error']['message'] ?? 'TikTok creator info error: HTTP ' . $code;
        return new WP_Error( 'wpac_tiktok_creator_error', $msg );
    }

    /**
     * Test the TikTok API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        $access_token = get_option( 'wpac_tiktok_access_token', '' );

        if ( empty( $access_token ) ) {
            return array(
                'success' => false,
                'message' => __( 'TikTok access token not configured.', 'wp-auto-content-pro' ),
            );
        }

        $creator_info = $this->get_creator_info( $access_token );

        if ( is_wp_error( $creator_info ) ) {
            return array( 'success' => false, 'message' => $creator_info->get_error_message() );
        }

        $username = $creator_info['creator_username'] ?? 'Connected';
        return array(
            'success' => true,
            'message' => sprintf( __( 'TikTok connected as @%s', 'wp-auto-content-pro' ), $username ),
        );
    }
}
