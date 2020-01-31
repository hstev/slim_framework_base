<?php
//echo md5("4PIOT"); hash 
ini_set('display_errors', true);
//error_reporting(0);
//error_reporting(E_ALL);
ini_set('max_execution_time', 30000000);
//date_default_timezone_set('Europe/Madrid');


class Functions{
	
    public function __construct()
    {
        Functions::validateToken(Functions::request('token'));
    }


    public function validateToken($token)
    {
        if($token != "bxr45987vbn")
		{
			$msg['msg'] = "Su token es incorrecto";
			Functions::generateJson($msg);	
			die();
		}
    }

    public function getUserIpAddr(){
	    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
	        //ip from share internet
	        $ip = $_SERVER['HTTP_CLIENT_IP'];
	    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        //ip pass from proxy
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }else{
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}

	public function registry($url=null,$httpResponse=null,$fnc=null,$peticion='',$respuesta=''){
		$table = 'log_request';
		$fields[] = 'Url';
		$fields[] = 'Fecha_Hora';
		$fields[] = 'Ip';
		$fields[] = 'Http_Response';
		$fields[] = 'Fnc';
		$fields[] = 'Peticion';
		$fields[] = 'Respuesta';

		$values[] = $url;
		$values[] = date('Y-m-d H:i:s');
		$values[] = Functions::getUserIpAddr();
		$values[] = $httpResponse;
		$values[] = $fnc;
		$values[] = $peticion;
		$values[] = $respuesta;


		Functions::insert($table,$fields,$values);

		//$callback($peticion,$httpResponse,__FUNCTION__);
	}

	public function current_url()
    {
    	//$url      = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $url      = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        //$validURL = str_replace("&", "&amp", $url);
        return $url;
    }

	


    //prepara la consulta
	/*    public function prepare($sql)
	    {
	        return Functions::prepare($sql);
	    }
	*/



    //prepara la Query
    public function query($sql)
    {
        $dbh = connect();
		$Query = $dbh->prepare($sql);
        $Query->execute();
		 return $Query->rowCount();
    }

    public function generateJson($elArray)
    {
        header('Content-type: application/json');
        echo json_encode($elArray);
    }
	
	 public function capturateJson($url)
    {
		$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);  
		
		$urlData = file_get_contents($url, false, stream_context_create($arrContextOptions));
		//$urlData = file_get_contents($url); 
		return json_decode($urlData, true); 
	}
	
	public function capturateJsonCurl($url)
	{
		$ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec( $ch );
        $response = utf8_encode($response);
        curl_close($ch);

        return json_decode($response, true);
	}


	public function request($variable)
	{
		if(isset($_REQUEST[$variable]))
		{
			return $_REQUEST[$variable];
		} else
		{
			return "";
		}
	}


	public function requestFile($variable)
	{
		if(isset($_FILES[$variable]))
		{
			return $_FILES[$variable];
		} else
		{
			return "";
		}
	}

    public function insert($table, $fields, $values)
    {
        $dbh = connect();
		
		$buildFields = '';
        if (is_array($fields)) {

            foreach ($fields as $key => $field):
                if ($key == 0) {
                    $buildFields .= $field;
                } else {
                    $buildFields .= ', ' . $field;
                }
            endforeach;

        } else {
            $buildFields .= $fields;
        }

        $buildValues = '';
        if (is_array($values)) {

           foreach ($values as $key => $value):
               if(is_array($value))
               {
                   if($key == 0)
                   {
                       $buildValues = 'VALUES ';
                   }else{
                       $buildValues .= ', ';
                   }
                   foreach ($value as $k2 => $v2) {
                   	//echo json_encode($v2).'<br><br>';
                      if(is_array($v2))//[?] => array
                       {
                            foreach ($v2 as $k3 => $v3) {
                                if ($k3 == 0) {
                                    $buildValues .= " ( '".$v3."'";
                                } else if ($k3 == count($v2)-1){
                                    $buildValues .= ", '".$v3."' )";
                                } else {
                                    $buildValues .= ", '".$v3."'";
                                }
                            }

                            if($k2 == count($value)-1)
                            {
                                $buildValues .= '';
                            }else{
                                $buildValues .= ',';
                            }
                       }else{
                            if ($k2 == 0) {
                                $buildValues .= " ( '".$v2."'";
                            } else if ($k2 == count($value)-1){
                                $buildValues .= ", '".$v2."' )";
                            } else {
                                $buildValues .= ", '".$v2."'";
                            }
                        }
                   }
               }else{
                    if ($key == 0) {
                        $buildValues .= 'VALUES ( ?';
                    } else if ($key == count($values)-1){
                        $buildValues .= ', ? )';
                    }else {
                        $buildValues .= ', ?';
                    }
                }
                $values_title = $buildValues;
           endforeach;

       } else {
           $buildValues .= ':value';
           $values_title = 'VALUES (' . $buildValues . ')';
       }

     
       $prepareInsert = $dbh->prepare('INSERT INTO ' . $table . ' (' . $buildFields . ') '.$values_title);
      
       if (is_array($values)) {
           if(is_array($values[0]))
           {
               $prepareInsert->execute();    
           }else{
               $prepareInsert->execute($values);
           }
       } else {
           $prepareInsert->execute(array(
               ':value' => $values
           ));
       }

       $error = $prepareInsert->errorInfo();


       return $dbh->lastInsertId();

       echo json_encode(array('msj'=>'Se ha realizado el insert'));

    }


	public function doUpdate($table, $fields, $values, $whereFields, $whereValues)
	{
			$dbh = connect();
			$set = '';
			$x = 1;
			$y = 1;
			$whereField = "";
			
			foreach($fields as $name => $value){
			  $set .="{$value} = ?";
			  if($x < count($fields)){
				$set .= ', ';
			  }
			  $x++;
			}
			
			foreach($whereFields as $nameb => $valueb){
			  $whereField .="{$valueb} = ?";
			  if($y < count($whereFields)){
				$whereField .= ' and ';
			  }
			  $y++;
			}
			
			$sql = "update {$table} set {$set} WHERE {$whereField}";
			$stmt = $dbh->prepare($sql);
			$g = 0;
			for($i=0; $i<count($values); $i++)
			{
				$g++;
				$stmt->bindParam($g, $values[$i]);
				
			}
			$f = $g;
			for($i=0; $i<count($whereValues); $i++)
			{
				$f++;
				$stmt->bindParam($f, $whereValues[$i]);
				
			}
			 
			 return $stmt->execute();
	}


	public function doDelete($table, $whereFields, $values)
	{
		$dbh = connect();
		$whereField = '';
		$field = '';
		$x = 1;
		$y = 1;

	
		foreach($whereFields as $nameb => $valueb){
		  $whereField .="{$valueb} = ?";
		  if($y < count($whereFields)){
			$whereField .= ' and ';
		  }
		  $y++;
		}
		
		$sql = "delete from {$table} where {$whereField}";	

		$stmt = $dbh->prepare($sql);
		for($i=0; $i<count($values); $i++)
		{
			$stmt->bindParam($i+1, $values[$i]);
		}
		//$stmt->bindParam($i+1, $id);
		return $stmt->execute();	
	}


	
    public function recordCount($fields, $table, $whereFields, $values)
    {
      $dbh = connect();
	  try {
		  
		$whereField = '';
		$field = '';
		$x = 1;
		$y = 1;
		foreach($fields as $name => $value){
		  $field .="{$value}";
		  if($x < count($fields)){
			$field .= ', ';
		  }
		  $x++;
		}
	
		foreach($whereFields as $nameb => $valueb){
		  $whereField .="{$valueb} = ?";
		  if($y < count($whereFields)){
			$whereField .= ' and ';
		  }
		  $y++;
		}
		
		$sql = "Select {$field} from {$table} where {$whereField}";	
		  
		$query = $dbh->prepare($sql);
		for($i=0; $i<count($values); $i++)
		{
			$query->bindParam($i+1, $values[$i]);
		}

          $query->execute();
          return $query->rowCount();
      }
      catch (PDOException $e) {
          print "Error!: " . $e->getMessage();
      }
    }



    public function records($fields, $table, $innerJoins="", $whereFields, $values, $fetchType="")
    {
        $dbh = connect();
	    try {
		$whereField = '';
		$field = '';
		$x = 1;
		$y = 1;
		foreach($fields as $name => $value){
		  $field .="{$value}";
		  if($x < count($fields)){
			$field .= ', ';
		  }
		  $x++;
		}
	
		foreach($whereFields as $nameb => $valueb){
		  $whereField .="{$valueb} = ?";
		  if($y < count($whereFields)){
			$whereField .= ' and ';
		  }
		  $y++;
		}
		
		$sql = "Select {$field} from {$table} {$innerJoins} where {$whereField}";	

		$query = $dbh->prepare($sql);
		for($i=0; $i<count($values); $i++)
		{
			$query->bindParam($i+1, $values[$i]);
		}
		//$query->bindParam($i+1, $id);  
		$query->execute();

            if ($query->rowCount() > 0) {
                switch ($fetchType) {
                    case 'FETCH_ARRAY':
                        return $query->fetchAll(PDO::FETCH_NUM);
                        break;
                    case 'FETCH_ASSOC':
                        return $query->fetchAll(PDO::FETCH_ASSOC);
                        break;
                    default:
                        return $query->fetchAll();
                        break;
                }
            } else
			{
					
			}

        }
        catch (PDOException $e) {
            print "Error!: " . $e->getMessage();
        }
    }

    public function formatNumber($number){
  		return number_format((float)$number, 2, ',', '.'); //'$'.number_format($number);
  	}

	public function recolect($POST){
		try {
			$datos;
			foreach($POST as $nombre_campo => $valor){
				$datos[$nombre_campo] = $valor;
			}
			return $datos;
		}
		catch(PDOException $e){print "Error!: " . $e->getMessage();}
	}

	public function crypt_blowfish($password, $digit = 7) {
		$set_salt = './1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$salt = sprintf('$2a$%02d$', $digit);
		for($i = 0; $i < 22; $i++)
		{
		 $salt .= $set_salt[mt_rand(0, 22)];
		}
		return crypt($password, $salt);
	}


	public function encryptNow($string, $theKey)
	{
		$method = 'AES-256-CBC';
		$iv = "1234567891234567";
		if($theKey == "")
		{
			$theKey = '83493932';	
		}
		return openssl_encrypt($string, $method, $theKey, false, $iv);
	}

	public function decryptNow($string, $theKey)
	{
		$method = 'AES-256-CBC';
		$iv = "1234567891234567";
		if($theKey == "")
		{
			$theKey = '83493932';	
		}
		
		return openssl_decrypt($string, $method, $theKey, false, $iv);	
		
	}


	public function createFile($string, $namFile)
	{
		$file = fopen("files/".$namFile, "w") or die("Unable to open file!");
		fwrite($file, $string);
		fclose($file);	
	}



	function uploadFile($path="", $file="", $codFile=""){

		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		if($extension == "" or $extension == "."){
				$newFile = $codFile ."_". date('YmdHis');
			} else
			{
				$newFile = $codFile ."_". date('YmdHis'). "." .$extension;	
			}
		
		if (!is_dir("files/".$path)) {
			mkdir("files/".$path);
		}
		$dir = "files/".$path.$newFile;
		if (move_uploaded_file($file['tmp_name'],$dir)){
			
			return $newFile;
		}
		else{
			return 0;
		}
	}



	public function resizeImage($filename, $output)
	{
		$width = 800;
		list($width_orig, $height_orig) = getimagesize($filename);
		
		$ratio_orig = $width_orig/$height_orig;
		$height = $width/$ratio_orig;
		
		$image_p = imagecreatetruecolor($width, $height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		
		imagejpeg($image_p, $output);
	}

	public function curl_getResponseCode($url){ 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		//curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $dataSend);
		$result = curl_exec($ch);
		$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_error ($ch);
		curl_close($ch);

		return $returnCode;
	}

}



?>
