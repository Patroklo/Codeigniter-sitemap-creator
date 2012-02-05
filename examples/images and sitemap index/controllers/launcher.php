<?php
  
class launcher extends CI_Controller {

	
	
   function __construct()
       {
     		parent::__construct();
	   }
	        
	function index()
	{
		$this->load->model('sitemaper_model');
		$this->sitemaper_model->makeSitemapBlock('imagesList');
		$this->sitemaper_model->makeSitemapBlock('blogList');
		$this->sitemaper_model->makeSitemapBlock('sitemap_builder');
	}
}