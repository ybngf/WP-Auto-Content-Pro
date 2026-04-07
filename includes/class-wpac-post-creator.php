<?php
/**
 * WordPress Post Creator class.
 *
 * Creates WordPress posts from AI-generated content including
 * featured images, categories, and tags.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Post_Creator
 *
 * Handles creating WordPress posts with all associated metadata.
 */
class WPAC_Post_Creator {

    /**
     * Create a WordPress post from AI-generated article data.
     *
     * @param array $article_data Article data from AI generator containing:
     *                            title, content, excerpt, tags, category,
     *                            meta_description, image_prompt.
     * @return int|WP_Error WordPress post ID on success, WP_Error on failure.
     */
    public function create_post( $article_data ) {
        if ( empty( $article_data['title'] ) || empty( $article_data['content'] ) ) {
            return new WP_Error( 'wpac_invalid_data', __( 'Missing title or content for post creation.', 'wp-auto-content-pro' ) );
        }

        $post_status   = get_option( 'wpac_default_post_status', 'publish' );
        $default_cat   = get_option( 'wpac_default_category', '' );

        // Resolve category ID.
        $category_id = $this->get_or_create_category( $article_data['category'], $default_cat );

        // Build post array.
        $post_data = array(
            'post_title'   => wp_strip_all_tags( $article_data['title'] ),
            'post_content' => $article_data['content'],
            'post_excerpt' => isset( $article_data['excerpt'] ) ? $article_data['excerpt'] : '',
            'post_status'  => in_array( $post_status, array( 'publish', 'draft', 'pending' ), true ) ? $post_status : 'publish',
            'post_type'    => 'post',
            'post_category' => $category_id ? array( $category_id ) : array(),
        );

        // Insert the post.
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Set tags.
        if ( ! empty( $article_data['tags'] ) && is_array( $article_data['tags'] ) ) {
            wp_set_post_tags( $post_id, $article_data['tags'], false );
        }

        // Save SEO metadata.
        if ( ! empty( $article_data['meta_description'] ) ) {
            update_post_meta( $post_id, '_wpac_meta_description', sanitize_text_field( $article_data['meta_description'] ) );
            // Yoast SEO compatibility.
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $article_data['meta_description'] ) );
            update_post_meta( $post_id, '_yoast_wpseo_title', wp_strip_all_tags( $article_data['title'] ) );
            // RankMath compatibility.
            update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $article_data['meta_description'] ) );
            update_post_meta( $post_id, 'rank_math_title', wp_strip_all_tags( $article_data['title'] ) );
        }

        // Save social caption.
        if ( ! empty( $article_data['social_caption'] ) ) {
            update_post_meta( $post_id, '_wpac_social_caption', sanitize_textarea_field( $article_data['social_caption'] ) );
        }

        // Save AI provider info and generation timestamp.
        if ( ! empty( $article_data['provider'] ) ) {
            update_post_meta( $post_id, '_wpac_ai_provider', sanitize_text_field( $article_data['provider'] ) );
        }
        update_post_meta( $post_id, '_wpac_generated_at', current_time( 'mysql' ) );

        // Generate and set featured image.
        $include_images = get_option( 'wpac_include_images', '1' );
        if ( '1' === $include_images ) {
            $image_id = $this->generate_and_attach_image( $post_id, $article_data );
            if ( $image_id && ! is_wp_error( $image_id ) ) {
                set_post_thumbnail( $post_id, $image_id );
            }
        }

        return $post_id;
    }

    /**
     * Get or create a category by name.
     *
     * @param string $category_name  Desired category name from AI.
     * @param string $default_cat_id Fallback default category ID from settings.
     * @return int|false Category ID or false.
     */
    private function get_or_create_category( $category_name, $default_cat_id = '' ) {
        if ( ! empty( $category_name ) ) {
            $existing = get_term_by( 'name', $category_name, 'category' );
            if ( $existing ) {
                return $existing->term_id;
            }

            // Create new category.
            $new_cat = wp_insert_term( $category_name, 'category' );
            if ( ! is_wp_error( $new_cat ) ) {
                return $new_cat['term_id'];
            }
        }

        // Fall back to configured default.
        if ( ! empty( $default_cat_id ) && is_numeric( $default_cat_id ) ) {
            return absint( $default_cat_id );
        }

        return false;
    }

    /**
     * Generate and attach a featured image to a post.
     *
     * Tries the configured source first, then falls back through the chain:
     * DALL-E -> Unsplash -> Picsum (free, no API key).
     *
     * @param int   $post_id      WordPress post ID.
     * @param array $article_data Article data including image_prompt and title.
     * @return int|WP_Error Attachment ID or error.
     */
    private function generate_and_attach_image( $post_id, $article_data ) {
        $image_source = get_option( 'wpac_image_source', 'dalle' );
        $title        = $article_data['title'] ?? 'featured-image';

        // Attempt DALL-E if configured.
        if ( 'dalle' === $image_source ) {
            $result = $this->generate_dalle_image( $post_id, $article_data );
            if ( ! is_wp_error( $result ) ) {
                return $result;
            }
            $this->log_debug( 'DALL-E failed, falling back to Unsplash: ' . $result->get_error_message() );
        }

        // Attempt Unsplash.
        if ( in_array( $image_source, array( 'dalle', 'unsplash' ), true ) ) {
            $result = $this->download_unsplash_image( $post_id, $title );
            if ( ! is_wp_error( $result ) ) {
                return $result;
            }
            $this->log_debug( 'Unsplash failed, falling back to Picsum: ' . $result->get_error_message() );
        }

        // Final fallback: Picsum (free, no API key needed).
        $picsum_url = 'https://picsum.photos/1792/1024';
        return $this->sideload_image( $picsum_url, $post_id, $title );
    }

    /**
     * Log debug messages when debug mode is enabled.
     *
     * @param string $message Debug message.
     * @return void
     */
    private function log_debug( $message ) {
        if ( '1' === get_option( 'wpac_debug_mode', '0' ) ) {
            error_log( 'WPAC Post Creator: ' . $message );
        }
    }

    /**
     * Generate an image using OpenAI DALL-E 3.
     *
     * @param int   $post_id      WordPress post ID.
     * @param array $article_data Article data.
     * @return int|WP_Error Attachment ID or error.
     */
    private function generate_dalle_image( $post_id, $article_data ) {
        $api_key = get_option( 'wpac_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'wpac_no_openai_key', __( 'No OpenAI API key for DALL-E.', 'wp-auto-content-pro' ) );
        }

        $dalle_model = get_option( 'wpac_dalle_model', 'dall-e-3' );
        $image_size  = get_option( 'wpac_dalle_size', '1792x1024' );
        $prompt      = ! empty( $article_data['image_prompt'] ) ? $article_data['image_prompt'] : 'Professional blog featured image about: ' . ( $article_data['title'] ?? 'technology' );

        $body = wp_json_encode( array(
            'model'   => $dalle_model,
            'prompt'  => $prompt,
            'n'       => 1,
            'size'    => $image_size,
            'quality' => 'standard',
        ) );

        $response = wp_remote_post(
            'https://api.openai.com/v1/images/generations',
            array(
                'timeout' => 60,
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
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $message = $data['error']['message'] ?? 'DALL-E API error: ' . $code;
            return new WP_Error( 'wpac_dalle_error', $message );
        }

        $image_url = $data['data'][0]['url'] ?? '';
        if ( empty( $image_url ) ) {
            return new WP_Error( 'wpac_dalle_no_url', __( 'DALL-E returned no image URL.', 'wp-auto-content-pro' ) );
        }

        return $this->sideload_image( $image_url, $post_id, $article_data['title'] ?? 'featured-image' );
    }

    /**
     * Download an image from Unsplash API.
     *
     * @param int    $post_id WordPress post ID.
     * @param string $query   Search query.
     * @return int|WP_Error Attachment ID or error.
     */
    private function download_unsplash_image( $post_id, $query ) {
        $access_key = get_option( 'wpac_unsplash_access_key', '' );

        if ( empty( $access_key ) ) {
            return new WP_Error( 'wpac_no_unsplash_key', __( 'No Unsplash API key configured.', 'wp-auto-content-pro' ) );
        }

        $encoded_query = urlencode( $query );
        $url = "https://api.unsplash.com/photos/random?query={$encoded_query}&orientation=landscape&client_id={$access_key}";

        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'wpac_unsplash_error', __( 'Unsplash API error.', 'wp-auto-content-pro' ) );
        }

        $data      = json_decode( wp_remote_retrieve_body( $response ), true );
        $image_url = $data['urls']['regular'] ?? '';

        if ( empty( $image_url ) ) {
            return new WP_Error( 'wpac_unsplash_no_url', __( 'Unsplash returned no image URL.', 'wp-auto-content-pro' ) );
        }

        return $this->sideload_image( $image_url, $post_id, $query );
    }

    /**
     * Sideload a remote image into WordPress media library.
     *
     * @param string $image_url   Remote image URL.
     * @param int    $post_id     Parent post ID.
     * @param string $description Image description/alt text.
     * @return int|WP_Error Attachment ID or error.
     */
    private function sideload_image( $image_url, $post_id, $description ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $image_url, 60 );

        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }

        // Validate downloaded file size (max 10MB).
        $file_size = filesize( $tmp );
        if ( $file_size > 10 * MB_IN_BYTES ) {
            @unlink( $tmp );
            return new WP_Error( 'wpac_image_too_large', __( 'Downloaded image exceeds 10MB limit.', 'wp-auto-content-pro' ) );
        }

        // Detect file extension from content type.
        $ext = 'jpg';
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        if ( $finfo ) {
            $mime = finfo_file( $finfo, $tmp );
            finfo_close( $finfo );
            $mime_to_ext = array(
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                'image/jpeg' => 'jpg',
            );
            $ext = $mime_to_ext[ $mime ] ?? 'jpg';
        }

        $file_array = array(
            'name'     => sanitize_file_name( 'wpac-' . time() . '-' . wp_rand( 100, 999 ) . '.' . $ext ),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload( $file_array, $post_id, $description );

        // Cleanup temp file if sideload failed.
        if ( is_wp_error( $attachment_id ) ) {
            if ( file_exists( $tmp ) ) {
                @unlink( $tmp );
            }
            return $attachment_id;
        }

        // Set image alt text for accessibility and SEO.
        $alt_text = wp_strip_all_tags( $description );
        if ( ! empty( $alt_text ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
        }

        return $attachment_id;
    }

    /**
     * Get the featured image URL for a post.
     *
     * @param int    $post_id Post ID.
     * @param string $size    Image size.
     * @return string Image URL or empty string.
     */
    public function get_featured_image_url( $post_id, $size = 'large' ) {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumbnail_id ) {
            return '';
        }

        $image = wp_get_attachment_image_src( $thumbnail_id, $size );
        return $image ? $image[0] : '';
    }
}
