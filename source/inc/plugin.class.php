<?php


require_once(dirname(__FILE__) . "/conn.php");

class Plugin{
	private $conn;

	public function __construct(){
		$this->conn = new Csdb();
	}
	public function flush_app_list(){
		$handle = opendir("/usr/local/lnmp/nginx/html/dev/cs_ll/app");
		if($handle){
			while (false !== ($file = readdir($handle))){
				if($file != '..' && $file != '.'){
					$xml = $this->parse_app("/usr/local/lnmp/nginx/html/dev/cs_ll/app/$file/config");
					if( $xml ){
						$status = $this->conn->query("SELECT * FROM cs_app where name='$file';");
						if($status->num_rows == 0){
							$attr = json_encode($xml, JSON_UNESCAPED_UNICODE);
							$this->conn->query("insert into cs_app values(NULL,'$file',1,'$attr');");
						}
					}
					else
						return false;
				}
			}
		}else
			return false;
		return true;
	}
	public function get_app_list(){
		//$this->flush_app_list();
		$result = $this->conn->query("select * from cs_app where status = 1;");
		$list = "";
		while( ($arr = $result->fetch_assoc()) )
			$list[] = $arr;
		if($list === "")
			return false;
		return $list;
        }
        public function get_all_app_list(){
                $result = $this->conn->query("select * from cs_app;");
                $list = "";
                while( ($arr = $result->fetch_assoc()) )
                        $list[] = $arr;
                if($list == "")
                        return false;
                return $list;
        }
	public function change_app($file,$status){
		if( is_file("../app/$file/config") ){	
			$query_str = "update `cs_app` set status=$status where name='$file';";
			$result = $this->conn->query($query_str);
			if($result)
				return true;
			return false;
		}
	}

	private function parse_app($path){
		$xml = simplexml_load_file($path);
		return $this->simplexml2array($xml);
	}
	private function simplexml2array($obj){    
	    if( count($obj) >= 1 ){
		$result = $keys = array();
		
		foreach( $obj as $key=>$value){   
		    isset($keys[$key]) ? ($keys[$key] += 1) : ($keys[$key] = 1);
		    
		    if( $keys[$key] == 1 )
			$result[$key] = $this->simplexml2array($value);
		    elseif( $keys[$key] == 2 )
			$result[$key] = array($result[$key], $this->simplexml2array($value));
		    else if( $keys[$key] > 2 )
			$result[$key][] = $this->simplexml2array($value);
		}
		return $result;
	    }
	    else if( count($obj) == 0 )
		return (string)$obj;
	}
}

?>
