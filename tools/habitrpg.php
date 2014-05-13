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
			task($argv[1]);
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
	dump($stats);

	//print "Gems: "
}

function task($type = '') {
	global $api;
	if($type == 'task') $type = '';

	$data = $api->task();
	foreach ($data as $task) {
		if($type == 'todo' and (isset($task['completed']) and $task['completed'])) continue; // Don't show the task if already completed.

		if(!$type) print $task['text'] . "\n";
		else {
			if($task['type'] == $type) // Show the task only if the type matches
				print $task['text'] . "\n";
		}
	}

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


