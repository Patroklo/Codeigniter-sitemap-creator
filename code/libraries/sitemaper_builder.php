<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class sitemaper_builder
{
	
	private $fichero = NULL;
	
	private $nombreFichero = '';
	private $numeroFichero = '';
	
	private $buffer;
	
	private $tipoCargado = '';
	
	private $tiposAceptados = array('sitemap', 'sitemapIndex');
	
	private $cabeceras = array('sitemap' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n\t",
						'sitemapIndex' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\t<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n\t");
	
	private $colas = array('sitemap' => "</urlset>",
					   'sitemapIndex' => '</sitemapindex>');
					   
	private	$etiquetas = array('sitemap' => array('<url>', '</url>'),
								'sitemapIndex' => array('<sitemap>', '</sitemap>'));
	private $contadorInterno = 0;		
	
	private $config; //guarda la configuración de campos especiales para el sitemap, como por ejemplo image	   
	
	function builder($params)
	{
		if(!isset($params['archive']))
		{
			throw new Exception('You have to define the sitemap file.');
		}
		
		if(isset($params['type']))
		{
			$this->tipoCargado = $params['type'];
		}
		else {
			$this->tipoCargado = 'sitemap';
		} 

			if(!in_array($this->tipoCargado, $this->tiposAceptados))
			{
				$this->close();
				return $this->tipoCargado." it's not a valid sitemap type.";
			}
			else
			{
				$this->inicialize($params['archive']);
			}


	}
	
	private function inicialize($archive)
	{
				
			if($this->fichero == NULL)
			{
				$arrFichero = explode('.',$archive);
				
				if(count($arrFichero) == 1)
				{
					throw new Exception($archive.' is not a valid filename.');					
				}
				
				$this->nombreFichero = $arrFichero[0].$this->numeroFichero.'.'.$arrFichero[1];
				if($this->numeroFichero == '')
				{
					$this->numeroFichero = 1;
				}
				else
				{
					$this->numeroFichero += 1;
				}
			
				//check if directory exists, if not, create it
				$directory = dirname($this->nombreFichero);
				if(!file_exists($directory))
				{
					mkdir($directory);
				}

					$this->fichero = fopen($this->nombreFichero, 'w');
					$this->makeHeader();
			}
	}
	
	function makeHeader()
	{
		if($this->fichero != NULL)
		{
			fwrite($this->fichero, $this->cabeceras[$this->tipoCargado]);
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
			$this->inicializa($this->nombreFichero);
		}
		else
		{
			$this->contadorInterno += $cuenta;
		}
		//tener dos bucles es un poco tonto, pero así nos ahorramos tiempo de procesamiento 
		//en los casos en los que el tipo de sitemap no sea especial.
		
			foreach($var as $dato)
			{
				$this->buffer .= $this->etiquetas[$this->tipoCargado][0]."\n\t";
					foreach($dato as $etiqueta => $valor)
					{
						if(is_array($valor))
						{
							$this->buffer.="<$etiqueta>\n\t";
							foreach($valor as $etiqueta2 => $valor2)
							{
								$this->buffer.= "<$etiqueta2>".$this->xml_convert($valor2)."</$etiqueta2>\n\t";
							}
							$this->buffer.="</$etiqueta>\n\t";
						}		
						else 
						{
							$this->buffer.= "<$etiqueta>".$this->xml_convert($valor)."</$etiqueta>\n\t";
							
						}
					}
				$this->buffer.= $this->etiquetas[$this->tipoCargado][1]."\n\t";
			}
			
		fwrite($this->fichero, $this->buffer);
	}

	function close($send_ping = false)
	{
		if($this->fichero !== NULL)
		{
			fwrite($this->fichero, $this->colas[$this->tipoCargado]);
			fclose($this->fichero);
			
			if($send_ping == true)
			{
				$this->_pingGoogleSitemaps();
			}
			
			$this->fichero = NULL;
			$this->nombreFichero = '';
			$this->numeroFichero = '';
			$this->buffer = '';
			$this->tipoCargado = '';
			$this->contadorInterno = 0;
		}
		else
		{
			throw new Exception("Error, no hay ningún fichero abierto.", 1);
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
       if($this->nombreFichero !=='')
	   {
	   		$status = 0;
		       $google = 'www.google.es';
		       if( $fp=@fsockopen($google, 80) )
		       {
		          $req =  'GET /webmasters/sitemaps/ping?sitemap=' .
		                  urlencode( base_url().$this->nombreFichero ) . " HTTP/1.1\r\n" .
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
