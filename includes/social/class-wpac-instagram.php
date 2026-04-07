<?php
/**
 * Instagram Business API integration class.
 *
 * Posts content to Instagram Business accounts using the Meta Graph API.
 * Requires: Instagram Business Account connected to a Facebook Page.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Instagram
 *
 * Handles posting to Instagram via Meta Graph API.
 */
class WPAC_Instagram {

    /**
     * Meta Graph API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://graph.facebook.com/v19.0';

    /**
     * Post content to Instagram.
     *
     * Creates a media container then publishes it.
     *
     * @param array $content Content array with 'text' and optional 'image_url'.
     * @return array Result with 'success', 'message', 'platform_post_id', 'platform_url'.
     */
    public function post( $content ) {
        $access_token  = get_option( 'wpac_instagram_access_token', '' );
        $ig_account_id = get_option( 'wpac_instagram_account_id', '' );

        if ( empty( $access_token ) || empty( $ig_account_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Instagram API credentials not configured.', 'wp-auto-content-pro' ),
            );
        }

        // Instagram requires an image.
        $image_url = $content['image_url'] ?? '';
        if ( empty( $image_url ) ) {
            return array(
                'success' => false,
                'message' => __( 'Instagram requires an image. No featured image available.', 'wp-auto-content-pro' ),
            );
        }

        $caption = $content['text'] ?? '';

        // Step 1: Create media container.
        $container_id = $this->create_media_container( $ig_account_id, $image_url, $caption, $access_token );

        if ( is_wp_error( $container_id ) ) {
            return array( 'success' => false, 'message' => $container_id->get_error_message() );
        }

        // Wait for media to be ready.
        $ready = $this->wait_for_media_ready( $ig_account_id, $container_id, $access_token );

        if ( ! $ready ) {
            return array(
                'success' => false,
                'message' => __( 'Instagram media container did not become ready in time.', 'wp-auto-content-pro' ),
            );
        }

        // Step 2: Publish.
        $media_id = $this->publish_container( $ig_account_id, $container_id, $access_token );

        if ( is_wp_error( $media_id ) ) {
            return array( 'success' => false, 'message' => $media_id->get_error_message() );
        }

        return array(
            'success'          => true,
            'message'          => __( 'Posted to Instagram successfully.', 'wp-auto-content-pro' ),
            'platform_post_id' => $media_id,
            'platform_url'     => "https://www.instagram.com/p/{$media_id}/",
        );
    }

    /**
     * Create an Instagram media container.
     *
     * @param string $account_id   Instagram Business Account ID.
     * @param string $image_url    Public image URL.
     * @param string $caption      Post caption.
     * @param string $access_token Page access token.
     * @return string|WP_Error Container ID or error.
     */
    private function create_media_container( $account_id, $image_url, $caption, $access_token ) {
        $response = wp_remote_post(
            self::API_BASE . "/{$account_id}/media",
            array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => array(
                    'image_url'    => $image_url,
                    'caption'      => $caption,
                    'access_token' => $access_token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && isset( $data['id'] ) ) {
            return $data['id'];
        }

        $msg = $data['error']['message'] ?? 'Instagram container creation failed: HTTP ' . $code;
        return new WP_Error( 'wpac_instagram_container_error', $msg );
    }

    /**
     * Wait for a media container to be ready for publishing.
     *
     * Polls the status endpoint up to 5 times.
     *
     * @param string $account_id   Instagram Business Account ID.
     * @param string $container_id Container ID.
     * @param string $access_token Page access token.
     * @return bool True if ready, false if timed out.
     */
    private function wait_for_media_ready( $account_id, $container_id, $access_token ) {
        for ( $i = 0; $i < 5; $i++ ) {
            sleep( 3 );

            $response = wp_remote_get(
                self::API_BASE . "/{$container_id}?fields=status_code&access_token={$access_token}",
                array( 'timeout' => 15 )
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $data        = json_decode( wp_remote_retrieve_body( $response ), true );
            $status_code = $data['status_code'] ?? '';

            if ( 'FINISHED' === $status_code ) {
                return true;
            } elseif ( 'ERROR' === $status_code || 'EXPIRED' === $status_code ) {
                return false;
            }
        }

        return false;
    }

    /**
     * Publish a media container to Instagram.
     *
     * @param string $account_id   Instagram Business Account ID.
     * @param string $container_id Container ID.
     * @param string $access_token Page access token.
     * @return string|WP_Error Published media ID or error.
     */
    private function publish_container( $account_id, $container_id, $access_token ) {
        $response = wp_remote_post(
            self::API_BASE . "/{$account_id}/media_publish",
            array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => array(
                    'creation_id'  => $container_id,
                    'access_token' => $access_token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && isset( $data['id'] ) ) {
            return $data['id'];
        }

        $msg = $data['error']['message'] ?? 'Instagram publish failed: HTTP ' . $code;
        return new WP_Error( 'wpac_instagram_publish_error', $msg );
    }

    /**
     * Test the Instagram API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        $access_token  = get_option( 'wpac_instagram_access_token', '' );
        $ig_account_id = get_option( 'wpac_instagram_account_id', '' );

        if ( empty( $access_token ) || empty( $ig_account_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Instagram access token and account ID are required.', 'wp-auto-content-pro' ),
            );
        }

        $response = wp_remote_get(
            self::API_BASE . "/{$ig_account_id}?fields=id,username&access_token={$access_token}",
            array( 'timeout' => 15 )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            $username = $data['username'] ?? 'Connected';
            return array(
                'success' => true,
                'message' => sprintf( __( 'Instagram connected as @%s', 'wp-auto-content-pro' ), $username ),
            );
        }

        $msg = $data['error']['message'] ?? 'Instagram API error: HTTP ' . $code;
        return array( 'success' => false, 'message' => $msg );
    }
}
