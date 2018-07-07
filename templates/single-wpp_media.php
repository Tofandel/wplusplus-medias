<?php
while (ob_get_level()) ob_end_clean();
global $post;
$attachment = get_post_meta( $post->ID, 'media-file', true );
if ( empty( $attachment['url'] ) ) {
	die();
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

$mime = get_post_mime_type( $attachment['id'] );
if ( ! empty( $mime ) ) {
	header( "Content-type: " . $mime );
}

header( "Content-Disposition: " . $force_download . ";filename='" . $filename . "'" );

readfile( get_attached_file( $attachment['id'] ) );
die();