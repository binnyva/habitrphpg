#!/usr/bin/php
<?php
require(dirname(dirname(__FILE__)).'/HabitRPHPG.php');
@include('iframe.php');

// Set the User ID and password. Get this from https://habitrpg.com/#/options/settings/api
include('config.php');
$cache = false;

$api = new HabitRPHPG($user_id, $api_key);

if(isset($argv[1])) {	
	$action_index = 1;
	$action = $argv[$action_index];

	switch ($action) {
		case 'status':
			status();
			break;

		case 'inventory':
		case 'inv':
			inventory();
			break;

		case 'pets':
			showPets();
			break;

		case 'feed':
			if(!isset($argv[$action_index+2])) {
				print "Useage: habitrpg feed <food> <pet>\n";
				exit;
			}
			$food = $argv[$action_index + 1];
			$pet  = $argv[$action_index + 2];
			feed($food, $pet);
			break;

		case 'habit':
		case 'daily':
		case 'todo':
		case 'reward':
		case 'task':
			$search = '';
			if(!empty($argv[$action_index + 1])) $search = implode(" ", array_slice($argv, $action_index + 1));

			task($argv[$action_index], $search);
			break;

		case '+':
		case '-':
		case 'do':
		case 'done':
		case 'did':
		case 'up':
		case 'down':
			if(empty($argv[$action_index + 1])) die("Usage: habitrpg {$argv[$action_index]} <task string>");
			$direction = 'up';
			if($argv[$action_index] == '-' or $argv[$action_index] == 'down') $direction = 'down';

			$task_string = '';
			if(!empty($argv[$action_index + 1])) {
				$task_string = implode(" ", array_slice($argv, $action_index + 1));	
			}

			doTask($direction, $task_string);
			break;

		case 'add':
			if(isset($argv[$action_index + 2])) {
				$type = $argv[$action_index + 1];
				$task_name = $argv[$action_index + 2];
			}

			if(empty($argv[$action_index + 2]) or ($type != 'daily' and $type != 'todo' and $type != 'habit')) {
				print "Usage: habitrpg add (daily|todo|habit) <Task Name>\n";
				exit;
			}

			$data = $api->createTask($type, $task_name);
			if(isset($data['id'])) {
				print "Task '$task_name'($type) created.\n";
			} else {
				print "Error creating task.\n";
			}
			break;

		case 'help':
			print <<<END
Usage: habitrpg [<action> [<data>]]

Commands:
	habitrpg				Shows the user's status - Name, Level, Health, Experience, Gold and Silver
	habitrpg add (daily|todo|habit) <Task Name>		Add new task of type 'daily/todo/habit' with the text <Task Name>
	habitrpg task [<search string>]		Lists all the tasks of the current user
	habitrpg habit [<search string>] 	Lists all the habits of the current user
	habitrpg daily [<search string>] 	Lists all the dailies of the current user
	habitrpg todo [<search string>] 	Lists all the todos of the current user
	habitrpg reward [<search string>] 	Lists all the rewards of the current user
	habitrpg + <task keyword>		Mark the task as done. <task keyword> is a string within the task name. If it matches a unique task, that will be marked done. If not, a list of matching tasks are shown.
	habitrpg - <task keyword>		Mark the task as not done. <task keyword> is same as last command.
	habitrpg inventory			Show the inventory of the current user - Eggs, Hatching Potions, Food and Quests
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

function inventory() {
	global $api;

	$data = $api->user();
	showItems("eggs", $data['items']);
	showItems("hatchingPotions", $data['items']);
	showItems("food", $data['items']);
	showItems("quests", $data['items']);
}

function showItems($type, $items) {
	$all_items = $items[$type];
	$have_items = array();
	foreach ($all_items as $key => $value) {
		if($value)
			$have_items[$key] = $value;
	}

	if($have_items) {
		print " + " . formatKey($type) . "\n";

		foreach ($have_items as $key => $value) {
			print "\t" . formatKey($key) . "($value)\n";
		}
	}
}

function feed($food, $pet) {
	global $api;
	$api->feed($food, $pet);

	print "Pet fed\n";
}

function showPets() {
	global $api;

	$data = $api->user();
	$pets = $data['items']['pets'];
	foreach ($pets as $key => $value) {
		// Anything with value over 50 is a mount. // :TODO Show a percentage thingy.
		print formatKey($key) . "($value), ";
	}
	print "\n";
}

function info($stats) {
	global $api;

	$stats = $api->getStats($stats);
	if(isset($stats['maxHealth']) and $stats['maxHealth']) {
		print "Health:\t\t" . getFillStatus($stats['hp'], $stats['maxHealth']) . "\n";
		print "Experience:\t". getFillStatus($stats['exp'], $stats['toNextLevel']) . "\n";
		if($stats['lvl'] > 10) print "Mana:\t\t". getFillStatus($stats['mp'], $stats['maxMP']) . "\n";
	} else {
		print "Health: " . $stats['hp'] . "\n";
		print "Experience: " . $stats['exp'] . "\n";
	}

	print "Gold: $stats[gold] | Silver: $stats[silver]\n";

	// Show Delta
	$status_file = dirname(__FILE__) . '/cache/user_status.json';
	$old_status = json_decode(file_get_contents($status_file), true);

	if($stats != $old_status) {
		print "Change: ";
		foreach($stats as $name => $value) {
			if($name == 'maxHealth' or $name == 'toNextLevel') continue;
			if($stats[$name] != $old_status[$name] and ($stats[$name] - $old_status[$name]) > 0) 
				print ucfirst($name) . ": " . ($stats[$name] - $old_status[$name]) . " | ";
		}
		print "\n";
	}

	file_put_contents($status_file, json_encode($stats));

	global $cache;
	if($cache) {

	}
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
		print "Task '{$tasks[0]['text']}' is done.\n";
		showDrops($result);
		print "\n";
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

function showDrops($result) {
	if(isset($result['_tmp']['drop'])) {
		print $result['_tmp']['drop']['dialog'] . "\n";
	}
}

/// Return only the tasks that matches the search string.
function _search($task_string) {
	global $api;

	return $api->findTask($task_string);
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

function formatKey($text) {
	//return format($text);
	return $text;
}