<?php
/**
 * LinkedIn API v2 integration class.
 *
 * Posts content to LinkedIn profiles or company pages using the UGC Posts API.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_LinkedIn
 *
 * Handles posting to LinkedIn via the UGC Posts API.
 */
class WPAC_LinkedIn {

    /**
     * LinkedIn API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://api.linkedin.com/v2';

    /**
     * Post content to LinkedIn.
     *
     * @param array $content Content array with 'title', 'description', 'url', 'text', 'image_url'.
     * @return array Result with 'success', 'message', 'platform_post_id', 'platform_url'.
     */
    public function post( $content ) {
        $access_token = get_option( 'wpac_linkedin_access_token', '' );
        $author_urn   = get_option( 'wpac_linkedin_author_urn', '' );

        if ( empty( $access_token ) ) {
            return array(
                'success' => false,
                'message' => __( 'LinkedIn access token not configured.', 'wp-auto-content-pro' ),
            );
        }

        if ( empty( $author_urn ) ) {
            // Fetch author URN from the API.
            $profile    = $this->get_profile( $access_token );
            if ( is_wp_error( $profile ) ) {
                return array( 'success' => false, 'message' => $profile->get_error_message() );
            }
            $author_urn = 'urn:li:person:' . $profile['id'];
            update_option( 'wpac_linkedin_author_urn', sanitize_text_field( $author_urn ) );
        }

        // Build article post with link preview.
        $post_body = $this->build_article_post( $author_urn, $content );

        $response = wp_remote_post(
            self::API_BASE . '/ugcPosts',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization'          => 'Bearer ' . $access_token,
                    'Content-Type'           => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0',
                ),
                'body'    => wp_json_encode( $post_body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( in_array( $code, array( 200, 201 ), true ) ) {
            $post_id      = $data['id'] ?? '';
            $platform_url = ! empty( $post_id ) ? "https://www.linkedin.com/feed/update/{$post_id}/" : '';

            return array(
                'success'          => true,
                'message'          => __( 'Posted to LinkedIn successfully.', 'wp-auto-content-pro' ),
                'platform_post_id' => $post_id,
                'platform_url'     => $platform_url,
            );
        }

        $error_msg = $data['message'] ?? ( $data['serviceErrorCode'] ?? 'LinkedIn API error: HTTP ' . $code );
        return array( 'success' => false, 'message' => $error_msg );
    }

    /**
     * Build the UGC post payload for an article share.
     *
     * @param string $author_urn Author URN (person or organization).
     * @param array  $content    Content data.
     * @return array UGC post payload.
     */
    private function build_article_post( $author_urn, $content ) {
        $title       = $content['title'] ?? '';
        $description = $content['description'] ?? '';
        $url         = $content['url'] ?? '';
        $text        = $content['text'] ?? $title;

        $post = array(
            'author'          => $author_urn,
            'lifecycleState'  => 'PUBLISHED',
            'specificContent' => array(
                'com.linkedin.ugc.ShareContent' => array(
                    'shareCommentary' => array(
                        'text' => $text,
                    ),
                    'shareMediaCategory' => 'ARTICLE',
                    'media'              => array(
                        array(
                            'status'      => 'READY',
                            'description' => array( 'text' => mb_substr( $description, 0, 256 ) ),
                            'originalUrl' => $url,
                            'title'       => array( 'text' => mb_substr( $title, 0, 200 ) ),
                        ),
                    ),
                ),
            ),
            'visibility' => array(
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ),
        );

        return $post;
    }

    /**
     * Get LinkedIn profile information.
     *
     * @param string $access_token Access token.
     * @return array|WP_Error Profile data or error.
     */
    private function get_profile( $access_token ) {
        $response = wp_remote_get(
            self::API_BASE . '/me',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            return $data;
        }

        $msg = $data['message'] ?? 'LinkedIn profile fetch error: HTTP ' . $code;
        return new WP_Error( 'wpac_linkedin_profile_error', $msg );
    }

    /**
     * Test the LinkedIn API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        $access_token = get_option( 'wpac_linkedin_access_token', '' );

        if ( empty( $access_token ) ) {
            return array(
                'success' => false,
                'message' => __( 'LinkedIn access token not configured.', 'wp-auto-content-pro' ),
            );
        }

        $profile = $this->get_profile( $access_token );

        if ( is_wp_error( $profile ) ) {
            return array( 'success' => false, 'message' => $profile->get_error_message() );
        }

        $first_name = $profile['localizedFirstName'] ?? '';
        $last_name  = $profile['localizedLastName'] ?? '';
        $full_name  = trim( $first_name . ' ' . $last_name ) ?: 'Connected';

        return array(
            'success' => true,
            'message' => sprintf( __( 'LinkedIn connected as %s', 'wp-auto-content-pro' ), $full_name ),
        );
    }
}
