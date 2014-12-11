<?php
namespace litle\sdk;
require_once realpath(__DIR__). '/../../vendor/autoload.php';

define('NUM_TRANSACTIONS', 5);

#Sale
$sale_info = array(
        	      'orderId' => '1',
                      'id'=> '456',
		      'amount' => '10010',
		      'orderSource'=>'ecommerce',
		      'billToAddress'=>array(
		      'name' => 'John Smith',
		      'addressLine1' => '1 Main St.',
		      'city' => 'Burlington',
		      'state' => 'MA',
		      'zip' => '01803-3747',
		      'country' => 'US'),
		      'card'=>array(
		      'number' =>'5112010000000003',
		      'expDate' => '0112',
		      'cardValidationNum' => '349',
		      'type' => 'MC')
			);

// Parallel version
$t1 = microtime(true);
$initilaize = new LitleMultiOnlineRequest();
$saleResponseObjs = array();
for ($i = 0; $i < NUM_TRANSACTIONS; $i++)
	$saleResponseObjs[$i] = $initilaize->saleRequest($sale_info);
$initilaize->processRequests();
$t2 = microtime(true);

for ($i = 0; $i < NUM_TRANSACTIONS; $i++)
{
	$saleResponse = $saleResponseObjs[$i]->response;
	# Display results
	echo ("Response: " . (XmlParser::getNode($saleResponse,'response')) . "\n");
	echo ("Message: " . XmlParser::getNode($saleResponse,'message') . "\n");
	echo ("Litle Transaction ID: " . XmlParser::getNode($saleResponse,'litleTxnId') . "\n");
	
	if(XmlParser::getNode($saleResponse,'message')!='Approved')
	 throw new \Exception('LitleSaleTransaction does not have the right response');
}


// Serial version
$t3 = microtime(true);
$initilaize = new LitleOnlineRequest(); 
$saleResponses = array();
for ($i = 0; $i < NUM_TRANSACTIONS; $i++)
	$saleResponses[$i] = $initilaize->saleRequest($sale_info);
$t4 = microtime(true);
 
for ($i = 0; $i < NUM_TRANSACTIONS; $i++)
{
	$saleResponse = $saleResponses[$i];
	# Display results
	echo ("Response: " . (XmlParser::getNode($saleResponse,'response')) . "\n");
	echo ("Message: " . XmlParser::getNode($saleResponse,'message') . "\n");
	echo ("Litle Transaction ID: " . XmlParser::getNode($saleResponse,'litleTxnId') . "\n");
	
	if(XmlParser::getNode($saleResponse,'message')!='Approved')
	 throw new \Exception('LitleSaleTransaction does not have the right response');
}

echo "\n\n";
printf("Parallel: %0.03f\n", $t2-$t1);
printf("Serial: %0.03f\n", $t4-$t3);