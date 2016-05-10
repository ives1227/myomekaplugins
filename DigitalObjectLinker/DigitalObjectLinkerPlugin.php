<?php
/**
* DigitalObjectLinkerPlugin class - represents the Digital Object Linker plugin
* Digital Object links are supplied in the relation field with tags for the thumb
* and tags for the full image.  This should look like:
* thumb:urn and full:urn (where urn is the link to the digital object).
* 
* The thumb:urn links are displayed as a thumbnail image.  
* The full:urn links are displayed as links to the digital object.
* 
* The work is done by a filter called 'replaceDigitalObjectRelations'.
*
*/

/**
 * Digital Object Linker plugin.
 */
class DigitalObjectLinkerPlugin extends Omeka_Plugin_AbstractPlugin
{
    //The default relation tags indicating fullimage or thumb.
	const DEFAULT_FULL_IMAGE_TAG = "full:";
	const DEFAULT_THUMB_TAG = "thumb:";
	const DEFAULT_LINKTO_TAG = "linkto:";
	
	//The default thumbnail size.
	const DEFAULT_THUMB_WIDTH = 200;
	
	//The default size of the large image on the Items show page.
	const DEFAULT_ITEM_PAGE_IMAGE_WIDTH = 400;
	
	
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('config', 
                              'config_form',
    						  'uninstall',
    		        		  'install',
    						  'after_save_item', 'before_delete_item', 'deactivate');

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('replaceDigitalObjectRelations' => array('Display', 'Item', 'Dublin Core', 'Relation'));

    /**
     * @var array Options and their default values.
     */
    protected $_options = array();
    
    /**
     * This is a filter function.  
     * 
     * If the relation text begins with thumb:, then the thumb: portion 
     * is stripped and the remaining urn is displayed as a thumbnail.
     * If the relation text begins with full:, the full: portion
     * is stripped and the remaining urn is displayed as a link.
     * 
     * Any other relation text not meeting the criteria is simply returned as is.
     * 
     * @param string - the text from the Relation field
     * @return string - this will be an img tag if thumb:, a href tag if full:, or currently existing text.
     */
    public function replaceDigitalObjectRelations($text, $args)
    {
    	//If the relation has a full string, check to see if it has a thumb relation.  If so, then
    	//display the thumb and link it out to the full image.  Otherwise just make it a link.
    	if (preg_match(get_option('digitalobjectlinkerplugin_preg_full_image_string'), $text)) {
    		//Strip the full: from the text.
    		$fulllink = substr($text, strlen(get_option('digitalobjectlinkerplugin_full_image_tag')));
    		$fullid = $this->parseUniqueId($fulllink);
    		if ($fullid != 'no-id')
    		{
    			$fulllink = trim(str_replace($fullid.":", "", $fulllink));
    		}
    		
    		//Create the link with parameters.
    		$fulllinkwithparams = $fulllink . "?buttons=Y";
    		
    		//The only way that I could find to get all relations during the filter was to pull the relations from the database.
    		//Trying to pull from the metadata function seemed to throw this into an infinite loop.
    		//This first gets the element_id for the 'Relation' element from the 'Element' table (omeka_elements if you are looking in the db).
    		//Second, it then finds all of the 'Relation' element texts from the 'ElementText' table (omeka_element_texts) using
    		//the record ID which was passed in from the filter and the element_id that was retrieved.
    		$element = get_db()->getTable('Element')->findByElementSetNameAndElementName('Dublin Core', 'Relation');
    		$elementID = $element->id;
    		//Record ID that was passed in from the filter.
    		$recordID = $args['record']->id;
    		//We no longer need the element object that we retrieved so releas it.
    		release_object($element);
    		
    		//Create the select for the ElementText table.
    		$select = get_db()->select()->from(array(get_db()->ElementText),array('text'))
    			->where('record_id=' . $recordID . ' AND element_id = ' . $elementID);
    		
    		//Fetch all of the relations.  They come back as an array in this form:
    		//array(0 => array('text' => full:urn...), 1 => array('text' => thumb:urn....))
    		$relations = get_db()->getTable('ElementText')->fetchAll($select);
    		//Logger::log($relations);
    		//As long as at least one relation was returned, we can continue.
    		if (count($relations) > 0)
    		{
    			foreach ($relations as $relation)
    			{
    				//Make sure the relation is not the full relation that we are filtering.  If it isn't,
    				//check to see if it is the thumb relation.
    				if ($relation['text'] != $text
    						&& preg_match(get_option('digitalobjectlinkerplugin_preg_thumb_string'), $relation['text']))
    				{
    					//Create a thumb image that links out to the full image.
    					$thumblink = substr($relation['text'], strlen(get_option('digitalobjectlinkerplugin_thumb_tag')));
    					if (!empty($thumblink))
    					{
    						$thumbid = $this->parseUniqueId($thumblink);
    						if ($thumbid != 'no-id')
    						{
    							$thumblink = trim(str_replace($thumbid.":", "", $thumblink));
    						}
    						if ($fullid == $thumbid)
    						{	
		    					//Determine the width and height of the thumb.
					    		$width = is_admin_theme() ? get_option('digitalobjectlinkerplugin_width_admin') : get_option('digitalobjectlinkerplugin_width_public');
					    		
					    		return "<div class=\"item-relation\"><a href=\"" . $fulllinkwithparams . "\" target=\"_blank\"><img src=\"" . $thumblink . "\" alt=\"" . $thumblink . "\" height=\"" . $width . "\"></img></a></div>";
    						}
    					}
    				}
    			}
    		}
    		//If it reaches this point, the relations did not contain a thumbnail so return a plain link.
    		return "<a href=\"" . $fulllinkwithparams . "\" target=\"_blank\">" . $fulllink . "</a>";
    	}
    	//If the relation is not a thumb or full string, then just return the text.  As it stands now (Sept 2013), the only
    	//things being passed to this field are thumbs and full.
    	elseif (!preg_match(get_option('digitalobjectlinkerplugin_preg_thumb_string'), $text))
    	{
    		return $text;
    	}
    	return NULL;
    }  
    
    /**
     * This hides the relation field if it only has the image.  If additional
     * text is in the fields, then the field is displayed but the CSS hides the
     * image. This is useful if you choose to display your images in a different place on the 'show' page.
     * The default will display your images under the 'Relation' field
     * 
     * If you would like to activate this filter, add 'display_elements' to the $_filters array above.
     * 
     * The basis of this code was taken from the HideElementsPlugin.php.
     * @param  array $elementsBySet
     * @return array
     */
    public function filterDisplayElements($elementsBySet)
    {
    	if (is_admin_theme()) return $elementsBySet;
    	$elementTexts = array();
    	try {
    		$elementTexts = metadata('item', array('Dublin Core', 'Relation'), array('all' => true, 'no-filters'=>true));
    	}
    	catch (Exception $e)
    	{
    		try {
    			$elementTexts = metadata('colletion', array('Dublin Core', 'Relation'), array('all' => true, 'no-filters'=>true));
    		}
    		catch (Exception $e)
    		{
    		
    		}
    	}
    	$hide = true;
    	foreach ($elementTexts as $text)
    	{
    		if (!empty($text) && !preg_match("/<img/", $text))
    		{
    			$hide = false;
    		}
    	}
    	if ($hide)
    	{
    		unset($elementsBySet['Dublin Core']['Relation']);
    	}
    	return $elementsBySet;
    }

    /**
     * This hook is called when an admin user clicks 'save' on the 
     * configuration page for the plugin.
     * @throws Exception
     */
    function hookConfig()
    {
    	if (!is_numeric($_POST['digitalobjectlinkerplugin_width_admin']) ||
    			!is_numeric($_POST['digitalobjectlinkerplugin_width_public'])) {
    		throw new Exception('The width and height must be numeric.');
    	}
    	
    	set_option('digitalobjectlinkerplugin_embed_admin', (int) (boolean) $_POST['digitalobjectlinkerplugin_embed_admin']);
    	set_option('digitalobjectlinkerplugin_width_admin', $_POST['digitalobjectlinkerplugin_width_admin']);
    	set_option('digitalobjectlinkerplugin_embed_public', (int) (boolean) $_POST['digitalobjectlinkerplugin_embed_public']);
    	set_option('digitalobjectlinkerplugin_width_public', $_POST['digitalobjectlinkerplugin_width_public']);
    	set_option('digitalobjectlinkerplugin_items_page_width_public', $_POST['digitalobjectlinkerplugin_items_page_width_public']);
    	 
    	if (!empty($_POST['digitalobjectlinkerplugin_thumb_tag']))
    	{
    		set_option('digitalobjectlinkerplugin_thumb_tag', $_POST['digitalobjectlinkerplugin_thumb_tag']);
    		set_option('digitalobjectlinkerplugin_preg_thumb_string', "/^" . $_POST['digitalobjectlinkerplugin_thumb_tag'] . "([a-zA-Z0-9]*:){0,1}/");
    	}
    	if (!empty($_POST['digitalobjectlinkerplugin_full_image_tag']))
    	{
    		set_option('digitalobjectlinkerplugin_full_image_tag', $_POST['digitalobjectlinkerplugin_full_image_tag']);
    		set_option('digitalobjectlinkerplugin_preg_full_image_string', "/^" . $_POST['digitalobjectlinkerplugin_full_image_tag'] . "([a-zA-Z0-9]*:){0,1}/");
    	}
    	if (!empty($_POST['digitalobjectlinkerplugin_linkto_tag']))
    	{
    		set_option('digitalobjectlinkerplugin_linkto_tag', $_POST['digitalobjectlinkerplugin_linkto_tag']);
    		set_option('digitalobjectlinkerplugin_preg_linkto_string', "/^" . $_POST['digitalobjectlinkerplugin_linkto_tag'] . "([a-zA-Z0-9]*:){0,1}/");
    	}
    }
    
    /**
     * This displays the custom configuration form.
     */
    function hookConfigForm()
    {
    	include 'config_form.php';
    }
    
    /**
     * When installing the plugin initally, this sets the default values.
     */
    public function hookInstall()
    {
    	set_option('digitalobjectlinkerplugin_thumb_tag', self::DEFAULT_THUMB_TAG);
    	set_option('digitalobjectlinkerplugin_preg_thumb_string', "/^" . self::DEFAULT_THUMB_TAG . "/");
    	set_option('digitalobjectlinkerplugin_full_image_tag', self::DEFAULT_FULL_IMAGE_TAG);
    	set_option('digitalobjectlinkerplugin_preg_full_image_string', "/^" . self::DEFAULT_FULL_IMAGE_TAG . "/");
    	set_option('digitalobjectlinkerplugin_linkto_tag', self::DEFAULT_LINKTO_TAG);
    	set_option('digitalobjectlinkerplugin_preg_linkto_string', "/^" . self::DEFAULT_LINKTO_TAG . "/");
    	set_option('digitalobjectlinkerplugin_width_admin', self::DEFAULT_THUMB_WIDTH);
    	set_option('digitalobjectlinkerplugin_width_public', self::DEFAULT_THUMB_WIDTH);
    	set_option('digitalobjectlinkerplugin_items_page_width_public', self::DEFAULT_ITEM_PAGE_IMAGE_WIDTH);
    	
    	//Create a database table if one doesn't already exist for the image mappings
    	/* omeka_id: the omeka_id
    	 * thumbnail_url: the thumbnail url
    	* full_url: the full image url
    	*/
    	$sql = "
    	CREATE TABLE IF NOT EXISTS `{$this->_db->prefix}external_images` (
    	`external_image_id` int unsigned auto_increment NOT NULL,
    	`omeka_id` int unsigned NOT NULL,
    	`thumbnail_uri` varchar(500) NOT NULL,
    	`full_uri` varchar(500) NOT NULL,
    	`linkto_uri` varchar(500) NOT NULL,
    	`width` int unsigned NOT NULL default 0,
    	`height` int unsigned NOT NULL default 0,
    	PRIMARY KEY  (`external_image_id`)
    	) ENGINE=InnoDB;";
    	$this->_db->query($sql);
    	 
    }
    
    /**
     * When uninstalling, all set options are removed.
     */
    public function hookUninstall()
    {
    	delete_option('digitalobjectlinkerplugin_width_admin');
    	delete_option('digitalobjectlinkerplugin_width_public');
    	delete_option('digitalobjectlinkerplugin_items_page_width_public');
    	delete_option('digitalobjectlinkerplugin_thumb_tag');
    	delete_option('digitalobjectlinkerplugin_full_image_tag');
    	delete_option('digitalobjectlinkerplugin_linkto_tag');
    	set_option('digitalobjectlinkerplugin_embed_admin', 0);
    	set_option('digitalobjectlinkerplugin_embed_public', 0);
    	 
    }
    
    /**
     * When uninstalling, make sure thumbnails become invisible
     */
    public function hookDeactivate()
    {
    	set_option('digitalobjectlinkerplugin_embed_admin', 0);
    	set_option('digitalobjectlinkerplugin_embed_public', 0);
    
    }
    
    /**
     * After Save Record hook allows us to insert the thumb and full
     * NOTE - this assumes that there is only one of each per record.  
     */
    public function hookAfterSaveItem($args)
    {
    	$record = $args['record'];
    	//Parse out the Oasis ID from the Identifier link
    	$oasisid = NULL;
    	//Updating the record so get rid of the relations and start over
    	$deletesql = "DELETE FROM `{$this->_db->prefix}external_images` WHERE omeka_id=$record->id";
    	$this->_db->query($deletesql);
    			
    	$relations = metadata($record, array('Dublin Core', 'Relation'), array('all' => true, 'no_filter' => true));
    	
	    if (!is_null($relations))
		{
			//This array is of the form array(id=>array(thumb=><uri>,full=><uri>), id2=>array(thumb=><uri>,full=><uri>)) where
			//the id array may have a thumb and/or a full.
			$externalimages = array();
			
			foreach ($relations as $relation)
			{
				//If the relation has a full string, check to see if it has a thumb relation.  If so, then
				//display the thumb and link it out to the full image.  Otherwise just make it a link.
				if (preg_match(get_option('digitalobjectlinkerplugin_preg_full_image_string'), $relation)) {
					//Strip the drs:full: from the text.
					$fulllink = substr($relation, strlen(get_option('digitalobjectlinkerplugin_full_image_tag')));
					$id = $this->parseUniqueId($fulllink);
					if ($id != 'no-id')
					{
						$fulllink = trim(str_replace($id.":", "", $fulllink));
					}
					if (array_key_exists($id, $externalimages))
					{
						$externalimages[$id]['full'] = $fulllink;
					}
					else
					{
						$externalimages[$id] = array('full'=>$fulllink);
					}
				}
				//Thumb
				elseif (preg_match(get_option('digitalobjectlinkerplugin_preg_thumb_string'), $relation))
				{
					//Update the thumblink to have no dimensions.
					$thumblink = substr($relation, strlen(get_option('digitalobjectlinkerplugin_thumb_tag')));
					$id = $this->parseUniqueId($thumblink);

					if ($id != 'no-id')
					{
						$thumblink = trim(str_replace($id.":", "", $thumblink));
					}
					if (array_key_exists($id, $externalimages))
					{
						$externalimages[$id]['thumb'] = $thumblink;
					}
					else
					{
						$externalimages[$id] = array('thumb'=>$thumblink);
					}
					
				}
				//Linkto
				elseif (preg_match(get_option('digitalobjectlinkerplugin_preg_linkto_string'), $relation))
				{
					//Strip the drs:full: from the text.
					$linkto = substr($relation, strlen(get_option('digitalobjectlinkerplugin_linkto_tag')));
					$id = $this->parseUniqueId($linkto);
				
					if ($id != 'no-id')
					{
						$linkto = trim(str_replace($id.":", "", $linkto));
					}
					if (array_key_exists($id, $externalimages))
					{
						$externalimages[$id]['linkto'] = $linkto;
					}
					else
					{
						$externalimages[$id] = array('thumb'=>$thumblink);
					}
						
				}
			}

			foreach ($externalimages as $externalimage)
			{
				
				$resource = array_key_exists('thumb', $externalimage) && !empty($externalimage['thumb']) ? $this->imagecreatefromany($externalimage['thumb']) : FALSE;
				$full = array_key_exists('full', $externalimage) && !empty($externalimage['full']) ? $externalimage['full'] : NULL;
				//Use the full if a linkto does not exist.
				$linkto = array_key_exists('linkto', $externalimage) && !empty($externalimage['linkto']) ? $externalimage['linkto'] : $full;
				
				$imagewidth = 0;
				$imageheight = 0;
				$thumb = $full;
				if ($resource)
				{
					$imagewidth = imagesx($resource);
					$imageheight = imagesy($resource);
					$thumb = $externalimage['thumb'];
				}
				
				// insert
				$sql = "INSERT INTO {$this->_db->prefix}external_images (omeka_id, thumbnail_uri, full_uri, linkto_uri, `width`, `height`) VALUES ($record->id, '$thumb', '$full', '$linkto', $imagewidth, $imageheight);";
				$this->_db->query($sql);
			}
		}
 
    	
    }
    
    /**
     * Pulls the unique id from the string
     * @param string $string
     * @return string - the unique id, FALSE if one does not exist
     */
    private function parseUniqueId($string)
    {
    	if (strpos($string, "http:") !== 0 && strpos($string, "https:") !== 0 && preg_match("/^[a-zA-Z0-9]*:/", $string, $matches)) {
    		
    		//Strip the : from the matched id.
    		return str_replace(":", "", $matches[0]);
    	}
    	return 'no-id';
    }
    
    /**
     * Get the image info from the given image url
     * @param string $filepath
     * @return array of image info, false if the exif type is not supported or if the url doesn't provide an image.
     */
    private function imagecreatefromany($filepath) {
    	$type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
    	if (!$type)
    	{
    		return $type;
    	}
    	$allowedTypes = array(
    			1,  // [] gif
    			2,  // [] jpg
    			3,  // [] png
    			6,   // [] bmp
    			10  // [] jp2
    	);
    	if (!in_array($type, $allowedTypes)) {
    		return false;
    	}
    	switch ($type) {
    		case 1 :
    			$im = imagecreatefromgif($filepath);
    			break;
    		case 2 :
    			$im = imagecreatefromjpeg($filepath);
    			break;
    		case 3 :
    			$im = imagecreatefrompng($filepath);
    			break;
    		case 6 :
    			$im = imagecreatefrombmp($filepath);
    			break;
    		case 6 :
    			$im = imagecreatefromjpeg($filepath);
    			break;
    	}
    	return $im;
    }
    
    /**
     * Deletes the oasis id associated with a deleted record.
     *
     * @param Omeka_Record_AbstractRecord record
     */
    public function hookBeforeDeleteItem($args)
    {
    	$record = $args['record'];
    	//Delete the row in the oasis table
    	$sql = "DELETE FROM `{$this->_db->prefix}external_images` WHERE omeka_id=$record->id";
    	$this->_db->query($sql);
    }
    
}
