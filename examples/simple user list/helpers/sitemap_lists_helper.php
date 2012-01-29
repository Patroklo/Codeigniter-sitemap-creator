<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

abstract class urlList
{
	protected $CI;
	
	protected $maxRows = 1000;
	
	protected $list = NULL;
	
	protected $table;
	
	protected $url_base;

	protected $offset = 0;
	
	protected $fileName = NULL;
	
	protected $images = FALSE;
	
	protected $type = 'sitemap';
	
	function __construct()
	{
		$this->CI =& get_instance(); 
	}
	
	abstract function filters();
	
	function makeQuery()
	{
			$this->filters();
			$this->CI->db->limit($this->maxRows, $this->offset);
			$this->CI->db->from($this->table);
			$query = $this->CI->db->get();
			
			$this->offset += $this->maxRows;
			
			if($query->num_rows() > 0)
			{
				$this->list = $query->result();
				return true;
			}
			else
			{
				return false;
			}
	}
	
	//return the pointer of the list
	function &getList()
	{
		return $this->list;
	}
	
	function getFileName()
	{
		return $this->fileName;
	}

	function getType()
	{
		return $this->type;
	}

}

class usersList extends urlList
{
	protected $table = 'us_regs';
	protected $fileName = 'sitemaps/sitemap_users.xml';

	function __construct()
	{
			parent::__construct();
			$this->url_base = base_url().'user/';
	}
	
	function filters()
	{}
	
	function makeQuery()
	{
	 	$this->CI->db->select("concat('".$this->url_base."', user, '/' ) as loc, DATE_FORMAT(fechareg, '%Y-%m-%d') as lastmod, 'monthly' as changefreq", false);
		return parent::makeQuery();
	}
	
}