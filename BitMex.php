<?php

class BitMex {

  //const API_URL = 'https://testnet.bitmex.com';
  const API_URL = 'https://www.bitmex.com';
  const API_PATH = '/api/v1/';
  const SYMBOL = 'XBTUSD';

  private $apiKey = '';
  private $apiSecret = '';

  public function __construct($apiKey,$apiSecret) {
    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
  }

  /*
   * Public
   *
   */

  public function getTicker() {

    $symbol = self::SYMBOL;
    $data['function'] = "instrument";
    $data['params'] = array(
      "symbol" => $symbol
    );

    $return = $this->publicQuery($data);

    if(!$return || count($return) != 1|| !isset($return[0]['symbol'])) return false;

    $return = array(
      "symbol" => $return[0]['symbol'],
      "last" => $return[0]['lastPrice'],
      "bid" => $return[0]['bidPrice'],
      "ask" => $return[0]['askPrice'],
      "high" => $return[0]['highPrice'],
      "low" => $return[0]['lowPrice']
    );

    return $return;

  }

  public function getCandles($timeFrame,$count) {

    $symbol = self::SYMBOL;
    $data['function'] = "trade/bucketed";
    $data['params'] = array(
      "symbol" => $symbol,
      "count" => $count,
      "binSize" => $timeFrame,
      "partial" => "false",
      "reverse" => "true"
    );

    $return = $this->publicQuery($data);

    $candles = array();

    // Converting
    foreach($return as $item) {

      $time = strtotime($item['timestamp']); // Unix time stamp

      $candles[$time] = array(
        'timestamp' => date('Y-m-d H:i:s',$time), // Local time human-readable time stamp
        'time' => $time,
        'open' => $item['open'],
        'high' => $item['high'],
        'close' => $item['close'],
        'low' => $item['low']
      );

    }

    // Sorting candles from the past to the present
    ksort($candles);

    return $candles;

  }

  public function getOrders() {

    $symbol = self::SYMBOL;
    $data['method'] = "GET";
    $data['function'] = "order";
    $data['params'] = array(
      "symbol" => $symbol,
      "reverse" => "false"
    );

    return $this->authQuery($data);
  }

  public function getOpenOrders() {

    $symbol = self::SYMBOL;
    $data['method'] = "GET";
    $data['function'] = "order";
    $data['params'] = array(
      "symbol" => $symbol,
      "reverse" => "false"
    );

    $orders = $this->authQuery($data);

    $openOrders = array();
    foreach($orders as $order) {
      if($order['ordStatus'] == 'New' || $order['ordStatus'] == 'PartiallyFilled') $openOrders[] = $order;
    }

    return $openOrders;

  }

  public function getOpenPositions() {

    $symbol = self::SYMBOL;
    $data['method'] = "GET";
    $data['function'] = "position";
    $data['params'] = array(
      "symbol" => $symbol
    );

    return $this->authQuery($data);
  }

  public function editOrderPrice($orderID,$price) {

    $data['method'] = "PUT";
    $data['function'] = "order";
    $data['params'] = array(
      "orderID" => $orderID,
      "price" => $price
    );

    return $this->authQuery($data);
  }

  public function createOrder($type,$side,$price,$quantity) {

    $symbol = self::SYMBOL;
    $data['method'] = "POST";
    $data['function'] = "order";
    $data['params'] = array(
      "symbol" => $symbol,
      "side" => $side,
      "price" => $price,
      "orderQty" => $quantity,
      "ordType" => $type
    );

    return $this->authQuery($data);
  }

  public function cancelAllOpenOrders($text = "") {

    $symbol = self::SYMBOL;
    $data['method'] = "DELETE";
    $data['function'] = "order/all";
    $data['params'] = array(
      "symbol" => $symbol,
      "text" => $text
    );

    return $this->authQuery($data);
  }

  public function cancelOrder($orderID,$text = "") {

    $data['method'] = "DELETE";
    $data['function'] = "order";
    $data['params'] = array(
      "orderID" => $orderID,
      "text" => $text
    );

    return $this->authQuery($data);
  }

  /*
   * Private
   *
   */

  private function authQuery($data) {

    $method = $data['method'];
    $function = $data['function'];
    if($method == "GET" || $method == "POST" || $method == "PUT") {
      $params = http_build_query($data['params']);
    }
    elseif($method == "DELETE") {
      $params = json_encode($data['params']);
    }
    $path = self::API_PATH . $function;
    $url = self::API_URL . self::API_PATH . $function;
    if($method == "GET" && count($data['params']) >= 1) {
      $url .= "?" . $params;
      $path .= "?" . $params;
    }
    $nonce = $this->generateNonce();
    if($method == "GET") {
      $post = "";
    }
    else {
      $post = $params;
    }
    $sign = hash_hmac('sha256', $method.$path.$nonce.$post, $this->apiSecret);

    $headers = array(
      'api-signature: '.$sign,
      'api-key: '.$this->apiKey,
      'api-nonce: '.$nonce
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if($data['method'] == "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if($data['method'] == "DELETE") {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      $headers[] = 'X-HTTP-Method-Override: DELETE';
    }
    if($data['method'] == "PUT") {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      //curl_setopt($ch, CURLOPT_PUT, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      $headers[] = 'X-HTTP-Method-Override: PUT';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $return = curl_exec($ch);

    if(!$return) {
      return $this->curlError($ch);
    }

    $return = json_decode($return,true);

    if(isset($return['error'])) {
      echo "BitMex error: ".$return['error']['name']." : ".$return['error']['message']."\n";
      return false;
    }
    else {
      return $return;
    }

  }

  private function publicQuery($data) {

    $function = $data['function'];
    $params = http_build_query($data['params']);
    $url = self::API_URL . self::API_PATH . $function . "?" . $params;;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $return = curl_exec($ch);

    if(!$return) {
      return $this->curlError($ch);
    }

    $return = json_decode($return,true);

    if(isset($return['error'])) {
      echo "BitMex error: ".$return['error']['name']." : ".$return['error']['message']."\n";
      return false;
    }
    else {
      return $return;
    }

  }

  private function generateNonce() {
    $nonce = (string) number_format(round(microtime(true) * 100000), 0, '.', '');
    return $nonce;
  }

  private function curlError($ch) {

    if ($errno = curl_errno($ch)) {
      $errorMessage = curl_strerror($errno);
      echo "cURL error ({$errno}): {$errorMessage}\n";
      return false;
    }

    return true;
  }

}