<?php
namespace Tools;

use Logger;
use Model;
use Flight;
use Exception;

class ClsTestTool
{
	/**
	 * @param array $result 待解析的数据
	 * @param string $index result数组中代表实际数据的索引
	 * @param string $type 报错的类型
	 * @throws string 发生错误时报出的异常
	 * @return array $array 返回的数据
	 */
	private static function parseResult($result,$index,$type) {
		if (! isset($result) || ! isset($result['result']) || $result['result'] != "ok" || ! isset($result['inventory_transaction_list'])) {
			if (isset($result['error_info'])) {
				throw new Exception($result['error_info']);
			} else {
				throw new Exception($type.'内部服务器错误');
			}
		}
		return $result[$index];
	}
	public static function cbTest($param1, $param2) {
		$config = Flight::get('master_config');
		$url = $config['test_config']['test_url'];
		$data = array(
			"param1" => $param1,
			"param2" => $param2,
		);
		$result = ClsTestTool::post_json_data($url, json_encode($data));
		$result = json_decode($result, true);
		return ClsTestTool::parseResult($result,'test','test');
	}
	
	public static function post_data($url,$data){
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		$ret = curl_exec ( $ch );
		curl_close ( $ch );
		return $ret;
	}
	public static function post_json_data($url, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8',
			'Content-Length: ' . strlen($data))
		);
		ob_start();
		curl_exec($ch);
		$return_content = ob_get_contents();
		ob_end_clean();
		return $return_content;
	}

	public static function get_data($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	
}

?>
