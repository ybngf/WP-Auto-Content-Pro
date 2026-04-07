<?php
/**
 * Facebook Pages API integration class.
 *
 * Posts content to Facebook Pages using the Meta Graph API.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Facebook
 *
 * Handles posting to Facebook Pages via Meta Graph API.
 */
class WPAC_Facebook {

    /**
     * Meta Graph API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://graph.facebook.com/v19.0';

    /**
     * Post content to a Facebook Page.
     *
     * @param array $content Content array with 'message', 'link', 'title', 'image_url'.
     * @return array Result with 'success', 'message', 'platform_post_id', 'platform_url'.
     */
    public function post( $content ) {
        $page_access_token = get_option( 'wpac_facebook_page_access_token', '' );
        $page_id           = get_option( 'wpac_facebook_page_id', '' );

        if ( empty( $page_access_token ) || empty( $page_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Facebook Page credentials not configured.', 'wp-auto-content-pro' ),
            );
        }

        // Decide whether to post as a link or as a photo.
        if ( ! empty( $content['image_url'] ) ) {
            return $this->post_photo( $page_id, $content, $page_access_token );
        }

        return $this->post_link( $page_id, $content, $page_access_token );
    }

    /**
     * Post a link share to a Facebook Page.
     *
     * @param string $page_id      Facebook Page ID.
     * @param array  $content      Content data.
     * @param string $access_token Page access token.
     * @return array Result array.
     */
    private function post_link( $page_id, $content, $access_token ) {
        $params = array(
            'message'      => $content['message'] ?? '',
            'link'         => $content['link'] ?? '',
            'access_token' => $access_token,
        );

        $response = wp_remote_post(
            self::API_BASE . "/{$page_id}/feed",
            array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => $params,
            )
        );

        return $this->handle_response( $response, $page_id );
    }

    /**
     * Post a photo with caption to a Facebook Page.
     *
     * @param string $page_id      Facebook Page ID.
     * @param array  $content      Content data.
     * @param string $access_token Page access token.
     * @return array Result array.
     */
    private function post_photo( $page_id, $content, $access_token ) {
        $caption = $content['message'] ?? '';
        if ( ! empty( $content['link'] ) ) {
            $caption .= "\n\nRead more: " . $content['link'];
        }

        $params = array(
            'url'          => $content['image_url'],
            'caption'      => $caption,
            'access_token' => $access_token,
        );

        $response = wp_remote_post(
            self::API_BASE . "/{$page_id}/photos",
            array(
                'timeout' => 60,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => $params,
            )
        );

        return $this->handle_response( $response, $page_id );
    }

    /**
     * Handle the API response from Facebook.
     *
     * @param array|WP_Error $response   HTTP response.
     * @param string         $page_id    Facebook Page ID.
     * @return array Result array.
     */
    private function handle_response( $response, $page_id ) {
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && ( isset( $data['id'] ) || isset( $data['post_id'] ) ) ) {
            $post_id      = $data['post_id'] ?? $data['id'];
            $platform_url = "https://www.facebook.com/{$post_id}";

            return array(
                'success'          => true,
                'message'          => __( 'Posted to Facebook Page successfully.', 'wp-auto-content-pro' ),
                'platform_post_id' => $post_id,
                'platform_url'     => $platform_url,
            );
        }

        $msg = $data['error']['message'] ?? 'Facebook API error: HTTP ' . $code;
        return array( 'success' => false, 'message' => $msg );
    }

    /**
     * Test the Facebook API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        $page_access_token = get_option( 'wpac_facebook_page_access_token', '' );
        $page_id           = get_option( 'wpac_facebook_page_id', '' );

        if ( empty( $page_access_token ) || empty( $page_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Facebook Page ID and Page Access Token are required.', 'wp-auto-content-pro' ),
            );
        }

        $response = wp_remote_get(
            self::API_BASE . "/{$page_id}?fields=id,name&access_token={$page_access_token}",
            array( 'timeout' => 15 )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            $page_name = $data['name'] ?? 'Connected';
            return array(
                'success' => true,
                'message' => sprintf( __( 'Facebook connected to page: %s', 'wp-auto-content-pro' ), $page_name ),
            );
        }

        $msg = $data['error']['message'] ?? 'Facebook API error: HTTP ' . $code;
        return array( 'success' => false, 'message' => $msg );
    }
}
