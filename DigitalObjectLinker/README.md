# Digital Object Linker #
## A plugin for Omeka ##

This plugin allows for external images to be linked to the Relation field
in an Omeka item.  Two Relations are required for an image to be displayed, a link
to the full image and a link to the thumbnail.  Both can be the same link and a thumbnail
will be created from the full.  

full:url (or whichever prefix you choose in your config settings)
thumb:url (or whichever prefix you choose in your config settings)

# Example #
By creating two Relation fields, you can display this image of a cardinal (from Flickr)

full:http://farm5.staticflickr.com/4002/4335389188_e6afd9bd11_b.jpg

thumb:http://farm5.staticflickr.com/4002/4335389188_e6afd9bd11_b.jpg

### Version History

*1.0*

* Initial release.
