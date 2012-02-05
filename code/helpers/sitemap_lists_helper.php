<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

abstract class urlList
{
	protected $CI;
	//esta variable limita el número máximo de rows que se obtendrán con cada query
	//es necesaria a la hora trabajar con tablas que tengan grandes cantidades de datos
	//ya que podrían causar un overflow de memoria.
	
	//this variable limit the max number of rows that will be fetched with each query
	//it's neccesary for working with tables that hold large quantity of data because
	//it could make a memory overflow.
	protected $maxRows = 1000;
	
	//offset usado junto con $maxRows para hacer las querys
	//offset used alongside $maxRows to make the querys
	protected $offset = 0;
	
	//esta variable almacena la lista obtenida en las querys y será el dato que se envíe al sitemap builder
	
	//this variable stores the list of query results and will be the data sent to the sitemap builder
	protected $list = NULL;
	
	//un string que contiene el nombre de la tabla que se accederá para crear la query
	
	//string that holds the table name that will be used to make the query.	
	protected $table;
	
	//string que contiene la dirección base del url de la web
	
	//string that holds the base url direction of the website
	protected $url_base;

	//string con el nombre del archivo que se va a crear para introducirle el xml
	
	//string that holds the file name in wich we will insert the xml
	protected $fileName = NULL;
	
	//tipo de sitemap que se creará. Puede ser un 'sitemap' o un 'sitemapIndex'
	
	//type of sitemap we will make. It can be a 'sitemap' or a 'sitemapIndex'
	protected $type = 'sitemap';
	
	
	//array que contiene la lista de etiquetas que se generarán con la query y se enviarán al generador de sitemap
	
	//array list that holds the labels that will be generated in the query and will be sent to the sitemap generator
	protected $fields = array();
	protected $sqlSelect = NULL;
	protected $query;
	
	private $row_recursive;
	
	function __construct()
	{
		$this->CI =& get_instance(); 
		$this->sqlSelect = substr($this->makeSelect(),0,-1);
		
	}
	//función llamada para filtrar los datos de la query de la base de datos que se retornarán como datos sin tratar del sitemap
	
	//function called to filter the data of the database query that will return the sitemap raw data
	abstract function filters();
	
	
	//lanza la query que obtiene la lista con los datos del sitemap. Está diseñada para lanzarse múltiples veces hasta que la tabla de datos esté completamente recorrida y retorne un false
	
	//launches the query that gets the sitemap data list. It's designed to be called multiple times until the datatable it's fully loaded and returns false.
	function makeQuery()
	{
			if($this->sqlSelect == null)
			{
				throw new Exception('There are no loaded fields in the class.');
			}
			$this->CI->db->select($this->sqlSelect, false);
			$this->filters();
			$this->CI->db->limit($this->maxRows, $this->offset);
			$this->CI->db->from($this->table);
			$this->query = $this->CI->db->get();
			
			$this->offset += $this->maxRows;
			
			if($this->query->num_rows() > 0)
			{
				$this->getData();
				return true;
			}
			else
			{
				return false;
			}
	}
	
	//forma el array de list que será el que se envie a sitemap builder con todos los datos obtenidos por la query
	
	//makes the array list that will be sent to the sitemap builder with all the data fetched in the query.
	function getData()
	{
		$hasArrays = false;
		foreach($this->fields as $row)
		{
			if(is_array($row))
			{
				$hasArrays = true;
			}
		}
		if($hasArrays == false)
		{
			$this->list = $this->query->result_array();
		}
		else 
		{
	  		foreach($this->query_array() as $this->row_recursive)
			{
				$this->list[] = $this->getDataRecursive();
			}
		}
	}
	
	private function getDataRecursive($arr = array(), $key = NULL, $rec = false)
	{	
		if($rec == false)
		{
			$arr = $this->fields;
		}
		
		$new_row = array();
		
		if(is_array($arr))
		{
			foreach($arr as $key => $row)
			{
				$new_row[$key] = loadList($row, $key, true);
			}
		}
		else
		{
			$new_row = $this->row_recursive[$key];
		}

		return $new_row;
	}

	private function makeSelect($arr = array(), $key = NULL, $rec = false)
	{
		if($rec == false)
		{
			$arr = $this->fields;
		}
		
		$sql = '';
		
		if(is_array($arr))
		{
			foreach($arr as $key => $row)
			{
				$sql.= $this->makeSelect($row, $key, true);
			}
		}
		else 
		{
		 	$sql = $arr.' as '.$key.',';
		}
		return $sql;
	}
	
	
	//retorna el puntero de la lista de rows obtenidas, para ahorrar memoria y tiempo
	
	//returns the pointer to the fetched rows, to save memory and tiem
	function &getList()
	{
		return $this->list;
	}
	
	//retorna el nombre de archivo de la lista
	
	//return the filename of the list
	function getFileName()
	{
		return $this->fileName;
	}
	
	//retorna el tipo de la lista
	
	//returns the type list
	function getType()
	{
		return $this->type;
	}

}