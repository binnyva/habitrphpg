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
				'enable_cache'	=> true,   // FOR DEVELOPMENT ONLY.
				'cache_path'	=> '/tmp/' // Use this for faster testing
			);

	///Constructor
	function __construct($user_id, $api_key) {
		$this->user_id = $user_id;
		$this->api_key = $api_key;
	}

	private function _request($method, $operation, $data = '') {
		if(!function_exists("curl_init")) die("HabitRPG Library requires curl to function.");

		$url = $this->base_url . 'api/v1/' . $operation;
		if($method == 'get') $url .= '/' . $data;

		$url_parts = parse_url($url);
		$ch = curl_init($url_parts['host']);

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
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

	function createTask($type, $text, $data = array()) {
		$data['type'] = $type;
		$data['text'] = $text;
		if(!isset($data['completed'])) $data['completed'] = false;
		if(!isset($data['value'])) $data['value'] = 0;
		if(!isset($data['notes'])) $data['notes'] = "";

		return $this->_request("post", "user/task/", $data);
	}

	function updateTask($task_id, $data) {
		return $this->_request("put", "user/task/$task_id", $data);	
	}

	function doTask($task_id, $direction) {
		return $this->_request("post", "users/{$this->user_id}/tasks/$task_id/$direction", array('apiToken'=>$this->api_key));
	}


}
