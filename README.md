# W++ medias
A Wordpress Plugin allowing you to create easier links for your media files (Like: https://example.com/media/my-awesome-file/)

## Features
This plugin is pretty intuitive, it works by creating a new post type: "wpp_media" and a submenu named "Linked medias" in the media menu

In this new post, you can add a media file and some options (force download, filename, custom endpoint)

The URL of this media by default will be https://yoursite.com/media/the_slug/ but can be anything since you can edit the custom endpoint (eg: https://yoursite.com/the_slug/, https://yoursite.com/my/custom/endpoint/the_slug/)

When the post is saved, the .htaccess will be regenerated the Wordpress way with new rules adding an invisible redirection to the original file and headers for the options

As such the file can be accessed extremely fast since you will never hit php or wordpress when requesting it

## Fallback
If .htaccess is not supported (Because you use nginx for example)

Then a wordpress template will be used to display the file and add the specified headers.

Just keep in mind this method is a bit slower so don't use it to embed images into a page