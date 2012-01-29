<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


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