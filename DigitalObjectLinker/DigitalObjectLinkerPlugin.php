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
	
	//The default thumbnail size.
	const DEFAULT_THUMB_WIDTH = 200;
	
	
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('config', 
                              'config_form',
    						  'uninstall',
    		        		  'install');

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('replaceDigitalObjectRelations' => array('Display', 'Item', 'Dublin Core', 'Relation'),
    							'display_elements');

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
	    					//Determine the width and height of the thumb.
				    		$width = is_admin_theme() ? get_option('digitalobjectlinkerplugin_width_admin') : get_option('digitalobjectlinkerplugin_width_public');
				    		
				    		return "<div class=\"item-relation\"><a href=\"" . $fulllink . "\" target=\"_blank\"><img src=\"" . $thumblink . "\" alt=\"" . $thumblink . "\" height=\"" . $width . "\"></img></a></div>";
    					}
    				}
    			}
    		}
    		//If it reaches this point, the relations did not contain a thumbnail so return a plain link.
    		return "<a href=\"" . $fulllink . "\" target=\"_blank\">" . $fulllink . "</a>";
    	}
    	//If the relation is not a thumb or full string, then just return the text.  As it stands now (Sept 2013), the only
    	//things being passed to this field are thumbs and full.
    	elseif (!preg_match(get_option('digitalobjectlinkerplugin_preg_thumb_string'), $text))
    	{
    		return $text;
    	}
    	else
    	{
    		return "<div></div>";
    	}
    }  
    
    /**
     * This hides the relation field if it only has the image.  If additional
     * text is in the fields, then the field is displayed but the CSS hides the
     * image.
     * 
     * The basis of this code was taken from the HideElementsPlugin.php.
     * @param  array $elementsBySet
     * @return array
     */
    public function filterDisplayElements($elementsBySet)
    {
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
    	
    	if (!empty($_POST['digitalobjectlinkerplugin_thumb_tag']))
    	{
    		set_option('digitalobjectlinkerplugin_thumb_tag', $_POST['digitalobjectlinkerplugin_thumb_tag']);
    		set_option('digitalobjectlinkerplugin_preg_thumb_string', "/^" . $_POST['digitalobjectlinkerplugin_thumb_tag'] . "/");
    	}
    	if (!empty($_POST['digitalobjectlinkerplugin_full_image_tag']))
    	{
    		set_option('digitalobjectlinkerplugin_full_image_tag', $_POST['digitalobjectlinkerplugin_full_image_tag']);
    		set_option('digitalobjectlinkerplugin_preg_full_image_string', "/^" . $_POST['digitalobjectlinkerplugin_full_image_tag'] . "/");
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
    	set_option('digitalobjectlinkerplugin_width_admin', self::DEFAULT_THUMB_WIDTH);
    	set_option('digitalobjectlinkerplugin_width_public', self::DEFAULT_THUMB_WIDTH);
    }
    
    /**
     * When uninstalling, all set options are removed.
     */
    public function hookUninstall()
    {
    	delete_option('digitalobjectlinkerplugin_width_admin');
    	delete_option('digitalobjectlinkerplugin_width_public');
    	delete_option('digitalobjectlinkerplugin_thumb_tag');
    	delete_option('digitalobjectlinkerplugin_full_image_tag');
    }
    
}
