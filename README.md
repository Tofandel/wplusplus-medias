# W++ medias
A Wordpress Plugin allowing you to create easier links for your media files (Like: https://example.com/media/my-awesome-file/)

## Performances
This plugin works by creating a new post type: "wpp_media"

In this post, you can add a media file and some options (force download, and filename)

When the post is saved, the .htaccess will be regenerated with new rules adding an invisible redirection to the original file and headers for the options

As such the file can be accessed extremely fast since you will never hit php or wordpress when requesting it

## Fallback
If .htaccess is not supported (Because you use nginx for example)

Then a wordpress template will be used to display the file and add the specified headers
