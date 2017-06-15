<?php
/**
 * 银兴乐天(指点无限)订票选座
 * @Author esyy   
 * @version 1.8
 * @var data 2016/2/10
 */
class YxltInterface
{
    /*************测试商户和密匙***********/
	private $data_url = "http://####################erver";//API请求地址
	private $order_url = "http://#################service";//API请求地址
	private $uid = '#############';//授权码
	private $ukey = '###############';
    /****正式商户和密匙*****/
	//private $key = '################';//授权码
	//private $pwd = '#######################';
	//private $api_url = "http://#######################imovie/";
	
	/**
	 * ------------------------------------------------------
	 * 获取城市列表
	 * @param array $params 城市接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function cityList(){
		$method = 'getCitys';
		$params = $this->create_params($method);
		$res = $this->request_api($method,$params);
		
		return $res;
		
	}
	
	/**
	 * ------------------------------------------------------
	 * 获取影院列表
	 * @param array $params 影院接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function cinemaList($params){
		$method = 'getCinemas';
		$params = $this->create_params($method);
		$res = $this->request_api($method,$params);
		
		return $res;
		
	}
	
	/**
	 * ------------------------------------------------------
	 * 获取影院影厅列表
	 * @param array $params 影院影厅接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function hallList($params){
		$method = 'getHallsByCinemaId';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params);
		
		return $res;
		
	}
	
	/**
	 * ------------------------------------------------------
	 * 获取影院影厅座位列表
	 * @param array $params 影院影厅座位接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function seatList($params){
		$method = 'getSeatsByCinemaAndHall';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params);
		file_put_contents('yxlt_seat.txt',var_export($params,true).var_export($res,true));
		return $res;
		
	}
	
	/**
	 * ------------------------------------------------------
	 * 获取影院放映排期列表
	 * @param array $params 影院放映排期接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function planList($params){
		$method = 'getPlans';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params);
		//echo '<pre>';
		//print_r($res);
		
		return $res;
		
	}
	
	/**
	 * ------------------------------------------------------
	 * 获取影院放映排期列表
	 * @param array $params 影院放映排期接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function soldSeat($params){
		$method = 'getSoldSeats';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params);
		//print_r($res);
		file_put_contents('yxlt_soldseat.txt',var_export($params,true).var_export($res,true));
		return $res;
		
	}

	/**
	 * ------------------------------------------------------
	 * 合并座位及不可选座位数据，构造前台显示所需的数据
	 * @param array $param1 影院影厅座位接口所需参数数组
	 * @param array $param2 影院影厅已售座位接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function seatStatus($param1,$param2){
		$seat = $this->seatList($param1);
		$notseat = $this->soldSeat($param2);
		if($seat['errCode'] == 0 && $notseat['errCode'] == 0){
			$max_col = 0;
			$max_row = 0;
			foreach($notseat['data'] as $k=>$v){
				$r = $v['seatRow'];
				$c = $v['seatCol'];
				$not[$r][$c] = 1;
			}
			foreach($seat['data'] as $key => $val){
				if($val['graphCol'] > $max_col){$max_col = $val['graphCol'];}
				if($val['graphRow'] > $max_row){$max_row = $val['graphRow'];}
				$row = $val['graphRow'];
				$col = $val['graphCol']; 
				$seat_info['seat'][$row][$col]['row'] = $val['seatRow']; 
				$seat_info['seat'][$row][$col]['col'] = $val['seatCol'];
				$seat_info['seat'][$row][$col]['id'] = $val['seatNo'];
				$seat_info['rowId'][$row] = $val['seatRow'];
				//$seat_info['colId'][$row][$col] = $val['seatCol'];
				switch($val['seatType']){
					case 0:
						$seat_info['seat'][$row][$col]['type'] = '1';//普通座
					break;
					case 1:
					case 2:
						$seat_info['seat'][$row][$col]['type'] = '2';//情侣座
					break;
					default:
						$seat_info['seat'][$row][$col]['type'] = '3';//特殊座位
				}
				
				$seat_info['seat'][$row][$col]['status'] = $val['seatFlag'] == 0 && !$not[$val['seatRow']][$val['seatCol']]? 1 : 0;
			}
			$col_arr = array_keys($seat_info['seat'][1]);
			
			//补空位
			for($i=1;$i<=$max_row;$i++){
				for($j=1;$j<=$max_col;$j++){
					if(!isset($seat_info['seat'][$i][$j]) || !$seat_info['seat'][$i][$j]){
						$seat_info['seat'][$i][$j] = '';
					}
				}
				
				if($col_arr[0] < $col_arr[1]){
					ksort($seat_info['seat'][$i]);
				}else{
					krsort($seat_info['seat'][$i]);
				}
				
			}
			ksort($seat_info['seat']);
			return $seat_info;
		}else {
			if($seat['errCode'] !=0 ){
				return $seat;
			}
			if($notseat['errCode'] != 0 ){
				return $notseat;
			}
		}
	}
	
	/**
	 * ------------------------------------------------------
	 * 锁座并下单
	 * @param array $params 锁座并下单接口所需参数数组
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function addOrder($params){
		$method = 'order_add';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params,2);
		
		return $res;
		
	}
	
	
	/**
	 * ------------------------------------------------------
	 * 确认订单出票
	 * @param array $params 确认订单出票接口所需参数数组,示例:array('order_no'=>'1234');
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function confirmOrder($params){
		$method = 'order_confirm';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params,2);
		
		return $res;
		
	}
	
	
	/**
	 * ------------------------------------------------------
	 * 取消订单并解锁座位
	 * @param array $params 取消订单并解锁座位接口所需参数数组,示例:array('order_no'=>'1234');
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function cancelOrder($params){
		$method = 'order_cancel';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params,2);
		
		return $res;
		
	}
	
	
	
	/**
	 * ------------------------------------------------------
	 * 查询订单
	 * @param array $params 查询订单接口所需参数数组,示例:array('order_no'=>'1234');
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function queryOrder($params){
		$method = 'order_query';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params,2);
		
		return $res;
		
	}
	
	
	/**
	 * ------------------------------------------------------
	 * 批量核对订单
	 * @param array $params 批量核对订单接口所需参数数组,示例:array('order_status'=>'1','begin_time'=>'2015-04-07 00:00:00','end_time'=>'2015-04-08 00:00:00');
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function checkOrder($params){
		$method = 'order_check';
		$params = $this->create_params($method,$params);
		$res = $this->request_api($method,$params,2);
		
		return $res;
		
	}
	
	/*
	 * ------------------------------------------------------
	 * 请求API并且换返回数据
	 * @param array or string $condition
	 * @return json array
	 * ------------------------------------------------------
	 */
	 
	private function request_api($method,$params = FALSE,$url_mode = 1)
	{
		if($url_mode == 1){
			$url = $this->data_url;
		}else{
			$url = $this->order_url;
		}
		//post方式
		//$rs_json = $this->curl_post($url,$params);
		
		//$rs_arr = json_decode($this->gzdecode($rs_json), true);
	
		//get方式
		//生成请求参数 cid=1&format=xml&pid=10000
		$query = urldecode(http_build_query($params));
		$api_url = $url."?{$query}";
		$rs_arr =json_decode(file_get_contents($api_url),true);
		//echo $api_url.'<br />';
		//print_r($rs_arr);
		//file_put_contents('spider_url.txt',$this->api_url.$method.var_export($params,true).var_export($rs_arr,true),FILE_APPEND);
		return $rs_arr;
	}
	
	
	/*
	 * ------------------------------------------------------
	 * curl post方式获取 
	 * @param array or string $condition
	 * @return json array
	 * ------------------------------------------------------
	 */
	public function curl_post($url,$params)
	{
		$header = array();
		$curlPost = $params;
		$ch = curl_init();
		//echo $url.'<br />';
		//echo '<pre>';
		//print_r($params);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($curlPost));
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			print curl_error($ch);
		}
		curl_close($ch);
		return $response;
	}

	function gzdecode ($data) {
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;
		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}
		if ($flags & 8) // Filename
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 16) // Comment
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 2) // CRC at end of file
			$headerlen += 2;
		$unpacked = @gzinflate(substr($data, $headerlen));
		if ($unpacked === FALSE)
			$unpacked = $data;
		return $unpacked;
	}
	
	private function create_params($method,$param=array()){
		$param['method'] = $method;
		$param['uid'] = $this->uid;
		$micr = microtime();
		$arr = explode(' ',$micr);
		$time1 = $arr[1]*1000;
		$time2 = substr($arr[0],2,3);
		$time = $time1+$time2;
		$param['time_stamp'] = $time;
		
		ksort($param);
		$str = '';
		foreach($param as $val){
			$str .= $val;
		}
		$str = $str.$this->ukey;
		$sign = md5($str);
		
		$param['enc'] = strtolower($sign);
		return $param;
	}
	
}
