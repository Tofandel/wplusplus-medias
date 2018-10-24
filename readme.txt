=== W++ Medias ===
Contributors: Tofandel
Tags: apidae,twig,maps,plugin
Donate link: https://tukangroup.com/webshop/premium-plugins/wplusplus-apidae/
Requires at least: 4.7
Tested up to: 4.9.8
Requires PHP: 5.5
Stable tag: v1.9.4
License: Apache License Version 2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

W++ medias allows to create user-friendly hardlinks for medias so that the link doesn't change when the media is updated

== Description ==
This plugin is pretty intuitive, it works by creating a new post type: "wpp_media" and a submenu named "Linked medias" in the media menu

In this new post, you can add a media file and some options (force download, filename, custom endpoint)

The URL of this media by default will be https://yoursite.com/media/the_slug/ but can be anything since you can edit the custom endpoint (eg: https://yoursite.com/the_slug/, https://yoursite.com/my/custom/endpoint/the_slug/)

When the post is saved, the .htaccess will be regenerated the Wordpress way with new rules adding an invisible redirection to the original file and headers for the options

As such the file can be accessed extremely fast since you will never hit php or wordpress when requesting it

== Installation ==
Just activate the plugin and you will find a new menu in "Medias > Linked Medias" there you can add your linked medias and that's all there is, no weird option panel no configuration needed

== Frequently Asked Questions ==
Q: Can I set my linked media to be at the root ? (Eg: https://myawesome.site/my_media/)
A: Yes you can by leaving the custom endpoint empty in the Link settings

Q: What file types are supported ?
A: Any file type supported by the Wordpress Library (You can add support for your files using the 'upload_mimes' filter or using the plugin WP Extra File Types)