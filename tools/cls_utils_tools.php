<?php
namespace Tools;

use Flight;

class ClsUtilsTools
{
	static public $error_array;
	static public $domain_array;
	static public $constant_array;
	static public $wechatMessage;

	static public function getRealIp() {
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}elseif(!empty($_SERVER['HTTP_CLIENT_IP'])){
			$IP = getenv('HTTP_CLIENT_IP');
		}elseif(!empty($_SERVER['REMOTE_ADDR'])){
			$IP = $_SERVER['REMOTE_ADDR'];
		}elseif($_SERVER['HTTP_VIA']){
			$IP = $_SERVER['HTTP_VIA'];
		}else{
			$IP = null;
		}

		return trim(substr($IP,strpos($IP," ")));
	}

	static public function createGuid () {
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = //chr(123)// "{"
                substr($charid, 0, 8)
                .substr($charid, 8, 4)
                .substr($charid,12, 4)
                .substr($charid,16, 4)
                .substr($charid,20,12);
                //.chr(125);// "}"
            return $uuid;
        }
    }

	static public function isValidGuid ($guid) {
		return !empty($guid) && preg_match('/^\{?[A-Z0-9]{32}\}?$/', $guid);
	}

	static public function array2string (&$data) {
		if(is_array($data)){
			foreach ($data as $key => &$value) {
				ClsUtilsTools::array2string($value);
			}
		}else{
			$data = $data===null ? "" : $data;
			$data = (string)$data;
		}
	}

	static public function generateSmsVertCode(){
        $c = "0123456789";
        $l = 4;
        $rand = "";
        srand((double)microtime()*1000000);

        for($i=0; $i<$l; $i++) {
            $rand.= $c[rand()%strlen($c)];
        }

        return $rand;
    }

	static public function isAllowCrossDomain($domain) {
		$temp = ClsUtilsTools::$domain_array['allow'];
		$domains = explode(",", $temp['domain_name']);
		return in_array($domain, $domains);
	}

	static public function getErrorInfo ($error_code, $arg1=null, $arg2=null, $arg3=null, $arg4=null, $arg5=null) {
		$language = 'cn';
		$errors = ClsUtilsTools::$error_array[$language];
		if (isset($errors[$error_code])) {
			$string = $errors[$error_code]; 
		} else {
			$string = $errors['50000'];
		}
		
		if (!is_null($arg1)) {
			// insert argument(s) into string
			$string = sprintf($string, $arg1, $arg2, $arg3, $arg4, $arg5);
		}

		return $string;
	}

	/**
     * 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     *
     * @access public
     * @param string $str
     *            待转换字串
     *
     * @return string $str 处理后字串
     */
    public static function makeSemiangle($str)
    {
        $arr = array(
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'Ａ' => 'A',
            'Ｂ' => 'B',
            'Ｃ' => 'C',
            'Ｄ' => 'D',
            'Ｅ' => 'E',
            'Ｆ' => 'F',
            'Ｇ' => 'G',
            'Ｈ' => 'H',
            'Ｉ' => 'I',
            'Ｊ' => 'J',
            'Ｋ' => 'K',
            'Ｌ' => 'L',
            'Ｍ' => 'M',
            'Ｎ' => 'N',
            'Ｏ' => 'O',
            'Ｐ' => 'P',
            'Ｑ' => 'Q',
            'Ｒ' => 'R',
            'Ｓ' => 'S',
            'Ｔ' => 'T',
            'Ｕ' => 'U',
            'Ｖ' => 'V',
            'Ｗ' => 'W',
            'Ｘ' => 'X',
            'Ｙ' => 'Y',
            'Ｚ' => 'Z',
            'ａ' => 'a',
            'ｂ' => 'b',
            'ｃ' => 'c',
            'ｄ' => 'd',
            'ｅ' => 'e',
            'ｆ' => 'f',
            'ｇ' => 'g',
            'ｈ' => 'h',
            'ｉ' => 'i',
            'ｊ' => 'j',
            'ｋ' => 'k',
            'ｌ' => 'l',
            'ｍ' => 'm',
            'ｎ' => 'n',
            'ｏ' => 'o',
            'ｐ' => 'p',
            'ｑ' => 'q',
            'ｒ' => 'r',
            'ｓ' => 's',
            'ｔ' => 't',
            'ｕ' => 'u',
            'ｖ' => 'v',
            'ｗ' => 'w',
            'ｘ' => 'x',
            'ｙ' => 'y',
            'ｚ' => 'z',
            '（' => '(',
            '）' => ')',
            '〔' => '[',
            '〕' => ']',
            '【' => '[',
            '】' => ']',
            '〖' => '[',
            '〗' => ']',
            '“' => '[',
            '”' => ']',
            '‘' => '[',
            '’' => ']',
            '｛' => '{',
            '｝' => '}',
            '《' => '<',
            '》' => '>',
            '％' => '%',
            '＋' => '+',
            '—' => '-',
            '－' => '-',
            '～' => '-',
            '：' => ':',
            '。' => '.',
            '、' => ',',
            '，' => '.',
            '、' => '.',
            '；' => ',',
            '？' => '?',
            '！' => '!',
            '…' => '-',
            '‖' => '|',
            '”' => '"',
            '’' => '`',
            '‘' => '`',
            '｜' => '|',
            '〃' => '"',
            '　' => ' '
        );

        return strtr($str, $arr);
    }

	public static function C($part, $keyname){
		
// 		var_dump($part);
// 		var_dump($keyname);
		//die('ssss');
        return ClsUtilsTools::$constant_array[$part][$keyname];
    }

    static public function checkStringMatchRegex($string, $regex,$errorWhenNotMatch=40009){
        //int preg_match ( string $pattern , string $subject [, array &$matches [, int $flags = 0 [, int $offset = 0 ]]] )
        $temp =preg_match($regex, $string);
        if(empty($temp)){
            if($errorWhenNotMatch != false){
                Flight::sendRouteResult(array('error_code'=>$errorWhenNotMatch));
            }else{
                return false;
            }
        }else{
            return true;
        }
    }
    
    static public function checkStringNotNull($string,$errorWhenNotMatch=40009){
    	if(!isset($string)){
    		if($errorWhenNotMatch != false){
    			Flight::sendRouteResult(array('error_code'=>$errorWhenNotMatch));
    		}else{
    			return false;
    		}
    	}else{
    		return true;
    	}
    }
    
    /**
     * 生成条码串
     * @param unknown $seed
     * @return string
     */
    public static function generateBarCode($seed){
    	$tmp = substr(hash("md5",$seed), 9, 9);
    	mt_srand((double) microtime() * 1000000);
    	return substr(hexdec($tmp), -6, 6).str_pad((mt_rand(1, 999999)), 6, '0', STR_PAD_LEFT);
    }

    static public function arrayToObject (&$array)
    {
        ArrayConvert::main($array);
    }

}

class ArrayConvert
{
    private static function conversion (&$array)
    {
        $array = (object)$array;
    }
 
    private static function loop (&$array)
    {
        while (list($key, $value) = each($array)) {
            if (is_array($value)) {
                ArrayConvert::loop($array[$key]);
                ArrayConvert::conversion($array[$key]);
            }
        }
        ArrayConvert::conversion($array);
    }
 
    public static function main (&$array)
    {
        if(empty($array)){
            $kkk=array();
            $array = (object)$kkk;
        }else{
            ArrayConvert::loop($array);
        }
    }
}

ClsUtilsTools::$error_array=parse_ini_file("config/errorcode.ini", true);
//ClsUtilsTools::$domain_array=parse_ini_file("config/domain.ini", true);
ClsUtilsTools::$constant_array=parse_ini_file("config/constants.ini", true);
