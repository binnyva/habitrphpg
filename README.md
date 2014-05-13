# HabitRPHPG
 
## Version: 0.00.A

HabitRPG PHP library.

## Installation

git clone https://github.com/binnyva/habitrphpg

## Requires

* Curl Support for PHP

## Usage

    require("HabitRPHPG.php");
	$api = new HabitRPHPG("USER_ID_GOES_HERE", "API_KEY_GOES_HERE");
	$data = $api->user();
	$stats = $data['stats'];
	print $data['profile']['name'] . " (Level $stats[lvl])\n";
    ... # etc.

## Todo

* Unit tests
* Test all aspects of the code

## Contributors

* Binny V A - Initial Author

## Thanks To

* HabitRPG Team(Obviously)
* pyhabit(Python Library for HabitRPG). The idea for this came after seeing pyhabit.