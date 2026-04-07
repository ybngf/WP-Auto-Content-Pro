<?php
/**
 * Social Media Orchestrator class.
 *
 * Coordinates sharing of WordPress posts across multiple social media platforms
 * with platform-specific content formatting.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Social_Media
 *
 * Orchestrates social media sharing across all configured platforms.
 */
class WPAC_Social_Media {

    /**
     * Platform handler class map.
     *
     * @var array
     */
    private $platform_classes = array(
        'twitter'   => 'WPAC_Twitter',
        'threads'   => 'WPAC_Threads',
        'instagram' => 'WPAC_Instagram',
        'facebook'  => 'WPAC_Facebook',
        'tiktok'    => 'WPAC_TikTok',
        'linkedin'  => 'WPAC_LinkedIn',
    );

    /**
     * Share a WordPress post to one or more social media platforms.
     *
     * @param int      $post_id    WordPress post ID.
     * @param array    $platforms  Array of platform slugs to share to.
     * @param int|null $log_id     Post log ID from WPAC_Database.
     * @return array Results array keyed by platform slug, each with 'success', 'message', 'platform_post_id'.
     */
    public function share_post( $post_id, $platforms = array(), $log_id = null ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $database  = new WPAC_Database();
        $results   = array();
        $post_data = $this->prepare_post_data( $post );
        $is_first  = true;

        foreach ( $platforms as $platform ) {
            if ( ! isset( $this->platform_classes[ $platform ] ) ) {
                continue;
            }

            // Brief delay between platform posts to avoid rate limiting.
            if ( ! $is_first ) {
                sleep( 1 );
            }
            $is_first = false;

            $class_name     = $this->platform_classes[ $platform ];
            $platform_class = new $class_name();
            $content        = $this->format_content_for_platform( $platform, $post_data );

            $result = $platform_class->post( $content );

            // Log to database.
            $log_data = array(
                'post_log_id'   => $log_id,
                'wp_post_id'    => $post_id,
                'platform'      => $platform,
                'status'        => $result['success'] ? 'success' : 'failed',
                'error_message' => $result['success'] ? null : $result['message'],
            );

            if ( $result['success'] ) {
                $log_data['platform_post_id'] = $result['platform_post_id'] ?? '';
                $log_data['platform_url']     = $result['platform_url'] ?? '';
            }

            $database->log_social( $log_data );
            $results[ $platform ] = $result;
        }

        return $results;
    }

    /**
     * Test connection to a specific platform.
     *
     * @param string $platform Platform slug.
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_platform( $platform ) {
        if ( ! isset( $this->platform_classes[ $platform ] ) ) {
            return array(
                'success' => false,
                'message' => __( 'Unknown platform.', 'wp-auto-content-pro' ),
            );
        }

        $class_name     = $this->platform_classes[ $platform ];
        $platform_class = new $class_name();
        return $platform_class->test_connection();
    }

    /**
     * Prepare all post data needed for social sharing.
     *
     * @param WP_Post $post WordPress post object.
     * @return array Prepared post data.
     */
    private function prepare_post_data( $post ) {
        $post_url      = get_permalink( $post->ID );
        $post_title    = $post->post_title;
        $post_excerpt  = $post->post_excerpt;
        $social_caption = get_post_meta( $post->ID, '_wpac_social_caption', true );

        if ( empty( $post_excerpt ) ) {
            $post_excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }

        $tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

        // Build hashtags.
        $hashtags = array();
        foreach ( array_slice( $tags, 0, 8 ) as $tag ) {
            $hashtags[] = '#' . preg_replace( '/\s+/', '', ucwords( $tag ) );
        }

        $image_url = '';
        $thumb_id  = get_post_thumbnail_id( $post->ID );
        if ( $thumb_id ) {
            $image_src = wp_get_attachment_image_src( $thumb_id, 'large' );
            $image_url = $image_src ? $image_src[0] : '';
        }

        return array(
            'post_id'        => $post->ID,
            'title'          => $post_title,
            'excerpt'        => $post_excerpt,
            'url'            => $post_url,
            'tags'           => $tags,
            'hashtags'       => $hashtags,
            'image_url'      => $image_url,
            'social_caption' => $social_caption,
        );
    }

    /**
     * Format content differently per platform.
     *
     * @param string $platform  Platform slug.
     * @param array  $post_data Prepared post data.
     * @return array Platform-specific content array.
     */
    private function format_content_for_platform( $platform, $post_data ) {
        $hashtag_str = implode( ' ', array_slice( $post_data['hashtags'], 0, 5 ) );

        switch ( $platform ) {
            case 'twitter':
                return $this->format_for_twitter( $post_data, $hashtag_str );

            case 'threads':
                return $this->format_for_threads( $post_data, $hashtag_str );

            case 'instagram':
                return $this->format_for_instagram( $post_data, $hashtag_str );

            case 'facebook':
                return $this->format_for_facebook( $post_data );

            case 'tiktok':
                return $this->format_for_tiktok( $post_data, $post_data['hashtags'] );

            case 'linkedin':
                return $this->format_for_linkedin( $post_data );

            default:
                return $post_data;
        }
    }

    /**
     * Format content for Twitter (max 280 chars).
     *
     * @param array  $post_data   Post data.
     * @param string $hashtag_str Hashtag string.
     * @return array Twitter content.
     */
    private function format_for_twitter( $post_data, $hashtag_str ) {
        $url_length     = 23; // Twitter URL shortening.
        $hashtag_length = strlen( $hashtag_str ) + 1;
        $available      = 280 - $url_length - $hashtag_length - 3;

        $custom_template = get_option( 'wpac_twitter_template', '' );
        if ( ! empty( $custom_template ) ) {
            $text = str_replace(
                array( '{title}', '{url}', '{hashtags}' ),
                array( $post_data['title'], $post_data['url'], $hashtag_str ),
                $custom_template
            );
        } else {
            $title = $post_data['title'];
            if ( strlen( $title ) > $available ) {
                $title = substr( $title, 0, $available - 3 ) . '...';
            }
            $text = $title . "\n\n" . $post_data['url'] . "\n\n" . $hashtag_str;
        }

        return array(
            'text'      => $text,
            'image_url' => $post_data['image_url'],
        );
    }

    /**
     * Format content for Instagram Threads.
     *
     * @param array  $post_data   Post data.
     * @param string $hashtag_str Hashtag string.
     * @return array Threads content.
     */
    private function format_for_threads( $post_data, $hashtag_str ) {
        $caption = ! empty( $post_data['social_caption'] ) ? $post_data['social_caption'] : $post_data['title'];

        $text = $caption . "\n\n" . $post_data['url'] . "\n\n" . $hashtag_str;

        return array(
            'text'      => $text,
            'image_url' => $post_data['image_url'],
        );
    }

    /**
     * Format content for Instagram (caption heavy with hashtags).
     *
     * @param array  $post_data   Post data.
     * @param string $hashtag_str Hashtag string.
     * @return array Instagram content.
     */
    private function format_for_instagram( $post_data, $hashtag_str ) {
        $all_hashtags = implode( ' ', $post_data['hashtags'] );

        $caption = ! empty( $post_data['social_caption'] ) ? $post_data['social_caption'] : $post_data['excerpt'];
        $caption = wp_strip_all_tags( $caption );

        $text = $caption . "\n\n" . "Read more: " . $post_data['url'] . "\n\n" . $all_hashtags;

        return array(
            'text'      => $text,
            'image_url' => $post_data['image_url'],
        );
    }

    /**
     * Format content for Facebook (link share).
     *
     * @param array $post_data Post data.
     * @return array Facebook content.
     */
    private function format_for_facebook( $post_data ) {
        $message = ! empty( $post_data['social_caption'] ) ? $post_data['social_caption'] : $post_data['excerpt'];
        $message = wp_strip_all_tags( $message );

        return array(
            'message'   => $message,
            'link'      => $post_data['url'],
            'title'     => $post_data['title'],
            'image_url' => $post_data['image_url'],
        );
    }

    /**
     * Format content for TikTok (hashtag heavy).
     *
     * @param array $post_data    Post data.
     * @param array $hashtags_arr Array of hashtag strings.
     * @return array TikTok content.
     */
    private function format_for_tiktok( $post_data, $hashtags_arr ) {
        $all_hashtags = implode( ' ', $hashtags_arr );
        $text         = wp_strip_all_tags( $post_data['title'] ) . "\n\n" . $all_hashtags;

        return array(
            'text'      => $text,
            'image_url' => $post_data['image_url'],
        );
    }

    /**
     * Format content for LinkedIn (professional, long-form).
     *
     * @param array $post_data Post data.
     * @return array LinkedIn content.
     */
    private function format_for_linkedin( $post_data ) {
        $excerpt = ! empty( $post_data['excerpt'] ) ? wp_strip_all_tags( $post_data['excerpt'] ) : '';
        $hashtag_str = implode( ' ', array_slice( $post_data['hashtags'], 0, 5 ) );

        $text = $post_data['title'] . "\n\n" . $excerpt . "\n\nRead more: " . $post_data['url'] . "\n\n" . $hashtag_str;

        return array(
            'title'       => $post_data['title'],
            'description' => $excerpt,
            'url'         => $post_data['url'],
            'text'        => $text,
            'image_url'   => $post_data['image_url'],
        );
    }
}
