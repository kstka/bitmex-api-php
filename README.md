# bitmex-api-php
BitMex PHP REST API

Get API keys from https://www.bitmex.com/app/apiKeys

## Usage Example
    <?php
    require_once ("BitMex.php");
    
    $key = "xxxxxxxxxxxxxxxxxxxxxx";
    $secret = "yyyyyyyyyyyyyyyyyyyyyy";

    $bitmex = new BitMex($key,$secret);
    
    var_dump($bitmex->createOrder("Limit","Sell",50000,1000));
    ?>

## Donations
You BitCoin donations are highly appreciated [1N36HHos4qQ76PX1BrmeaJCzWDmggreuNU](https://blockchain.info/address/1N36HHos4qQ76PX1BrmeaJCzWDmggreuNU)
