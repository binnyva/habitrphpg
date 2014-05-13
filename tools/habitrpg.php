<?php
require('iframe.php');
require('../HabitRPHPG.php');
// Set the User ID and password. Get this from https://habitrpg.com/#/options/settings/api
$user_id = '53a24ef5-fe71-4267-9b0b-91e52cadfade';
$api_key = '26d0cc70-6583-4660-ab91-b7877c73f2c0';

$api = new HabitRPHPG($user_id, $api_key);

if(isset($argv[1])) {
	switch ($argv[1]) {
		case 'status':
			status();
			break;
		
		case 'habit':
		case 'daily':
		case 'todo':
		case 'reward':
		case 'task':
			$search = '';
			if(!empty($argv[2])) $search = $argv[2];

			task($argv[1], $search);
			break;

		case '+':
		case '-':
		case 'do':
		case 'done':
		case 'did':
		case 'up':
		case 'down':
			if(empty($argv[2])) die("Usage: habitrpg $argv[1] <task string>");
			$direction = 'up';
			if($argv[1] == '-' or $argv[1] == 'down') $direction = 'down';

			doTask($direction, $argv[2]);
			break;

		default:
			# code...
			break;
	}
} else {

	status();
}


function status() {
	global $api;

	$data = $api->user();
	$stats = $data['stats'];
	print $data['profile']['name'] . " (Level $stats[lvl])\n";
	print "Health:\t\t" . getFillStatus($stats['hp'], $stats['maxHealth']) . "\n";
	print "Experiance:\t". getFillStatus($stats['exp'], $stats['toNextLevel']) . "\n";
	list($gold,$silver) = explode(".", substr($stats['gp'],0,5));
	print "Gold: $gold | Silver: $silver\n";
}

function task($type = '', $search = '') {
	global $api;
	if($type == 'task') $type = '';

	// If search is set on task, show only things that match.
	$data = _search($search);

	foreach ($data as $task) {
		// Don't show the task if already completed.
		if($task['type'] == 'todo' and (isset($task['completed']) and $task['completed'])) continue; 

		// Show the task only if the type matches
		if($type and $task['type'] != $type) continue; 
		
		print $task['text'] . "\n";
	}
}

function doTask($direction, $task_string) {
	global $api;
	$tasks = _search($task_string);

	if(count($tasks) == 1) {
		$result = $api->doTask($tasks[0]['id'], $direction);
		dump($result);
		print "Task '{$tasks[0]['text']}' is done\n";

	} elseif(count($tasks) > 1) {
		foreach ($tasks as $task) {
			print $task['text'] . "\n";
		}

	} else {
		print "Could not find any tasks matching '$task_string'\n";
	}
}

/// Return only the tasks that matches the search string.
function _search($task_string) {
	global $api;

	$returns = array();
	$data = $api->task();
	if(!$task_string) return $data;

	foreach ($data as $task) {
		if(stripos($task['text'], $task_string) !== false) 
			$returns[] = $task;
	}
	return $returns;
}



// Support functions...
function getFillStatus($current, $full) {
	$output = "$current/$full  ";

	$percent = intval(($current / $full) * 100);
	$total_block_count = 20;
	$block_count = round($percent / (100 / $total_block_count));
	$output .= "[";
	$output .= str_repeat("#", $block_count);
	$output .= str_repeat(" ", $total_block_count - $block_count);
	$output .= "]";

	return $output;
}


