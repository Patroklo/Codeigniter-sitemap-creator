<?php
class sitemaper_model extends CI_Model {

  private $block;

  function sitemaper_model() {
  
    // Call the Model constructor
    parent::__construct();
  	$this->load->helper('sitemap_lists');
	$this->load->library('sitemaper_builder');
  } 
  
  function makeSitemapBlock($new_block, $datos = NULL)
  {
  		if(class_exists($new_block) and is_subclass_of($new_block, 'urlList'))
		{
		  	if($datos == NULL)
		  	{
		  		$this->block = new $new_block();
			}
			

			$this->sitemaper_builder->builder(array('archive' => $this->block->getFileName(), 'type' => $this->block->getType()));
			$this->generateSitemap();
			return TRUE;
		}
		else
		{
			return FALSE;
		}
  }
  
  function generateSitemap()
  {

  		while($this->block->makeQuery())
		{
			$rowsList =& $this->block->getList();

			$this->sitemaper_builder->insertLines($rowsList);
		}
		
		$this->sitemaper_builder->close(true);
  }
  
   
 } 
?>