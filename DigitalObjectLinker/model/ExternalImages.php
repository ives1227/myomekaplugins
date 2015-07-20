<?php
/**
 * The ExternalImages record class.
 *
 */
class ExternalImages extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
	public $omeka_id;
	public $thumbnail_uri;
	public $full_uri;
	public $linkto_uri;
	public $width;
	public $height;
	
	public function getResourceId()
	{
		return 'ExternalImages';
	}
}
