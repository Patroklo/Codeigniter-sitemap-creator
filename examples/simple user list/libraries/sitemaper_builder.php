<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class sitemaper_builder
{
	
	private $fichero = NULL;
	
	private $fileName = '';
	private $fileNumber = '';
	
	private $buffer;
	
	private $loadedType = '';
	private $xml_data = NULL;
	
	
	private $namespaces = array(
								'image:image' => "http://www.google.com/schemas/sitemap-image/1.1",
								'video:video' => "http://www.google.com/schemas/sitemap-video/1.1",
								'mobile:mobile' => "http://www.google.com/schemas/sitemap-mobile/1.0",
								'geo:geo' => "http://www.google.com/geo/schemas/sitemap/1.0",
								'news:news' => "http://www.google.com/schemas/sitemap-news/0.9",
								'codesearch:codesearch' => "http://www.google.com/codesearch/schemas/sitemap/1.0"
								);
	
	private $headers = array('sitemap' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" 
												xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\"
												xmlns:video=\"http://www.google.com/schemas/sitemap-video/1.1\"
												xmlns:mobile=\"http://www.google.com/schemas/sitemap-mobile/1.0\"
												xmlns:geo=\"http://www.google.com/geo/schemas/sitemap/1.0\"
												xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\"
												xmlns:codesearch=\"http://www.google.com/codesearch/schemas/sitemap/1.0\">\n\t</urlset>",
						'sitemapIndex' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\t<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n\t</sitemapindex>");		   
	private $labels = array('sitemap' => 'url',
							'sitemapIndex' => 'sitemap');
	private $contadorInterno = 0;		
	
	private $config; //guarda la configuración de campos especiales para el sitemap, como por ejemplo image	   
	
	/*
	 * 
	 *@input $params['archive'] => (string) archive where the xml will be stored
	 *@input $params['type] => (string) type of xml for the header and the open and ending labels
	 * 							accepted: xml, sitemap, sitemapIndex
	 *@input $params['overwrite'] => (boolean) tells if the archive will be overwritten or not. Default true.
	 */
	
	function builder($params)
	{
		if(!isset($params['archive']))
		{
			throw new Exception('You have to define the sitemap file.');
		}
		
		if(isset($params['type']))
		{
			$this->loadedType = $params['type'];
		}
		else {
			$this->loadedType = 'sitemap';
		} 

		if(!isset($params['overwrite']))
		{
			$params['overwrite'] = true;
		}
	
		if(!array_key_exists($this->loadedType, $this->headers))
		{
			$this->close();
			return $this->loadedType." it's not a valid sitemap type.";
		}
		else
		{
			$this->inicialize($params['archive'], $params['overwrite']);
		}
		
			


	}
	
	private function inicialize($archive, $overwrite)
	{
				
			if($this->xml_data == NULL)
			{
				$arrFichero = explode('.',$archive);
				
				if(count($arrFichero) == 1)
				{
					throw new Exception($archive.' is not a valid filename.');					
				}
				
				$this->fileName = $arrFichero[0].$this->fileNumber.'.'.$arrFichero[1];
				if($this->fileNumber == '')
				{
					$this->fileNumber = 1;
				}
				else
				{
					$this->fileNumber += 1;
				}
			
				//check if directory exists, if not, create it
				$directory = dirname($this->fileName);
				if(!file_exists($directory))
				{
					mkdir($directory);
				}
				
				if(($overwrite == true) || ($overwrite == false and !file_exists($this->fileName)))
					{
						$this->xml_data = new SimpleXMLElement($this->headers[$this->loadedType]);
					}
				else
					{
						$this->xml_data = new SimpleXMLElement($this->fileName);
					}			

			}
	}
	
	function insertLines($var = array())
	{
		$this->buffer = '';
		
		$cuenta = count($var);
		
		if(($this->contadorInterno + $cuenta) > 50000)
		{
			$this->contadorInterno = 0;
			$this->cierra();
			$this->inicializa($this->fileName, false);
		}
		else
		{
			$this->contadorInterno += $cuenta;
		}
		
		foreach($var as $value)
		{
			$subnode = $this->xml_data->addChild($this->labels[$this->loadedType]);	
			foreach($value as $key => $data)
			{
				if(!is_array($data))
				{
					$subnode->addChild("$key",$this->xml_convert($data));
				}
				else
				{
					$subnode2 = $subnode->addChild("$key", '', $this->namespaces[$key]);
					foreach($data as $key2 => $data2)
					{
						if(is_array($data2))
						{
							$this->array_to_xml($data2, $subnode2);
						}
						else
						{
							$subnode2->addChild("$key2",$this->xml_convert($data2));
						}
					}
			
				}
			}
			

		}
	}

	function array_to_xml($vars, &$xml_data) 
	{
	    foreach($vars as $key => $value) 
	    {
		    if(is_array($value)) {
	            if(!is_numeric($key)){
	                $subnode = $xml_data->addChild("$key", '', $this->namespaces[$key]);
	                $this->array_to_xml($value, $subnode);
	            }
	            else{
	                $this->array_to_xml($value, $xml_data);
	            }
	        }
	        else {
	            $xml_data->addChild("$key",$this->xml_convert($value));
	        }
	    }
	}

	function close($send_ping = false)
	{
		if($this->xml_data !== NULL)
		{		
			$this->xml_data->asXML($this->fileName);	
			
			if($send_ping == true)
			{
				$this->_pingGoogleSitemaps();
			}
			
			$this->fichero = NULL;
			$this->fileName = '';
			$this->fileNumber = '';
			$this->buffer = '';
			$this->loadedType = '';
			$this->contadorInterno = 0;
			$this->xml_data = NULL;
		}
		else {
			throw new Exception('Error, the file '.$this->fileName.' it\'s not openned.');
		}
		
	}
	
	function xml_convert($str, $protect_all = FALSE)
	{
		$temp = '__TEMP_AMPERSANDS__';

		// Replace entities to temporary markers so that
		// ampersands won't get messed up
		$str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);

		if ($protect_all === TRUE)
		{
			$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
		}

		$str = str_replace(array("&","<",">","\"", "'", "-"),
							array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
							$str);

		// Decode the temp markers back to entities
		$str = preg_replace("/$temp(\d+);/","&#\\1;",$str);

		if ($protect_all === TRUE)
		{
			$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		}

		return $str;
	}
	
	
	function _pingGoogleSitemaps()
    {
       if($this->fileName !=='')
	   {
	   		$status = 0;
		       $google = 'www.google.es';
		       if( $fp=@fsockopen($google, 80) )
		       {
		          $req =  'GET /webmasters/sitemaps/ping?sitemap=' .
		                  urlencode( base_url().$this->fileName ) . " HTTP/1.1\r\n" .
		                  "Host: $google\r\n" .
		                  "User-Agent: Mozilla/5.0 (compatible; " .
		                  PHP_OS . ") PHP/" . PHP_VERSION . "\r\n" .
		                  "Connection: Close\r\n\r\n";
		          fwrite( $fp, $req );
		          while( !feof($fp) )
		          {
		             if( @preg_match('~^HTTP/\d\.\d (\d+)~i', fgets($fp, 128), $m) )
		             {
		                $status = intval( $m[1] );
		                break;
		             }
		          }
		          fclose( $fp );
		       }
		       return( $status );
	   }
	else
		{
			return "Error, no hay ningún fichero cargado.";
		}
       
      
      
    }
	
}
