<?php

include_once("config.php");
include_once("php-get-call.php");


const SYSTEM_DB_FILE  = __DIR__ ."/db.txt";
const SYSTEM_EMAIL  = ""; // your email
const TICKET_API_URL = "https://idenlink.de/M-S/osticket/api/http.php/tickets.json"; // OS TICKET API URL (https://domain.de/M-S/api/http.php/tickets.json)
const TICKET_API_KEY = ""; // OSTICKET API KEY
 

// Need to edit  include/class.api.php  to get this working, IP check is not well build.
//https://github.com/osTicket/osTicket/pull/3932/files
	 
	//secure db file
	chmod(SYSTEM_DB_FILE,0600);

   $error ="";
 
   $key1 = $_GET['key1'];
   
//Nur für Personen die den Link Key kennen
if ($key1 == $key1speicher){
	   
	$ip = get_ip();
	#echo "ip: " . $ip  ;
   
	  //Check all Tasks


	  //Load DB
	  $db  = array();

	  if ( file_exists( SYSTEM_DB_FILE ) ) {
		  $db = load();
	  }


	  
	  if ( ! empty( $db )) {
					$db_number_elements =  count($db);

					$index=0;
					foreach ($db as &$db_element) 
					{
							#echo "db_element: " . json_encode($db_element);
							#echo "index " . $index;
							$dateTimestamp1 = time();
							$dateTimestamp2 = $db_element['timestamp_next_datetime'];
							
							#echo "dateTimestamp2 and dateTimestamp1: " . $dateTimestamp2 . "<=" .  $dateTimestamp1 . "<br>";
							
							if($dateTimestamp2 <= $dateTimestamp1 ){

												#echo "greater than";
	
													
												//create ticket

												$config = array(
														'url'=>TICKET_API_URL,
														'key'=>TICKET_API_KEY
														);
												#echo "config: " . json_encode($config);
												# Fill in the data for the new ticket, this will likely come from $_POST.

												$data = array(
													'name'      =>      $db_element['name'],
													'email'     =>      $db_element['email'],
													'subject'   =>      $db_element['subject'],
													'message'   =>     $db_element['message'] . ' IP: '  . $_SERVER['REMOTE_ADDR'],
													'ip'        =>      $_SERVER['REMOTE_ADDR'], 
													'attachments' => array(),
													'topicid'   => $topicid,
												);
												#echo "data: " . json_encode($data);
												#echo "config[key]: " . $config['key'];

												/* 
												 * Add in attachments here if necessary
												
												$data['attachments'][] =
												array('filename.pdf' =>
														'data:image/png;base64,' .
															base64_encode(file_get_contents('/path/to/filename.pdf')));
												 */
												
												#pre-checks
												function_exists('curl_version') or die('CURL support required');
												function_exists('json_encode') or die('JSON support required');
												
												#set timeout
												set_time_limit(30);
												
												#curl post
												$ch = curl_init();
												curl_setopt($ch, CURLOPT_URL, $config['url']);
												curl_setopt($ch, CURLOPT_POST, 1);
												curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
												curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
												curl_setopt($ch, CURLOPT_HEADER, FALSE);
												curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
												curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
												curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
												$result=curl_exec($ch);
												$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
												curl_close($ch);
												
												if ($code != 201)
													die('Unable to create ticket: '.$result);
												
												$ticket_id = (int) $result;
												
												# Continue onward here if necessary. $ticket_id has the ID number of the
												# newly-created ticket
												
												echo "Ticket_id: " . $ticket_id;
												


												//renew datetime
												#echo "db_element: " . json_encode($db_element);
												$db_element['timestamp_next_datetime'] = time()+$db_element['timestamp_next_step'];
												#echo "db_element: " . json_encode($db_element);
												$db[$index] = $db_element;
						
							}

							$index=$index+1;
					}







		
	} else {
		$db[0] = array( "timestamp_datetime" => time(),"timestamp_next_datetime" => time()+10,"timestamp_next_step" => 10,"name"  => "sytem_nobody", "email" => SYSTEM_EMAIL, "subject" => "Test subject","message" => "Test message" , "topicid" => "Test topicid");
	}

	save( $db );





	
}else{
	die( "Link kann nur von intern aufgerufen werden! / Staff access only!");
}
  



//Funktionen
function load() {
	return unserialize( file_get_contents( SYSTEM_DB_FILE ) );
}

function save( $data ) {
	return file_put_contents( SYSTEM_DB_FILE, serialize( $data ) );
}
  

function get_ip() {
	global $_SERVER;
	$ip = null;

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}


?>
