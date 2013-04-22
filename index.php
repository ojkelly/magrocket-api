<?php
require 'Slim/Slim.php';

// Pimple Dependency Injection Container
require_once 'Pimple.php';

\Slim\Slim::registerAutoloader();

$app = new Slim\Slim(array(
	'mode' => 'production',
	'debug' => false
));

// Set Default Timezone to America/Los_Angeles as this is what all App Store dates are in.
date_default_timezone_set('America/Los_Angeles');

// Create Pimple Dependency Injection Container for DB Connection.
$dbContainer = new Pimple();

// DB Setup for Pimple Container
// MagRocket API SETUP CONFIGURATION SETTING
// ************************************************************
$dbContainer['db.options'] = array(
	'host' => 'localhost',											// CONFIGURE TO YOUR DB HOSTNAME					
	'username' => 'mag1_install',									// CONFIGURE TO YOUR DB USERNAME		
	'password' => 'magrocket',										// CONFIGURE TO YOUR DB USERNAME'S PASSWORD
	'dbname' => 'mag1_magrocketinstall'							// CONFIGURE TO YOUR DB INSTANCE NAME
);
//*************************************************************

// Using "share" method makes sure that the function is only called when 'db' is retrieved the first time.
$dbContainer['db'] = $dbContainer->share(function () use($dbContainer)
{
	return new PDO('mysql:host=' . $dbContainer['db.options']['host'] . ';dbname=' . $dbContainer['db.options']['dbname'], $dbContainer['db.options']['username'], $dbContainer['db.options']['password']);
});

// ************************************************
// SLIM PHP Methods for handling REST API Methods
// ************************************************

// GET route
$app->get('/', function () {
    $template = "
			<!DOCTYPE html>
			<html>
			<head>
			<meta charset='utf-8'/>
			<title>MagRocket REST API</title>
			<style>
			html,body,div,span,object,iframe,
			h1,h2,h3,h4,h5,h6,p,blockquote,pre,
			abbr,address,cite,code,
			del,dfn,em,img,ins,kbd,q,samp,
			small,strong,sub,sup,var,
			b,i,
			dl,dt,dd,ol,ul,li,
			fieldset,form,label,legend,
			table,caption,tbody,tfoot,thead,tr,th,td,
			article,aside,canvas,details,figcaption,figure,
			footer,header,hgroup,menu,nav,section,summary,
			time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent;}
			body{line-height:1;}
			article,aside,details,figcaption,figure,
			footer,header,hgroup,menu,nav,section{display:block;}
			nav ul{list-style:none;}
			blockquote,q{quotes:none;}
			blockquote:before,blockquote:after,
			q:before,q:after{content:'';content:none;}
			a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent;}
			ins{background-color:#ff9;color:#000;text-decoration:none;}
			mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold;}
			del{text-decoration:line-through;}
			abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help;}
			table{border-collapse:collapse;border-spacing:0;}
			hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0;}
			input,select{vertical-align:middle;}
			html{ background: #EDEDED; height: 100%; }
			body{background:#FFF;margin:0 auto;min-height:100%;padding:0 30px;width:440px;color:#666;font:14px/23px Arial,Verdana,sans-serif;}
			h1,h2,h3,p,ul,ol,form,section{margin:0 0 20px 0;}
			h1{color:#333;font-size:20px;}
			h2,h3{color:#333;font-size:14px;}
			h3{margin:0;font-size:12px;font-weight:bold;}
			ul,ol{list-style-position:inside;color:#999;}
			ul{list-style-type:square;}
			code,kbd{background:#EEE;border:1px solid #DDD;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:0 4px;color:#666;font-size:12px;}
			pre{background:#EEE;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:5px 10px;color:#666;font-size:12px;}
			pre code{background:transparent;border:none;padding:0;}
			a{color:#0068ae;}
			header{padding: 30px 0;text-align:center;}
			</style>
			</head>
			<body>
			<header>
			<a href='http://www.magrocket.com'><img src='data:image/png;base64,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' alt='MagRocket'/></a>
			</header>
			<h1>Welcome to MagRocket!</h1>
			<p>
			Congratulations! Your MagRocket API is up and running.  However, you should test the Database connectivity below.  If you get a success message, then you should be good to go!
			</p>
			<section>
			<h2>Get Started</h2>
			<ol>
			<li><a href='/checkinstall' target='_blank'>Check DB Connectivity</a></li>
			<li>Read the <a href='http://www.magrocket.com/knowledgebase/' target='_blank'>online documentation</a></li>
			<li>Follow <a href='http://www.twitter.com/magrocket' target='_blank'>@magrocket</a> on Twitter</li>
			</ol>
			</section>
			<section>
			<h2>MagRocket Community</h2>
			
			<h3>Support Forum and Knowledge Base</h3>
			<p>
			Visit the <a href='http://www.magrocket.com/knowledgebase/' target='_blank'>MagRocket Support Forum and Knowledge Base</a>
			to read announcements, chat with fellow MagRocket users, ask questions, help others, or show off your cool
			Baker Newsstand apps.  You might also check out the <a href='http://github.com/nin9creative/magrocket-backend' target='_blank'>Github Project</a> as there will probably be lots of discussion happening
			over there.
			</p>
			
			<h3>Twitter</h3>
			<p>
			Follow <a href='http://www.twitter.com/magrocket' target='_blank'>@magrocket</a> on Twitter to receive the very latest news
			and updates about the framework.
			</p>
			</section>
			</body>
			</html>";
    echo $template;
});

// Check DB Connectivity
// *Makes connection to MagRocket DB and tries to make a select from the PUBLICATION table
$app->get('/checkinstall/', function ()
{  
	try {	
		global $dbContainer;
		$db = $dbContainer['db'];
	
		$result = $db->prepare("SELECT * FROM PUBLICATION");
	
		$result->execute();
		$checkInstall = $result->fetchAll();
		
		echo '{"MagRocket API":{"Success":"Database Connection Test Successful"}}';
	}
	catch(PDOException $e) {
		echo '{"MagRocket API":{"Error":"' . $e->getMessage() . '"}}';
	}
});

// Issues List
// *Retrieves a list of available issues for the App ID, for population of Baker Shelf
$app->get('/issues/:app_id/:user_id', function ($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
    
    // Lookup Issue Download Security condition for Publication, if true, create secured API Issue download links 
	$result = $db->query("SELECT ISSUE_DOWNLOAD_SECURITY FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$issueDownloadSecurity = $result->fetchColumn();
	
	// Query all issues for the incoming APP_ID
	$sql = "SELECT * FROM ISSUES WHERE APP_ID = '$app_id' AND AVAILABILITY = 'published'";
	
	try {	
		$IssuesArray = array();
		$i = 0;
		foreach($db->query($sql) as $row) {
			$IssuesArray[$i]['name'] = $row['NAME'];
			$IssuesArray[$i]['title'] = $row['TITLE'];
			$IssuesArray[$i]['info'] = $row['INFO'];
			$IssuesArray[$i]['date'] = $row['DATE'];
			$IssuesArray[$i]['cover'] = $row['COVER'];
			
			if ($issueDownloadSecurity == "TRUE") {
				$IssuesArray[$i]['url'] = "http://" . $_SERVER['HTTP_HOST'] . "/issue/" . $app_id . "/" . $user_id . "/" . $row['NAME'];
			}
			else{
				$IssuesArray[$i]['url'] = $row['URL'];
			}
			$IssuesArray[$i]['product_id'] = $row['PRODUCT_ID'];
			$i++;
		}
		echo json_encode($IssuesArray);
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// Issue Download
// *Validates availability of download of a specific named issue, redirects to download if available
$app->get('/issue/:app_id/:user_id/:name', function ($app_id, $user_id, $name) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];

	try {
			$result = $db->prepare("SELECT * FROM ISSUES WHERE APP_ID = '$app_id' AND NAME = '$name' LIMIT 0,1");

			$result->execute();
			$issue = $result->fetch();
	
			// The Issue is not found for App_ID and Name.  Throw 404 not found error.
			if (!$issue) {
				header('HTTP/1.1 404 Not Found');
				die();
			}
	
			// Retrieve issue Issue Product ID to cross check with purchases
			$product_id = $issue['PRODUCT_ID'];

			// Default to not allow download.		
			$allow_download = false;
			
			// Validate that the Product ID (from Issue Name) is an available download for given user		
			if ($product_id) {
				// Allow download if the issue is marked as purchased
				$result = $db->query("SELECT COUNT(*) FROM PURCHASES 
													WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' AND PRODUCT_ID = '$product_id'");		
														
				$allow_download = ($result->fetchColumn() > 0);
			} else if ($issue) {
				// No product ID -> the issue is free to download
				$allow_download = true;
			}
		
			if ($allow_download) {
				// Redirect to the downloadable file, nothing else needed in API call
				$app->response()->redirect($issue['URL'], 303);
			}
			else {
				header('HTTP/1.1 403 Forbidden');
				die();
			}
		}
		catch(PDOException $e) {
			// Handle exception
			logMessage($e->getMessage());
		}
});

// Purchases List
// *Returns a list of Purchased Product ID's
$app->get('/purchases/:app_id/:user_id', function ($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	$purchased_product_ids = array();
	
	try {
		$subscribed = false;

		// Retrieve latest receipt for Auto-Renewable-Subscriptions for the APP_ID, USER_ID combination
		$result = $db->query("SELECT BASE64_RECEIPT FROM RECEIPTS
									     WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' AND type = 'auto-renewable-subscription'
									     ORDER BY transaction_id DESC LIMIT 0, 1");
		
		$base64_latest_receipt = $result->fetchColumn();
		if($base64_latest_receipt)
		{
			$userSubscription = checkSubscription($app_id, $user_id);
			$dateLastValidated = new DateTime($userSubscription["LAST_VALIDATED"]);
			$dateExpiration = new DateTime($userSubscription["EXPIRATION_DATE"]);
			$dateCurrent = new DateTime('now');
			$interval = $dateCurrent->diff($dateLastValidated);
	
			logMessage($interval->format('%h hours %i minutes'));
			logMessage($user_id);
	
			// Only refresh and re-verify receipt if greater than 12 hours (max one day) before last check
			if ($interval->format('%h') > 12 || $interval->format('%a') > 1) {
				// Check the latest receipt from the subscription table
	
				if ($base64_latest_receipt) {
					$data = verifyReceipt($base64_latest_receipt, $app_id);
	
					markIssuesAsPurchased($data, $app_id, $user_id);
	
					// Check if there is an active subscription for the user.  Status=0 is true.
					$subscribed = ($data->status == 0);
				}
				else {
					// There is no receipt for this user, there is no active subscription
					$subscribed = false;
				}
			}
			else {
				// We aren't going to re-verify the receipt now, but we should determine if the Expiration Date is beyond now
				if ($dateCurrent > $dateExpiration) {
					$subscribed = false;
				}
				else {
					$subscribed = true;
				}
			}
		}
	
		$result = $db->query("SELECT PRODUCT_ID FROM PURCHASES
								WHERE APP_ID = '$app_id' AND USER_ID = '$user_id'");
			
		$purchased_product_ids = $result->fetchAll(PDO::FETCH_COLUMN);

		echo json_encode(array(
			'issues' => $purchased_product_ids,
			'subscribed' => $subscribed
		));
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// iTunes List
// *Returns a list of Issues in an iTunes ATOM Feed XML Format.  This can be hooked up to the FEED URL within
//  iTunes connect to display up to date information in the Newsstand App Store listing
$app->get('/itunes/:app_id', function ($app_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$sql = "SELECT * FROM ISSUES WHERE APP_ID = " . "'" . $app_id . "'";

	try {
		$iTunesUpdateDate = "2011-08-01T00:00:00-07:00";
		$AtomXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"" . "?>";
		$AtomXML.= "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:news=\"http://itunes.apple.com/2011/Newsstand\">";
		$AtomXML.= "<updated>" . $iTunesUpdateDate . "</updated>";
		foreach($db->query($sql) as $row) {
			$AtomXML.= "<entry>";
			$AtomXML.= "<id>" . $row['NAME'] . "</id>";
			$AtomXML.= "<updated>" . $row['ITUNES_UPDATED'] . "</updated>";
			$AtomXML.= "<published>" . $row['ITUNES_PUBLISHED'] . "</published>";
			$AtomXML.= "<summary>" . $row['ITUNES_SUMMARY'] . "</summary>";
			$AtomXML.= "<news:cover_art_icons>";
			$AtomXML.= "<news:cover_art_icon size=\"SOURCE\" src=\"" . $row['ITUNES_COVERART_URL'] . "\"/>";
			$AtomXML.= "</news:cover_art_icons>";
			$AtomXML.= "</entry>";
		}
		$AtomXML.= "</feed>";
		echo utf8_encode($AtomXML);
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// Confirm Purchase
// *Confirms the purchase by validating the Receipt_Data received for the in app purchase.  Records the receipt data
//  in the database and adds the available issues to the user's Purchased List
$app->post('/confirmpurchase/:app_id/:user_id', function ($app_id, $user_id) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$body = $app->request()->getBody();
	$receiptdata = $app->request()->post('receipt_data');
	$type = $app->request()->post('type');
	
	try {
		$iTunesReceiptInfo = verifyReceipt($receiptdata, $app_id);
		
		$sql = "INSERT IGNORE INTO RECEIPTS (APP_ID, QUANTITY, PRODUCT_ID, TYPE, TRANSACTION_ID, USER_ID, PURCHASE_DATE, 
	 		    			ORIGINAL_TRANSACTION_ID, ORIGINAL_PURCHASE_DATE, APP_ITEM_ID, VERSION_EXTERNAL_IDENTIFIER, BID, BVRS, BASE64_RECEIPT) 
	 		    			VALUES (:app_id, :quantity, :product_id, :type, :transaction_id, :user_id, :purchase_date, :original_transaction_id,
	 		    					  :original_purchase_date, :app_item_id, :version_external_identifier, :bid, :bvrs, :base64_receipt)";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("app_id", $app_id);
			$stmt->bindParam("quantity", $iTunesReceiptInfo->receipt->quantity);
			$stmt->bindParam("product_id", $iTunesReceiptInfo->receipt->product_id);
			$stmt->bindParam("type", $type);
			$stmt->bindParam("transaction_id", $iTunesReceiptInfo->receipt->transaction_id);
			$stmt->bindParam("user_id", $user_id);
			$stmt->bindParam("purchase_date", $iTunesReceiptInfo->receipt->purchase_date);
			$stmt->bindParam("original_transaction_id", $iTunesReceiptInfo->receipt->original_transaction_id);
			$stmt->bindParam("original_purchase_date", $iTunesReceiptInfo->receipt->original_purchase_date);
			$stmt->bindParam("app_item_id", $iTunesReceiptInfo->receipt->item_id);
			$stmt->bindParam("version_external_identifier", $iTunesReceiptInfo->receipt->version_external_identifier);
			$stmt->bindParam("bid", $iTunesReceiptInfo->receipt->bid);
			$stmt->bindParam("bvrs", $iTunesReceiptInfo->receipt->bvrs);
			$stmt->bindParam("base64_receipt", $receiptdata);
			$stmt->execute();

			// If successful, record the user's purchase
			if($type == 'auto-renewable-subscription'){
				markIssuesAsPurchased($iTunesReceiptInfo,$app_id,$user_id);
			}else if($type == 'issue'){
				markIssueAsPurchased($iTunesReceiptInfo->receipt->product_id, $app_id, $user_id);				
			}else if($type == 'free-subscription'){
				// Nothing to do, as the server assumes free subscriptions won't be enabled				
			}				

		}
		catch(PDOException $e) {
			logMessage($e->getMessage());
			echo '{"error":{"text":"' . $e->getMessage() . '"}}';
		}
	}
	catch(Exception $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// APNS Token
// *Stores the APNS Token in the database for the given App ID and User ID
$app->post('/apns/:app_id/:user_id', function ($app_id, $user_id) use($app)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$apns_token = $app->request()->post('apns_token');

	$sql = "INSERT IGNORE INTO APNS_TOKENS (APP_ID, USER_ID, APNS_TOKEN) 
 		    			VALUES (:app_id, :user_id, :apns_token)";
 		    			
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("apns_token", $apns_token);
		$stmt->execute();
		echo '{"success":{"message":"' . $apns_token . '"}}';
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
});

// ************************************************
// Utility Functions
// ************************************************

// Log Error Messages for tracking and debugging purposes, also displayed in the MagRocket Admin for issue debugging
function logMessage($logMessage)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$logType = 1;
	
	$sql = "INSERT INTO SYSTEM_LOG (TYPE, MESSAGE) 
		    			VALUES (:logtype, :logmessage)";
		    			
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("logtype", $logType);
		$stmt->bindParam("logmessage", $logMessage);
		$stmt->execute();
	}
	catch(PDOException $e) {
		// Error occurred, just ignore
		logMessage($e->getMessage());
	}
}

// Mark all available issues as purchased for a given user
function markIssuesAsPurchased($app_store_data, $app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
		
	$receipt = $app_store_data->receipt;
	$startDate = new DateTime($receipt->purchase_date_pst);
	
	if ($app_store_data->status == 0) {
		$endDate = new DateTime($app_store_data->latest_receipt_info->expires_date_formatted_pst);
	}
	else
	if ($app_store_data->status == 21006) {
		$endDate = new DateTime($app_store_data->latest_expired_receipt_info->expires_date_formatted_pst);
	}

	// Now update the Purchases table with all Issues that fall within the subscription start and expiration date
	$startDateFormatted = $startDate->format('Y-m-d H:i:s');
	$endDateFormatted = $endDate->format('Y-m-d H:i:s');

	// Update Subscriptions Table for user with current active subscription start and expiration date
	updateSubscription($app_id, $user_id, $startDateFormatted, $endDateFormatted);

	// Lookup development mode condition for Publication if in Development mark all issues as purchased, if production
	// mark only those that exist within subscription period
	$result = $db->query("SELECT DEVELOPMENT_MODE FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$development_mode = $result->fetchColumn();
	
	if($development_mode == "TRUE"){
		logMessage("DEVELOPMENT MODE TRUE");
		// For Testing, marking only with Subscription start date, not expiration date	
		//$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
	   //   							WHERE APP_ID = '$app_id'
	   //   							AND `DATE` > '$startDateFormatted'");
		
		// For Testing Purposes in development mark all as available
		$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
		  							 WHERE APP_ID = '$app_id'");
	}
	else{
		logMessage("DEVELOPMENT MODE FALSE");
		// For Production
		$result = $db->query("SELECT PRODUCT_ID FROM ISSUES
									WHERE APP_ID = '$app_id'
									AND `DATE` >= '$startDateFormatted'
									AND `DATE` <= '$endDateFormatted'");
	}

	$product_ids_to_mark = $result->fetchAll(PDO::FETCH_COLUMN);
	
	$insert = "INSERT IGNORE INTO PURCHASES (APP_ID, USER_ID, PRODUCT_ID)
						VALUES ('$app_id', '$user_id', :product_id)";
						
	$stmt = $db->prepare($insert);
	
	foreach($product_ids_to_mark as $key => $product_id) {
		$stmt->bindParam(':product_id', $product_id);
		$stmt->execute();
	}
}

// Mark all available issues as purchased for a given user
function markIssueAsPurchased($product_id, $app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	logMessage($product_id);
	
	$sql = "INSERT IGNORE INTO PURCHASES (APP_ID, USER_ID, PRODUCT_ID) 
	    			VALUES (:app_id, :user_id, :product_id)";
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("product_id", $product_id);
		$stmt->execute();
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
}

// Update the Subscription Record for a specific user with Effective Date and Expiration Date
function updateSubscription($app_id, $user_id, $effective_date, $expiration_date)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$currentDate = new DateTime('now');
	$lastValidated = $currentDate->format('Y-m-d H:i:s');
	
	$sql = "INSERT INTO SUBSCRIPTIONS (APP_ID, USER_ID, EFFECTIVE_DATE, EXPIRATION_DATE, LAST_VALIDATED) 
	    			VALUES (:app_id, :user_id, :effective_date, :expiration_date, :last_validated)
	    			ON DUPLICATE KEY UPDATE EFFECTIVE_DATE=:effective_date, EXPIRATION_DATE=:expiration_date, LAST_VALIDATED=:last_validated";
	
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam("app_id", $app_id);
		$stmt->bindParam("user_id", $user_id);
		$stmt->bindParam("effective_date", $effective_date);
		$stmt->bindParam("expiration_date", $expiration_date);
		$stmt->bindParam("last_validated", $lastValidated);
		$stmt->execute();
	}
	catch(PDOException $e) {
		logMessage($e->getMessage());
		echo '{"error":{"text":"' . $e->getMessage() . '"}}';
	}
}

// Check if the user has a current active Subscription and determine expiration date
function checkSubscription($app_id, $user_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	$result = $db->prepare("SELECT EFFECTIVE_DATE, EXPIRATION_DATE, LAST_VALIDATED FROM SUBSCRIPTIONS
										WHERE APP_ID = '$app_id' AND USER_ID = '$user_id' LIMIT 0,1");
	$result->execute();
	$data = $result->fetch();
	
	return $data;
}

// Validate InApp Purchase Receipt, by calling the Apple iTunes verifyReceipt method
// *Note that this seems to take between 2-4 seconds on average
function verifyReceipt($receipt, $app_id)
{
	global $dbContainer;
	$db = $dbContainer['db'];
	
	// Lookup shared secret from Publication table
	$result = $db->query("SELECT ITUNES_SHARED_SECRET FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$sharedSecret = $result->fetchColumn();

	// Lookup development mode condition for Publication if in Development, if development mode is false use the Production endpoint
	$result = $db->query("SELECT DEVELOPMENT_MODE FROM PUBLICATION WHERE APP_ID = '$app_id' LIMIT 0, 1");	
	$development_mode = $result->fetchColumn();
	
	if ($development_mode == "TRUE") {
		$endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
	}
	else {
		$endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
	}

	// If no shared secret exists, don't send it to the verifyReceipt call, however it should exist!
	if($sharedSecret){
		$postData = json_encode(array(
		'receipt-data' => $receipt,
		'password' => $sharedSecret));
	}else{
		$postData = json_encode(array(
		'receipt-data' => $receipt));
	}
	
	$ch = curl_init($endpoint);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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

	if (!isset($data->status) || ($data->status != 0 && $data->status != 21006)) {
		$product_id = $data->receipt->product_id;
		logMessage("Invalid receipt for $product_id : status " . $data->status);
		throw new Exception('Invalid receipt');
	}

	return $data;
}

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();


// Timer class for debugging and logging use
class timer
{
	var $start;
	var $pause_time;
	/*  start the timer  */
	function timer($start = 0)
	{
		if ($start) {
			$this->start();
		}
	}

	/*  start the timer  */
	function start()
	{
		$this->start = $this->get_time();
		$this->pause_time = 0;
	}

	/*  pause the timer  */
	function pause()
	{
		$this->pause_time = $this->get_time();
	}

	/*  unpause the timer  */
	function unpause()
	{
		$this->start+= ($this->get_time() - $this->pause_time);
		$this->pause_time = 0;
	}

	/*  get the current timer value  */
	function get($decimals = 8)
	{
		return round(($this->get_time() - $this->start) , $decimals);
	}

	/*  format the time in seconds  */
	function get_time()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}

?>