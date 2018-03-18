# bitmex-api-php
BitMex PHP REST API with HTTP Keep-Alive support

Get API keys from https://www.bitmex.com/app/apiKeys

HTTP Keep-Alive: BitMex says that "When using HTTP Keep-Alive, request/response round-trip time will be identical to Websocket"

## Usage Example
    <?php
    require_once ("BitMex.php");
    
    $key = "xxxxxxxxxxxxxxxxxxxxxx";
    $secret = "yyyyyyyyyyyyyyyyyyyyyy";

    $bitmex = new BitMex($key,$secret);
    
    var_dump($bitmex->createOrder("Limit","Sell",50000,1000));
    ?>

## Donations
Your BitCoin donations are highly appreciated at [1N36HHos4qQ76PX1BrmeaJCzWDmggreuNU](https://blockchain.info/address/1N36HHos4qQ76PX1BrmeaJCzWDmggreuNU)
