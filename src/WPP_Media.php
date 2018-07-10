<?php
/**
 * Created by PhpStorm.
 * User: Adrien
 * Date: 28/05/2018
 * Time: 11:40
 */

namespace Tofandel\Medias;


use Tofandel\Core\Objects\WP_Metabox;
use Tofandel\Core\Traits\WP_Post_Entity;

class WPP_Media {
	use WP_Post_Entity;

	protected function __init() {
		self::metabox();
		add_action( 'save_post', [ $this, 'flush_htaccess' ], 999, 2 );
		add_filter( 'mod_rewrite_rules', [ $this, 'output_htaccess' ], 999, 1 );
		add_filter( 'single_template', [ $this, 'template' ] );
	}

	public function template( $single ) {
		global $post;

		if ( $post->post_type == $this->postType() ) {
			if ( file_exists( __DIR__ . '/../templates/single-wpp_media.php' ) ) {
				return __DIR__ . '/../templates/single-wpp_media.php';
			}
		}

		return $single;
	}

	/**
	 * @param $post_id
	 * @param \WP_Post $post
	 */
	public function flush_htaccess( $post_id, $post ) {
		if ( $post->post_type == $this->postType() ) {
			flush_rewrite_rules( true );
		}
	}

	public function output_htaccess( $rules ) {
		$medias = get_posts( array(
			'post_type'      => 'wpp_media',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		) );

		$new_rules = "<IfModule mod_rewrite.c>\n" . "RewriteEngine On\n" . "RewriteBase /\n" . "</IfModule>\n";
		foreach ( $medias as $post ) {
			$attachment = get_post_meta( $post->ID, 'media-file', true );
			if ( empty( $attachment['url'] ) ) {
				continue;
			}

			$filename       = get_post_meta( $post->ID, 'file-name', true );
			$force_download = get_post_meta( $post->ID, 'force-download', true ) ? 'attachment' : 'inline';

			$ext = pathinfo( $attachment['url'], PATHINFO_EXTENSION );
			if ( ! empty( $filename ) ) {
				if ( strpos( '.', $filename ) == false && ! empty( $ext ) ) {
					$filename .= '.' . $ext;
				}
			} elseif ( ! empty( $ext ) ) {
				$filename = $attachment['title'] . '.' . $ext;
			} else {
				$filename = $attachment['title'];
			}

			$mime       = get_post_mime_type( $attachment['id'] );
			$redir_path = get_attached_file( $attachment['id'] );

			preg_match( "#https?:\/\/[^\/]*.\/(.*)#", get_permalink( $post->ID ), $matches );
			$path = trailingslashit( $matches[1] );

			$new_rules .= "<IfModule mod_rewrite.c>\n" .
			              "\tRewriteRule ^" . $path . "?$ " . $redir_path . " [L]\n" .
			              "</IfModule>\n";
			$new_rules .= "<IfModule mod_headers.c>\n" .
			              "\t<If \"%{THE_REQUEST} =~ m#\s/+".$path."?[?\s]#\">\n" .
			              "\t\tHeader set Content-type: \"" . $mime . "\"\n" .
			              "\t\tHeader set Content-Disposition: \"" . $force_download . ";filename='" . $filename . "'\"\n" .
			              "\t</If>\n" .
			              "</IfModule>\n";
		}


		return $new_rules . $rules;
	}


	public static function metabox() {
		global $WPlusPlusMedias;
		$m = new WP_Metabox( 'wpp_media', 'media', 'Media', self::StaticPostType(), 'normal', 'high' );
		$m->setSection( 'media', array(
			array(
				'title'   => esc_html__( 'Media File', $WPlusPlusMedias->getTextDomain() ),
				'desc'    => esc_html__( 'Choose or upload a file from the media library', $WPlusPlusMedias->getTextDomain() ),
				'id'      => 'media-file',
				'type'    => 'media',
				'repeat'  => false,
				'default' => ''
			),
			array(
				'title'   => esc_html__( 'File name', $WPlusPlusMedias->getTextDomain() ),
				'desc'    => esc_html__( 'This will be the name of the file when downloaded', $WPlusPlusMedias->getTextDomain() ),
				'id'      => 'file-name',
				'type'    => 'text',
				'default' => ''
			),
			array(
				'title'   => esc_html__( 'Force download', $WPlusPlusMedias->getTextDomain() ),
				'desc'    => esc_html__( 'This will tell the browser the file should be downloaded', $WPlusPlusMedias->getTextDomain() ),
				'id'      => 'force-download',
				'type'    => 'switch',
				'default' => false
			)
		) );
	}

	public static function post_type_options() {
		global $WPlusPlusMedias;
		$labels = array(
			'name'               => _x( 'Linked Medias', 'post type general name', $WPlusPlusMedias->getTextDomain() ),
			'singular_name'      => _x( 'Linked Media', 'post type singular name', $WPlusPlusMedias->getTextDomain() ),
			'menu_name'          => _x( 'Linked Medias', 'admin menu', $WPlusPlusMedias->getTextDomain() ),
			'name_admin_bar'     => _x( 'Linked Media', 'add new on admin bar', $WPlusPlusMedias->getTextDomain() ),
			'add_new'            => _x( 'Add New', 'media', $WPlusPlusMedias->getTextDomain() ),
			'add_new_item'       => __( 'Add New Linked Media', $WPlusPlusMedias->getTextDomain() ),
			'new_item'           => __( 'New Link', $WPlusPlusMedias->getTextDomain() ),
			'edit_item'          => __( 'Edit Link', $WPlusPlusMedias->getTextDomain() ),
			'view_item'          => __( 'View Linked Media', $WPlusPlusMedias->getTextDomain() ),
			'all_items'          => __( 'Linked Medias', $WPlusPlusMedias->getTextDomain() ),
			'search_items'       => __( 'Search Links', $WPlusPlusMedias->getTextDomain() ),
			//'parent_item_colon'  => __( 'Parent Link:', $WPlusPlusMedias->getTextDomain() ),
			'not_found'          => __( 'No Linked Media found.', $WPlusPlusMedias->getTextDomain() ),
			'not_found_in_trash' => __( 'No Linked Media found in Trash.', $WPlusPlusMedias->getTextDomain() )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', $WPlusPlusMedias->getTextDomain() ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'upload.php',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'media' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 11,
			'supports'           => array( 'title', 'author' )
		);

		return $args;
	}
}