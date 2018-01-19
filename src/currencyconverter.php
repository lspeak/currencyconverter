<?php 

namespace lspeak\currencyconverter;
use Exception;
/**
*  @author TOLGA KARABULUT
*/

class currency
{
	protected $url;
	protected $curr;
	protected $currency;
	protected $list;
	protected $response = [];
	protected $index;
	protected $name;
	function __construct()
	{
		$this->url 		= "http://www.tcmb.gov.tr/kurlar/today.xml";
		$this->curr 	= (array) simplexml_load_file($this->url);
		$this->currency = $this->curr['Currency'];
		$this->list 	= ["USD","AUD","DKK","EUR","GBP","CHF","SEK","CAD","KWD","NOK","SAR","JPY","BGN","RON","RUB","IRR","CNY","PKR","ALL"];
	}
	public function setCurrencyName( $name = "ALL" ){
		$listIndex 	 = array_search( strtoupper($name) , $this->list);
		$this->index = $listIndex;
		$this->name  = $name;
		return $this;
	}
	protected function getAttr(){
		$attr = $this->curr["@attributes"];
		$detail = [
			"Date" => $attr['Date'],
			"newsletterNo" => $attr['Bulten_No']
		];
		return $detail;
	}
	public function getDetail(){
		$this->response['success'] = true;
		$this->response['result']  = ( $this->index == 18 )  ? $this->currency : $this->currency[ $this->index ];
	}
	public function toTL($price , $type = "banknote" , $detail = false , $profitRate = 1){
		try {
			$response = [];
			if( !isset($this->index) ) 		throw new Exception("Currency name is not defined", 1);
			if( empty($this->name)  ) 		throw new Exception("Currency name is not defined", 1);
			if( empty($price) ) 			throw new Exception( $this->name . " is null " , 1);
			if( !is_numeric($price) ) 		throw new Exception( $this->name . " is not numeric " , 1);
			if( !is_bool($detail) ) 		throw new Exception("Detail is not boolean (detail = true / false )", 1);
			if( !is_numeric($profitRate) )  throw new Exception("Profit rate is not numeric", 1);
			if( $type != "banknote" && $type != "forex" ) throw new Exception("Change type is undefined (type = banknote / forex ) ",1);
			
			$changeType = ( $type == "banknote") ? "BanknoteSelling" : "ForexSelling";
				
				if( $this->name != "ALL"){
					$to = (array) $this->currency[$this->index];
					if( $detail ){ $response['detail'] = self::detail($to); }
					$response["toTL"] = self::exchange($to , $changeType , $price , $profitRate);
					$this->response = ['success'=> true , 'result' => $response];
				}
				else{
					$length = count($this->list) - 1;
					$allResponse =[];
					for ($i=0; $i < $length ; $i++) { 
						$to = (array) $this->currency[$i];
						if( $detail ){ $response['detail'] = self::detail($to); }
						$response['currencyName'] = $to['@attributes']['CurrencyCode'];
						$response["toTL"] = self::exchange($to , $changeType , $price , $profitRate);
						$allResponse[] = $response;
					};
					$this->response = ['success'=> true , 'result' => $allResponse];
				}

		} catch (Exception $e) {
			$this->response['success'] = false;
			$this->response['message'] = $e->getMessage();
		}
	}
	protected function exchange ($to ,$type , $price , $profitRate) {
		return number_format( $to[ $type ] * $price * $profitRate , 5);
		
	}
	protected function detail($to){
		$toDetail = [];
		$toDetail['name'] 		  = $to['Isim'];
		$toDetail['code'] 		  = $to['@attributes']['Kod'];
		$toDetail['currencyName'] = $to['CurrencyName'];
		$toDetail['currencyCode'] = $to['@attributes']['CurrencyCode'];
		$toDetail['attributes']   = self::getAttr();
		return $toDetail;
	}
	function __destruct(){
		print_r( json_encode($this->response ,JSON_UNESCAPED_UNICODE) );
	}

}