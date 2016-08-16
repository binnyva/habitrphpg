<?php
/**
 * HabitRPHPG
 * A PHP interface to the API provided by the HabitRPG game.
 * https://github.com/binnyva/habitrphpg
 */
class HabitRPHPG {
	private $user_id = '';
	private $api_key = '';
	private $base_url = 'https://habitica.com/';
	private $json_return_format_is_array = true;
	private $options = array(
				'enable_cache'	=> false,   // FOR DEVELOPMENT ONLY.
				'cache_path'	=> '/tmp/',	// Use this for faster testing
				'debug'			=> false,	// Development only.
			);
	private $egg_types = array('Wolf', 'TigerCub', 'PandaCub','LionCub','Fox','FlyingPig','Dragon','Cactus','BearCub', 	// Standard
			'Egg','Gryphon'		// Extras.
		);
	private $hatch_types = array('Base','White','Desert','Red','Shade','Skeleton','Zombie','CottonCandyPink','CottonCandyBlue','Golden');
	private $food_types = array('Meat','Milk','Potatoe','Strawberry','Chocolate','Fish','RottenMeat','CottonCandyPink','CottonCandyBlue','Cake_Skeleton','Cake_Base','Honey','Saddle');
	private $pet_types = array();

	///Constructor
	function __construct($user_id, $api_key) {
		$this->user_id = $user_id;
		$this->api_key = $api_key;

		foreach ($this->egg_types as $pet) {
			foreach ($this->hatch_types as $type) {
				$this->pet_types[] = $pet . "-" . $type;
			}
		}
	}

	private function _request($method, $operation, $data = '') {
		if(!function_exists("curl_init")) die("HabitRPG Library requires curl to function.");

		$url = $this->base_url . 'api/v3/' . $operation;
		if($method == 'get' and $data) $url .= '/' . $data;

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
		//curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
		if(isset($options['encoding'])) curl_setopt($ch, CURLOPT_ENCODING, "application/json");

		if($method == 'post') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_sting);
		}

		$custom_headers = array(
			"x-api-user: {$this->user_id}",
            "x-api-key: {$this->api_key}",
		);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ignores certificate error

		$response = curl_exec($ch);
		// output error message if an error is occured
		if($response === false) die(curl_error($ch)); // more verbose output
		curl_close($ch);

		if($this->options['debug']) {
			file_put_contents("/var/www/Others/Library/HabitRPHPG/dumps/" . str_replace("/", '_', $operation) . "_".rand()."_.json", $response);
		}

		// Save cached version of the file
		if($this->options['enable_cache']) {
			file_put_contents($cache_file, $response);
		}

		$return = json_decode($response, $this->json_return_format_is_array);
		$tasks = $return;

		if($this->json_return_format_is_array) {
			if($return['success']) {
				if(isset($return['data'])) $tasks = $return['data'];
			}
			else return false;
		} else {
			die("Make sure HabitRPHPG::json_return_format_is_array is true for this to work");
		}

		return $tasks;
	}

	function user() {
		return $this->_request("get", "user");
	}

	function task($task_id = 0, $type = '') {
		$query = '';
		if($type) $query = "?type=$type";

		if($task_id == 0) $return = $this->_request("get", "tasks/user" . $query);
		else $return = $this->_request("get", "tasks/user/$task_id");

		return $return;
	}

	// Returns all the tasks matchnig the task string.
	function findTask($task_string, $type = '') {
		$returns = array();
		$data = $this->task(0, $type);
		if(!$task_string) return $data;

		foreach ($data as $task) {
			if($task['text'] == $task_string) { // Exact match - must be the task we are looking for.
				return array($task);

			} else if(stripos($task['text'], $task_string) !== false and (!isset($task['completed']) or $task['completed'] == false)) {
				$returns[] = $task;
			}
		}
		return $returns;
	}

	function getStats($stats = false) {
		if(!$stats) {
			$data = $this->user();
			$stats = $data['stats'];
		}

		$stats['hp'] = round($stats['hp'], 1);

		if(strpos($stats['exp'], '.') !== false) list($experience,$dec) = explode(".", $stats['exp']);
		else $experience = $stats['exp'];

		if(strpos($stats['gp'], '.') !== false) {
			list($gold,$silver) = explode(".", $stats['gp']);
			$silver = substr($silver, 0, 2);
		}
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

		return $this->_request("post", "tasks/user", $data);
	}

	function updateTask($task_id, $data) {
		return $this->_request("put", "tasks/$task_id", $data);	
	}

	/**
	 * Arguments:	$task_id - The ID of the task that should be marked done on not done.
	 *				$direction - should be 'up' or 'down'
	 */
	function doTask($task_id, $direction) {
		return $this->_request("post", "tasks/$task_id/score/$direction", array('apiToken'=>$this->api_key));
	}

	/**
	 * Arguments:	$food - The food item that should be fed. You must have this.
	 *				$pet - Pet indentifier. Will be something like 'Fox-Desert'. You have to have this
	 *				$show_error - Prints an error if you enter an invalid food or pet type.
	 */
	function feed($food, $pet, $show_error = true) {
		if(!in_array($food, $this->food_types)) {
			if($show_error) print "'$food' is not a valid food type.";
			return false;
		}
		if(!in_array($pet, $this->pet_types)) {
			if($show_error) print "'$pet' is not a valid pet type.";
			return false;
		}

		return $this->_request("post", "user/feed/$pet/$food");
	}

	/**
	 * Arguments:	$egg - The eggo that should be made preggo. You must have this.
	 *				$hatching_portion - Hatching portion to be used on the egg. Must have this.
	 *				$show_error - Prints an error if you enter an invalid food or pet type.
	 */
	function hatch($egg, $hatching_portion, $show_error = true) {
		if(!in_array($egg, $this->egg_types)) {
			if($show_error) print "'$egg' is not a valid egg type.";
			return false;
		}
		if(!in_array($hatching_portion, $this->hatch_types)) {
			if($show_error) print "'$hatching_portion' is not a valid hatching portion.";
			return false;
		}

		return $this->_request("post", "user/hatch/$egg/$hatching_portion");
	}
}
