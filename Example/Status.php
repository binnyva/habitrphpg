<?php
require('../HabitRPHPG.php');

$api = new HabitRPHPG("USER_ID_GOES_HERE", "API_KEY_GOES_HERE");
$data = $api->user();
$stats = $data['stats'];
print $data['profile']['name'] . " (Level $stats[lvl])\n";
print "Health:\t\t" . getFillStatus($stats['hp'], $stats['maxHealth']) . "\n";
print "Experiance:\t". getFillStatus($stats['exp'], $stats['toNextLevel']) . "\n";


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

