<?php
/**
 * Copyright (c) Adrien Foulon - 2018. All rights reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Created by PhpStorm.
 * User: Adrien
 * Date: 28/05/2018
 * Time: 11:40
 */

namespace Tofandel\Medias;

use Tofandel\Core\Modules\ReduxFramework;
use Tofandel\Core\Objects\WP_Metabox;
use Tofandel\Core\Objects\WP_Post_Entity;

abstract class WPP_Media extends WP_Post_Entity {

	public static function actionsAndFilters() {
		add_action( 'wpp/redux/' . self::$parent->getReduxOptName() . '/config', [ self::class, 'metabox' ] );
		add_action( 'redux/metabox/' . self::StaticPostType() . '/saved', [ self::class, 'flush_htaccess' ], 999, 0 );
		add_filter( 'mod_rewrite_rules', [ self::class, 'output_htaccess' ], 999, 1 );
		//add_filter( 'single_template', [ self::class, 'template' ] );
		add_action( 'template_redirect', [ self::class, 'advanced_template' ], 1 );
		add_filter( 'post_type_link', [ self::class, 'remove_slug' ], 10, 2 );
		add_action( 'manage_' . self::StaticPostType() . '_posts_custom_column', [ self::class, 'custom_columns_data' ], 10, 2 );
		add_filter( 'manage_' . self::StaticPostType() . '_posts_columns', [ self::class, 'custom_columns' ], 10, 1 );
		add_action( 'redux/metabox/' . self::$parent->getReduxOptName() . '/saved', [ self::class, 'update_etag' ], 10, 3 );
	}

	public static function update_etag( $post, $updated, $diff ) {
		if ( $updated && array_key_exists( 'media-file', $diff ) ) {
			update_post_meta( $post->ID, 'etag', time() );
		}
	}

	public static function custom_columns( $columns ) {
		$columns = wpp_array_insert_after( $columns, 'cb', array(
			'featured_image' => __( 'Thumbnail', self::getTextDomain() ),
		) );
		$columns = wpp_array_insert_after( $columns, 'title', array(
			'link' => __( 'Link', self::getTextDomain() ),
		) );

		return $columns;
	}

	public static function custom_columns_data( $column, $post_id ) {
		switch ( $column ) {
			case 'featured_image':
				$attachment = WP_Metabox::get_meta_value( $post_id, 'media-file' );
				echo wp_get_attachment_image( $attachment['id'] );
				break;
			case 'link':
				$perma = get_the_permalink( $post_id );
				$short = wpp_remove_domain_from_url( $perma );
				echo "<a href='$perma'>$short</a>";
				break;
		}
	}

	public static function remove_slug( $post_link, $post ) {

		if ( self::StaticPostType() != $post->post_type || 'publish' != $post->post_status ) {
			return $post_link;
		}

		static $cache;

		if ( ! isset( $cache[ $post_link ] ) ) {
			$cslug = wpp_slugify( WP_Metabox::get_meta_value( $post->ID, 'custom-slug' ), false );

			$cache[ $post_link ] = str_replace( '/' . $post->post_type . '/', trailingslashit( '/' . $cslug ), $post_link );
		}

		return $cache[ $post_link ];
	}

	public static function advanced_template() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$posts = get_posts( array(
				'post_type'      => 'wpp_media',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			) );
			foreach ( $posts as $media ) {
				/**
				 * @var \WP_Post $media
				 */
				$perma = get_permalink( $media );
				preg_match( "#https?:\/\/[^\/]*.\/(.*)#", $perma, $matches );
				$path = $matches[1];
				if ( preg_match( '#^' . $path . '?$#i', ltrim( $_SERVER['REQUEST_URI'], '/' ) ) ) {
					global $post;
					$post = $media;
					include __DIR__ . '/../templates/single-wpp_media.php';
				}
			}
		}
	}

	public static function template( $single ) {
		global $post;

		if ( $post->post_type == self::StaticPostType() ) {
			if ( file_exists( __DIR__ . '/../templates/single-wpp_media.php' ) ) {
				return __DIR__ . '/../templates/single-wpp_media.php';
			}
		}

		return $single;
	}

	public static function flush_htaccess() {
		flush_rewrite_rules( true );
	}

	public static function output_htaccess( $rules ) {
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
			$attachment = WP_Metabox::get_meta_value( $post->ID, 'media-file' );
			if ( empty( $attachment['url'] ) ) {
				continue;
			}

			$filename       = WP_Metabox::get_meta_value( $post->ID, 'file-name' );
			$force_download = WP_Metabox::get_meta_value( $post->ID, 'force-download' ) ? 'attachment' : 'inline';
			$etag           = WP_Metabox::get_meta_value( $post->ID, 'etag' );
			if ( empty( $etag ) ) {
				$etag = time();
				update_post_meta( $post->ID, 'etag', $etag );
			}

			$ext = pathinfo( $attachment['url'], PATHINFO_EXTENSION );
			if ( ! empty( $filename ) ) {
				if ( strpos( '.', $filename ) === false && ! empty( $ext ) ) {
					$filename .= '.' . $ext;
				}
			} elseif ( ! empty( $ext ) ) {
				$filename = $attachment['title'] . '.' . $ext;
			} else {
				$filename = $attachment['title'];
			}
			//Remove dangerous quotes
			$filename = str_replace( '"', "", $filename );

			$mime       = get_post_mime_type( $attachment['id'] );
			$redir_path = get_attached_file( $attachment['id'] );
			$perma      = get_permalink( $post->ID );
			preg_match( "#https?:\/\/[^\/]*.\/(.*)#", $perma, $matches );
			$path = trailingslashit( $matches[1] );
			//$base = str_replace(site_url(), '', home_url());
			//$path = ltrim(trailingslashit($base).trailingslashit($cslug).trailingslashit($perma), '/');

			$rules = substr_replace( $rules, "RewriteRule ^" . $path . "?$ " . $redir_path . " [L]\n", $pos, 0 );

			$new_rules .= "<IfModule mod_headers.c>\n" .
			              "\t<If \"%{THE_REQUEST} =~ m#\s/+" . $path . "?[?\s]#\">\n" .
			              "\t\tHeader set Content-type: \"" . $mime . "\"\n" .
			              "\t\tHeader set Content-Disposition: \"" . $force_download . ";filename=\\\"" . $filename . "\\\"\"\n" .
			              "\t\tHeader set ETag: \"" . $etag . "\"\n" .
			              "\t</If>\n" .
			              "</IfModule>\n";
		}


		return $new_rules . $rules;
	}

	/**
	 * @param ReduxFramework $config
	 *
	 * @throws \Exception
	 */
	public static function metabox( ReduxFramework $config ) {
		//$m = new WP_Metabox( self::parent->getReduxOptName(), 'media', 'Media', self::StaticPostType(), 'normal', 'high' );
		$config->setMetabox( 'media', 'Media', self::StaticPostType(), 'normal', 'high' );
		$config->setMetaboxSection( 'media', array(
			array(
				'title'   => esc_html__( 'Media File', self::getTextDomain() ),
				'desc'    => esc_html__( 'Choose or upload a file from the media library', self::getTextDomain() ),
				'id'      => 'media-file',
				'type'    => 'media',
				'mode'    => false,
				'repeat'  => false,
				'default' => ''
			),
			array(
				'title'    => esc_html__( 'File name', self::getTextDomain() ),
				'desc'     => esc_html__( 'This will be the name of the file when downloaded (The extension is automatic)', self::getTextDomain() ),
				'id'       => 'file-name',
				'type'     => 'text',
				'default'  => '',
				'validate' => 'str_replace',
				'str'      => array(
					'search'      => '"',
					'replacement' => ''
				)
			),
			array(
				'title'   => esc_html__( 'Force download', self::getTextDomain() ),
				'desc'    => esc_html__( 'This will tell the browser the file should be downloaded', self::getTextDomain() ),
				'id'      => 'force-download',
				'type'    => 'switch',
				'default' => false
			),
			array(
				'title'   => esc_html__( 'Custom Endpoint', self::getTextDomain() ),
				'desc'    => esc_html__( 'This will be the endpoint where you can access the file, this setting might override existing pages so be careful', self::getTextDomain() ),
				'id'      => 'custom-slug',
				'type'    => 'text',
				'default' => 'media'
			),
		) );
	}

	public static function post_type_options() {
		$labels = array(
			'name'               => _x( 'Linked Medias', 'post type general name', self::getTextDomain() ),
			'singular_name'      => _x( 'Linked Media', 'post type singular name', self::getTextDomain() ),
			'menu_name'          => _x( 'Linked Medias', 'admin menu', self::getTextDomain() ),
			'name_admin_bar'     => _x( 'Linked Media', 'add new on admin bar', self::getTextDomain() ),
			'add_new'            => _x( 'Add New', 'media', self::getTextDomain() ),
			'add_new_item'       => __( 'Add New Linked Media', self::getTextDomain() ),
			'new_item'           => __( 'New Link', self::getTextDomain() ),
			'edit_item'          => __( 'Edit Link', self::getTextDomain() ),
			'view_item'          => __( 'View Linked Media', self::getTextDomain() ),
			'all_items'          => __( 'Linked Medias', self::getTextDomain() ),
			'search_items'       => __( 'Search Links', self::getTextDomain() ),
			//'parent_item_colon'  => __( 'Parent Link:', self::getTextDomain() ),
			'not_found'          => __( 'No Linked Media found.', self::getTextDomain() ),
			'not_found_in_trash' => __( 'No Linked Media found in Trash.', self::getTextDomain() )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', self::getTextDomain() ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'upload.php',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => static::StaticPostType() ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 11,
			'supports'           => array( 'title', 'author' )
		);

		return $args;
	}
}