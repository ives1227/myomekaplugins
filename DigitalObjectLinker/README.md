# Digital Object Linker #
## A plugin for Omeka ##

This plugin allows for external images to be linked to the Relation field
in an Omeka item.  Two Relations are required for an image to be displayed, a link
to the full image and a link to the thumbnail.  Both can be the same link and a thumbnail
will be created from the full.  An optional third Relation can also be included called linkto
which will allow you to post an external link that is not an image.  If a linkto is not supplied,
then the linkto is set to the full image.

Furthermore, an optional sequence number can be inserted into the paths to allow objects to have
more than one image.  The optseq numbers should match each other for each grouping.  See below for an example.

full:optseq:url (or whichever prefix you choose in your config settings)
thumb:optseq:url (or whichever prefix you choose in your config settings)
linkto:optseq:url (or whichever prefix you choose in your config settings)

# Example #
By creating two Relation fields, you can display this image of a cardinal (from Flickr)
The linkto is a link to a page about cardinals.

full:001:http://farm5.staticflickr.com/4002/4335389188_e6afd9bd11_b.jpg

thumb:001:http://farm5.staticflickr.com/4002/4335389188_e6afd9bd11_b.jpg

linkto:001:http://www.allaboutbirds.org/guide/Northern_Cardinal/id


full:002:https://farm3.staticflickr.com/2493/4029458470_cdb906e7a7_b_d.jpg

thumb:002:https://farm3.staticflickr.com/2493/4029458470_cdb906e7a7_m_d.jpg


### Version History

*2.0.1*

* Removed the 'display_elements' filter which suppresses the images from displaying under the 'Relation' field.

*2.0.0*

* Allows for a third parameter - full/thumb/linkto
* Allows for an item to have more than one image using an optional sequence number
* To improve performance, a database is now used to store the links 

*1.0.0*

* Initial release.
* Uses two parameters - full/thumb
