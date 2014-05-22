#!/usr/bin/php
<?php
require(dirname(dirname(__FILE__)).'/HabitRPHPG.php');

// Set the User ID and password. Get this from https://habitrpg.com/#/options/settings/api
include('config.php');

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
			if(!empty($argv[2])) $search = implode(" ", array_slice($argv, 2));

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

			$task_string = '';
			if(!empty($argv[2])) $task_string = implode(" ", array_slice($argv, 2));

			doTask($direction, $argv[2]);
			break;

		case 'help':
			print <<<END
Usage: habitrpg [<action> [<data>]]

Commands:
	habitrpg				Shows the user's status - Name, Level, Health, Experiance, Gold and Silver
	habitrpg task [<search string>]		Lists all the tasks of the current user
	habitrpg habit [<search string>] 	Lists all the habits of the current user
	habitrpg daily [<search string>] 	Lists all the dailies of the current user
	habitrpg todo [<search string>] 	Lists all the todos of the current user
	habitrpg reward [<search string>] 	Lists all the rewards of the current user
	habitrpg + <task keyword>		Mark the task as done. <task keyword> is a string within the task name. If it matches a unique task, that will be marked done. If not, a list of matching tasks are shown.
	habitrpg - <task keyword>		Mark the task as not done. <task keyword> is same as last command.
	habitrpg help 				Shows this screen


END;

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
	info($stats);
}

function info($stats) {
	if(isset($stats['maxHealth'])) {
		print "Health:\t\t" . getFillStatus($stats['hp'], $stats['maxHealth']) . "\n";
		print "Experiance:\t". getFillStatus($stats['exp'], $stats['toNextLevel']) . "\n";
	} else {
		print "Health: " . $stats['hp'] . "\n";
		print "Experiance: " . $stats['exp'] . "\n";
	}

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
		
		showTask($task);
	}
}

function doTask($direction, $task_string) {
	global $api;
	$tasks = _search($task_string);

	if(count($tasks) == 1) {
		$result = $api->doTask($tasks[0]['id'], $direction);
		print "Task '{$tasks[0]['text']}' is done.\n\n";
		info($result);

	} elseif(count($tasks) > 1) {
		print "Search phrase '$task_string' matches the following tasks...\n";
		foreach ($tasks as $task) {
			showTask($task);
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

function showTask($task) {
	print " + " . $task['text'] . "\n";
}


// Support functions...
function getFillStatus($current, $full) {
	$output = "$current/$full\t";

	$percent = intval(($current / $full) * 100);
	$total_block_count = 20;
	$block_count = round($percent / (100 / $total_block_count));
	$output .= "[";
	$output .= str_repeat("#", $block_count);
	$output .= str_repeat(" ", $total_block_count - $block_count);
	$output .= "]";

	return $output;
}


