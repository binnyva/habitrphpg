<?php
/**
 * HabitRPHPG
 * A PHP interface to the API provided by the HabitRPG game.
 * https://github.com/binnyva/habitrphpg
 */
class HabitRPHPG {
	private $user_id = '';
	private $api_key = '';
	private $base_url = 'https://habitrpg.com/';
	private $json_return_format_is_array = true;
	private $options = array(
				'enable_cache'	=> false,   // FOR DEVELOPMENT ONLY.
				'cache_path'	=> '/tmp/'	// Use this for faster testing
				'debug'			=> false	// Development only.
			);

	///Constructor
	function __construct($user_id, $api_key) {
		$this->user_id = $user_id;
		$this->api_key = $api_key;
	}

	private function _request($method, $operation, $data = '') {
		if(!function_exists("curl_init")) die("HabitRPG Library requires curl to function.");

		$url = $this->base_url . 'api/v2/' . $operation;
		if($method == 'get') $url .= '/' . $data;

		$url_parts = parse_url($url);
		$ch = curl_init($url_parts['host']);

		$data_sting = '';
		if(is_array($data)) {
			foreach($data as $key=>$value) { $data_sting .= $key.'='.$value.'&'; }
			rtrim($data_sting, '&');
		} else {
			$data_sting = $data;
		}

		// If cacheing is on and we have a cached copy of the request, use that.
		if($this->options['enable_cache']) {
			$cache_file = $this->options['cache_path'] . md5($url) . ".json";
			if(file_exists($cache_file)) {
				return json_decode(file_get_contents($cache_file), $this->json_return_format_is_array);
			}
		}
		curl_setopt($ch, CURLOPT_URL, $url) or die("Invalid cURL Handle Resouce");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
		// curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
		if(isset($options['encoding'])) curl_setopt($ch, CURLOPT_ENCODING, "application/json");

		if($method == 'post') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_sting);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		$custom_headers = array(
			"x-api-user: $this->user_id",
            "x-api-key: $this->api_key",
		);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		$response = curl_exec($ch);
		curl_close($ch);

		if($this->$options['debug']) {
			file_put_contents("/var/www/Others/Library/HabitRPHPG/dumps/" . str_replace("/", '_', $operation) . "_".rand()."_.json", $response);
		}

		// Save cached version of the file
		if($this->options['enable_cache']) {
			file_put_contents($cache_file, $response);
		}

		return json_decode($response, $this->json_return_format_is_array);
	}

	function user() {
		return $this->_request("get", "user");
	}

	function task($task_id = 0) {
		if($task_id == 0) return $this->_request("get", "user/tasks");
		else return $this->_request("get", "user/tasks/$task_id");
	}

	// Returns all the tasks matchnig the task string.
	function findTask($task_string) {
		$returns = array();
		$data = $this->task();
		if(!$task_string) return $data;

		foreach ($data as $task) {
			if($task['text'] == $task_string) { // Exact match - must be the task we are looking for.
				return array($task);

			} else if(stripos($task['text'], $task_string) !== false) {
				$returns[] = $task;
			}
		}
		return $returns;
	}

	function getStats($stats = false) {
		if(!$stats) {
			$data = $api->user();
			$stats = $data['stats'];
		}

		$stats['hp'] = round($stats['hp'], 1);

		if(strpos($stats['exp'], '.') !== false) list($experience,$dec) = explode(".", $stats['exp']);
		else $experience = $stats['exp'];

		if(strpos($stats['gp'], '.') !== false)  list($gold,$silver) = explode(".", substr($stats['gp'],0,5));
		else {
			$gold = $stats['gp'];
			$silver = 0;
		}

		return array(
			'hp'			=> $stats['hp'],
			'exp'			=> $experience,
			'maxHealth'		=> empty($stats['maxHealth']) ? 0 : $stats['maxHealth'],
			'toNextLevel'	=> empty($stats['toNextLevel']) ? 0 : $stats['toNextLevel'],
			'gold'			=> $gold,
			'silver'		=> $silver,
			'mp'			=> $stats['mp'],
			'maxMP'			=> empty($stats['maxMP']) ? 0 : $stats['maxMP'],
			'lvl'			=> empty($stats['lvl']) ? 0 : $stats['lvl']
		);
	}

	function createTask($type, $text, $data = array()) {
		$data['type'] = $type;
		$data['text'] = $text;
		if(!isset($data['completed'])) $data['completed'] = false;
		if(!isset($data['value'])) $data['value'] = 0;
		if(!isset($data['notes'])) $data['notes'] = "";

		return $this->_request("post", "user/tasks", $data);
	}

	function updateTask($task_id, $data) {
		return $this->_request("put", "user/task/$task_id", $data);	
	}

	/**
	 * Arguments:	$task_id - The ID of the task that should be marked done on not done.
	 *				$direction - should be 'up' or 'down'
	 */
	function doTask($task_id, $direction) {
		return $this->_request("post", "user/tasks/$task_id/$direction", array('apiToken'=>$this->api_key));
	}


}
