<?php
/**
 * Meta Threads API integration class.
 *
 * Posts content to Instagram Threads using the Meta Threads API.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Threads
 *
 * Handles posting to Meta Threads via the official API.
 */
class WPAC_Threads {

    /**
     * Threads API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://graph.threads.net/v1.0';

    /**
     * Post content to Threads.
     *
     * The Threads API requires two steps: create a media container, then publish it.
     *
     * @param array $content Content array with 'text' and optional 'image_url'.
     * @return array Result with 'success', 'message', 'platform_post_id', 'platform_url'.
     */
    public function post( $content ) {
        $access_token = get_option( 'wpac_threads_access_token', '' );
        $user_id      = get_option( 'wpac_threads_user_id', '' );

        if ( empty( $access_token ) || empty( $user_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Threads API credentials not configured.', 'wp-auto-content-pro' ),
            );
        }

        $text = $content['text'] ?? '';

        // Step 1: Create media container.
        $container_id = $this->create_container( $user_id, $text, $content['image_url'] ?? '', $access_token );

        if ( is_wp_error( $container_id ) ) {
            return array( 'success' => false, 'message' => $container_id->get_error_message() );
        }

        // Brief pause to let the container be processed.
        sleep( 2 );

        // Step 2: Publish the container.
        $post_id = $this->publish_container( $user_id, $container_id, $access_token );

        if ( is_wp_error( $post_id ) ) {
            return array( 'success' => false, 'message' => $post_id->get_error_message() );
        }

        return array(
            'success'          => true,
            'message'          => __( 'Posted to Threads successfully.', 'wp-auto-content-pro' ),
            'platform_post_id' => $post_id,
            'platform_url'     => "https://www.threads.net/t/{$post_id}",
        );
    }

    /**
     * Create a Threads media container.
     *
     * @param string $user_id      Threads user ID.
     * @param string $text         Post text content.
     * @param string $image_url    Optional image URL.
     * @param string $access_token Access token.
     * @return string|WP_Error Container ID or error.
     */
    private function create_container( $user_id, $text, $image_url, $access_token ) {
        $params = array(
            'media_type'   => ! empty( $image_url ) ? 'IMAGE' : 'TEXT',
            'text'         => $text,
            'access_token' => $access_token,
        );

        if ( ! empty( $image_url ) ) {
            $params['image_url'] = $image_url;
        }

        $response = wp_remote_post(
            self::API_BASE . "/{$user_id}/threads",
            array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => $params,
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

        $msg = $data['error']['message'] ?? 'Threads container creation failed: HTTP ' . $code;
        return new WP_Error( 'wpac_threads_container_error', $msg );
    }

    /**
     * Publish a Threads media container.
     *
     * @param string $user_id      Threads user ID.
     * @param string $container_id Container ID from create step.
     * @param string $access_token Access token.
     * @return string|WP_Error Published post ID or error.
     */
    private function publish_container( $user_id, $container_id, $access_token ) {
        $response = wp_remote_post(
            self::API_BASE . "/{$user_id}/threads_publish",
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

        $msg = $data['error']['message'] ?? 'Threads publish failed: HTTP ' . $code;
        return new WP_Error( 'wpac_threads_publish_error', $msg );
    }

    /**
     * Test the Threads API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        $access_token = get_option( 'wpac_threads_access_token', '' );
        $user_id      = get_option( 'wpac_threads_user_id', '' );

        if ( empty( $access_token ) || empty( $user_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Threads access token and user ID are required.', 'wp-auto-content-pro' ),
            );
        }

        $response = wp_remote_get(
            self::API_BASE . "/{$user_id}?fields=id,username&access_token={$access_token}",
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
                'message' => sprintf( __( 'Threads connected as @%s', 'wp-auto-content-pro' ), $username ),
            );
        }

        $msg = $data['error']['message'] ?? 'Threads API error: HTTP ' . $code;
        return array( 'success' => false, 'message' => $msg );
    }
}
