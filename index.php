<?php
 
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/issues/:pubid', function ($pubid) {
    $sql = "SELECT * FROM ISSUES WHERE PUBLICATION_UUID = " . "'" . $pubid . "'";
    try {
        $conn = getConnection();
        
        $IssuesArray = array();
        $i = 0;
        
    	  foreach ($conn->query($sql) as $row) {
        		$IssuesArray[$i]['name'] = $row['NAME'];
				$IssuesArray[$i]['title'] = $row['TITLE'];
				$IssuesArray[$i]['info'] = $row['INFO'];
				$IssuesArray[$i]['date'] = $row['DATE'];	
				$IssuesArray[$i]['cover'] = $row['COVER'];
				$IssuesArray[$i]['url'] = $row['URL'];
        		$i++;
    	 }
    echo json_encode($IssuesArray);
    } catch(PDOException $e) {
        echo '{"error":{"text":"'. $e->getMessage() .'"}}';
    }
});

$app->get('/itunes/:pubid', function ($pubid) {
    $sql = "SELECT * FROM ISSUES WHERE PUBLICATION_UUID = " . "'" . $pubid . "'";
    try {
        $conn = getConnection();

        $IssuesArray = array();
        $i = 0;
        
        $iTunesUpdateDate = "2011-08-01T00:00:00-07:00";
        
        $AtomXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"" . "?>";
        $AtomXML .= "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:news=\"http://itunes.apple.com/2011/Newsstand\">";
		  $AtomXML .= "<updated>" . $iTunesUpdateDate . "</updated>";        
        
    	  foreach ($conn->query($sql) as $row) {
    	  		$AtomXML .= "<entry>";
        	   $AtomXML .= "<id>" . $row['NAME'] . "</id>";
        	   $AtomXML .= "<updated>" . $row['ITUNES_UPDATED'] . "</updated>";
        	   $AtomXML .= "<published>" . $row['ITUNES_PUBLISHED'] . "</published>";
        	   $AtomXML .= "<summary>" . $row['ITUNES_SUMMARY'] . "</summary>";
        	   $AtomXML .= "<news:cover_art_icons>";
        	   $AtomXML .= "<news:cover_art_icon size=\"SOURCE\" src=\"" . $row['ITUNES_COVERART_URL'] . "\"/>";
        	   $AtomXML .= "</news:cover_art_icons>";
	  		   $AtomXML .= "</entry>";	
        		$i++;
    	  }

    	  $AtomXML .= "</feed>";
    	  
		  echo utf8_encode($AtomXML);

    } catch(PDOException $e) {
        echo '{"error":{"text":"'. $e->getMessage() .'"}}';
    }
}); 
 
$app->post('/subscription/:pubid', function ($pubid) use ($app) {

    $request = $app->request();
    $body = $request->getBody();
	$jsondata = json_decode($body,true);
	 
	$receiptdata = $jsondata['receipt-data'];

	//echo print_r($jsondata);	
	//Sample iTunes Connect Data Receipt
	//Array
    //(
    //[quantity] => 1
    //[product_id] => com.nin9creative.magrocket.sub.free
    //[transaction_id] => 1000000059938124
    //[purchase_date] => 2012-12-06 06:24:46 Etc/GMT
    //[app_item_id] => 
    //[bid] => com.nin9creative.magrocket
    //[bvrs] => Baker-40
	 //}
	 
	 try {
	 		$iTunesReceiptInfo = verifyReceipt($receiptdata);
	 		
	 		    $sql = "INSERT INTO SUBSCRIPTIONS (PUBLICATION_UUID, QUANTITY, PRODUCT_ID, TRANSACTION_ID, PURCHASE_DATE, 
	 		    			ORIGINAL_TRANSACTION_ID, ORIGINAL_PURCHASE_DATE, APP_ITEM_ID, VERSION_EXTERNAL_IDENTIFIER, BID, BVRS) 
	 		    			VALUES (:publication_uuid, :quantity, :product_id, :transaction_id, :purchase_date, :original_transaction_id,
	 		    					  :original_purchase_date, :app_item_id, :version_external_identifier, :bid, :bvrs)";
			    try {
			        $db = getConnection();
			        $stmt = $db->prepare($sql);
			        $stmt->bindParam("publication_uuid", $pubid);
			        $stmt->bindParam("quantity", $iTunesReceiptInfo['quantity']);
			        $stmt->bindParam("product_id", $iTunesReceiptInfo['product_id']);
			        $stmt->bindParam("transaction_id", $iTunesReceiptInfo['transaction_id']);
			        $stmt->bindParam("purchase_date", $iTunesReceiptInfo['purchase_date']);
			        $stmt->bindParam("original_transaction_id", $iTunesReceiptInfo['original_transaction_id']);
			        $stmt->bindParam("original_purchase_date", $iTunesReceiptInfo['original_purchase_date']);
			        $stmt->bindParam("app_item_id", $iTunesReceiptInfo['app_item_id']);
			        $stmt->bindParam("version_external_identifier", $iTunesReceiptInfo['version_external_identifier']);
			        $stmt->bindParam("bid", $iTunesReceiptInfo['bid']);
			        $stmt->bindParam("bvrs", $iTunesReceiptInfo['bvrs']);			        			        			        			        
			        $stmt->execute();
			        
			        $lastInsertID = $db->lastInsertId();
			        $db = null;
			        if($lastInsertID > 0)
			        {
			        	echo '{"success":{"message":"'. $lastInsertID .'"}}';	
			        }
			    } catch(PDOException $e) {
			        echo '{"error":{"text":"'. $e->getMessage() .'"}}';
			    }
	 		
	 } catch (Exception $e) {
    		echo '{"error":{"text":"'. $e->getMessage() .'"}}';
	 }
});
 
/*
function getIssues($pubid) {
    $sql = "SELECT * FROM ISSUES WHERE PUBLICATION_UUID = :pubid";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $issues = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '[' . json_encode($issues) . ']';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}
*/

 // Validate InApp Purchase Receipt
 function verifyReceipt($receipt, $isSandbox = true)
 {
     	if ($isSandbox) {
      	   $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
     	}
     	else {
      	   $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
     	}
  
     	$postData = json_encode(
      	   array('receipt-data' => $receipt)
     	);

		$ch = curl_init($endpoint);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
     	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     	curl_setopt($ch, CURLOPT_POST, true);
     	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
 
     	$response = curl_exec($ch);
     	$errno = curl_errno($ch);
     	$errmsg = curl_error($ch);
     	curl_close($ch);

		if ($errno != 0) {
      	   throw new Exception($errmsg, $errno);
     	}

     	$data = json_decode($response);

     	if (!is_object($data)) {
      	   throw new Exception('Invalid Response Data');
     	}
 
     	if (!isset($data->status)|| $data->status != 0)
		{
         throw new Exception('Invalid Receipt');
     	}

		return array(
         'quantity' => $data->receipt->quantity,
         'product_id' => $data->receipt->product_id,
         'transaction_id' => $data->receipt->transaction_id,
         'purchase_date' => $data->receipt->purchase_date,
         'app_item_id' => $data->receipt->app_item_id,
         'bid' => $data->receipt->bid,
         'bvrs' => $data->receipt->bvrs
     	);
  }
     
function getConnection() {
    $dbhost="localhost";
    $dbuser="DBUSERNAME";
    $dbpass="DBPASSWORD";
    $dbname="DBNAME";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();

 
?>