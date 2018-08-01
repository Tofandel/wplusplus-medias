<?php
/**
 * Created by PhpStorm.
 * User: Adrien
 * Date: 28/05/2018
 * Time: 11:40
 */

namespace Tofandel\Medias;


use Tofandel\Core\Interfaces\SubModule as SubmoduleInterface;
use Tofandel\Core\Interfaces\WP_Plugin;
use Tofandel\Core\Objects\WP_Metabox;
use Tofandel\Core\Traits\SubModule;
use Tofandel\Core\Traits\WP_Post_Entity;

class WPP_Media implements SubmoduleInterface {
	use SubModule {
		__construct as SubModuleConstruct;
	}
	use WP_Post_Entity {
		__construct as PostEntityConstruct;
	}

	public function __construct( WP_Plugin &$parent = null ) {
		$this->SubModuleConstruct( $parent );
		self::__init__();
	}

	protected function __init() {
		add_action( 'redux_loaded', [ $this, 'metabox' ] );
		add_action( 'redux/metabox/' . $this->postType() . '/saved', [ $this, 'flush_htaccess' ], 999, 0 );
		add_filter( 'mod_rewrite_rules', [ $this, 'output_htaccess' ], 999, 1 );
		add_filter( 'single_template', [ $this, 'template' ] );
		add_action( 'template_redirect', [ $this, 'advanced_template' ], 1 );
	}

	public function advanced_template() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$query = new \WP_Query( array(
				'post_type'      => 'wpp_media',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'custom-slug',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => 'custom-slug',
						'compare' => '!=',
						'value'   => 'media'
					),
				)
			) );
			foreach ( $query->posts as $media ) {
				/**
				 * @var \WP_Post $media
				 */
				$path = trailingslashit( trailingslashit( ltrim( get_post_meta( $media->ID, 'custom-slug', true ), '/' ) ) . $media->post_name );

				var_dump( $path, ltrim( $_SERVER['REQUEST_URI'], '/' ) );
				if ( preg_match( '#^' . $path . '?$#i', ltrim( $_SERVER['REQUEST_URI'], '/' ) ) ) {
					global $post;
					$post = $media;
					include __DIR__ . '/../templates/single-wpp_media.php';
				}
			}
		}
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

	public function flush_htaccess() {
		flush_rewrite_rules( true );
	}

	public function output_htaccess( $rules ) {
		$medias = get_posts( array(
			'post_type'      => 'wpp_media',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		) );

		$pos       = strpos( $rules, "RewriteRule ^index\.php$ - [L]\n" );
		$new_rules = "";
		foreach ( $medias as $post ) {
			/**
			 * @var \WP_Post $post
			 */
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

			if ( ! empty( $cslug = get_post_meta( $post->ID, 'custom-slug', true ) ) ) {
				$path = trailingslashit( trailingslashit( $cslug ) . $post->post_name );
			} else {
				preg_match( "#https?:\/\/[^\/]*.\/(.*)#", get_permalink( $post->ID ), $matches );
				$path = trailingslashit( $matches[1] );
			}

			$rules = substr_replace( $rules, "RewriteRule ^" . $path . "?$ " . $redir_path . " [L]\n", $pos, 0 );

			$new_rules .= "<IfModule mod_headers.c>\n" .
			              "\t<If \"%{THE_REQUEST} =~ m#\s/+" . $path . "?[?\s]#\">\n" .
			              "\t\tHeader set Content-type: \"" . $mime . "\"\n" .
			              "\t\tHeader set Content-Disposition: \"" . $force_download . ";filename='" . $filename . "'\"\n" .
			              "\t</If>\n" .
			              "</IfModule>\n";
		}


		return $new_rules . $rules;
	}


	public function metabox() {
		$m = new WP_Metabox( 'wpp_media', 'media', 'Media', self::StaticPostType(), 'normal', 'high' );
		$m->setSection( 'media', array(
			array(
				'title'   => esc_html__( 'Media File', $this->getTextDomain() ),
				'desc'    => esc_html__( 'Choose or upload a file from the media library', $this->getTextDomain() ),
				'id'      => 'media-file',
				'type'    => 'media',
				'repeat'  => false,
				'default' => ''
			),
			array(
				'title'   => esc_html__( 'File name', $this->getTextDomain() ),
				'desc'    => esc_html__( 'This will be the name of the file when downloaded', $this->getTextDomain() ),
				'id'      => 'file-name',
				'type'    => 'text',
				'default' => ''
			),
			array(
				'title'   => esc_html__( 'Force download', $this->getTextDomain() ),
				'desc'    => esc_html__( 'This will tell the browser the file should be downloaded', $this->getTextDomain() ),
				'id'      => 'force-download',
				'type'    => 'switch',
				'default' => false
			),
			array(
				'title'   => esc_html__( 'Custom Endpoint', $this->getTextDomain() ),
				'desc'    => esc_html__( 'This will be the endpoint where you can access the file, this setting might override existing pages so be careful', $this->getTextDomain() ),
				'id'      => 'custom-slug',
				'type'    => 'text',
				'default' => 'media'
			),
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