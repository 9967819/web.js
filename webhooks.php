<?php
#session_start();

#error_reporting(E_ALL);
require_once dirname(__FILE__) . '/wp-load.php';
require_once dirname(__FILE__) . '/wp-includes/post.php';

function update_post($data = array(), $options = array())
{
	$user = DB_USER;
	$password = DB_PASSWORD;
	$table = 'wp_posts';
	$dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", '127.0.0.1', 'ahnjournal_frontend', 'utf8mb4');
	$dbh = new PDO($dsn, $user, $password) or die("snsfeoinwefe asdhns dsoawnd!!!\n");
	

	#KEEP THIS
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$sql = "UPDATE $table SET likes_count=:likes_count WHERE id=:id";
	try {
		$stmt = $dbh->prepare($sql);
		$stmt->execute($data);
	} catch (PDOException $e) {
		echo "Fatal error updating record: " . $e->getMessage();
		return false;
	}
	return true;

}


function add_vote_to_post($post_id, $redis, $debug = false) {
	$data = array(
			'code'  => 201, 
			'voted' => false, 
			'debug' => $debug);
	if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		
	$remoteip = $_SERVER['X_REAL_IP'] ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
	
	$post = get_post($post_id);
	#$has_voted = false;

	# Make sure i can debug the sql part in dev mode
	if ($debug == true) {
		$redis->sRem('clients', $remoteip);
	}
	# current post is a set
	$post_key = sprintf("post_%d", $post_id);
	


	if (! $redis->sIsMember('clients', $remoteip) && 
  	    ! $redis->sIsMember($post_key, $remoteip)){

		$count = intval($post->likes_count) + 1; # n+1
		update_post(array('id' => $post_id, 'likes_count' => $count)); 	
		$data = array(
			"count"  => $post->likes_count, 
			"voted"  => true, 
			"code"   => 200,
			'remote_addr' => $remoteip);

		$redis->sAdd($post_key, $remoteip);	
		$redis->sAdd('clients', $remoteip);
	} else {
		# Client has already voted for this post
		$data = array(
			"voted"       => false, 
			"code"        => 403,
			"message" 	  => "denied",
			"client_addr" => $remoteip);
		if ($debug == true) {
			$data["count"] = intval($post->likes_count);
		}
	}

	$redis->save(); #Save transaction and close redis connection. 
	$redis->close();
	}//REQUEST_METHOD IS POST
	return $data;

}

global $debug;
$debug = true;

#if ($_SERVER['X_REAL_IP'] == '192.168.0.193'){
#	$debug = true;
#}	

if (! empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
	$post_id = intval($_GET['id']);

	$json = add_vote_to_post($post_id, REDIS_CLIENT, $debug);
	if ($json['code'] == 200){
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(200);
		echo json_encode($json);
	} else {
		# Maximum 1 like per post allowed for each real IP addresses.
		http_response_code(403);
		echo json_encode(array("code" => 403));
	}	
}	
exit;
