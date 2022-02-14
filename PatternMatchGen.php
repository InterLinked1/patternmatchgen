<?php

/*
PatternMatchGen - automatic Asterisk dialplan pattern match generator
(C) 2021 PhreakNet.

This is a simple program that can be used to automatically generate dialplan pattern matches for use in the Asterisk dialplan. It does the opposite of what Asterisk does: instead of using a pattern match to represent a collection of extensions, it converts a collection of extensions into the corresponding pattern match representation(s).

The basic premise of this program is actually very simple. Given a starting number and an ending number, generate dialplan pattern matches for this number range, ideally using the fewest possible number of pattern matches, *without encompassing any numbers that do not fall in the provided range*. It's not really that difficult, it just involves a lot of math.

e.g. given 234, 742:
exten => _23[4-9]
exten => _2[4-9]XX
exten => _[3-6]XX
exten => _7[0-3]X
exten => _74[0-2]

This is what we will call the minimally encompassing set of pattern matches. The goal of this program is to generate this information.

For multiple ranges (disjoint number ranges), call the function multiple times.
Typically, what you'll want to do is retrieve a window range using SQL and then for each range that you get, call this function.

Usage:

Parameters:
$ptr = pointer to array to store result.
$min = starting number
$max = ending number

Return Value:
The constructor function does not explicitly return anything, but it fills an array containing key value pairs corresponding to the starting number and the quantity of additional numbers in that range (e.g. 0 for just that number, 1 for two consecutive numbers, etc.). This is sufficient to then generate pattern matches using some trivial logic.

Example:
$min = 234;
$max = 742;
$numberGroups = array();
$ptr = &$numberGroups;
new \PatternMatchGen($ptr, $min, $max);
foreach ($numberGroups as $start => $qty) {
	$ext = \PatternMatchGen::matchCallback($start, $qty);
	$ext .= ",1,Return(somethingcool)\n";
	echo $ext; # print the full extenpattern,priority,app that we just generated.
}

Result:
exten => _23[4-9],1,Return(somethingcool)
exten => _2[4-9]XX,1,Return(somethingcool)
exten => _[3-6]XX,1,Return(somethingcool)
exten => _7[0-3]X,1,Return(somethingcool)
exten => _74[0-2],1,Return(somethingcool)

Demo Usage from the command line:
php PatternMatchGen.php <min> <max>

*/

/* Demo usage */
if (php_sapi_name() == 'cli') { # https://stackoverflow.com/a/9765564
	if (isset($_SERVER['TERM']) && isset($argc)) {
		/* TERM is set on class invocations in a program, but $argc is only set on direct CLI invocation */
		if ($argc === 3) {
			$min = (int) $argv[1];
			$max = (int) $argv[2];
			$numberGroups = array();
			$ptr = &$numberGroups;
			new \PatternMatchGen($ptr, $min, $max);
			foreach ($numberGroups as $start => $qty) {
				echo \PatternMatchGen::matchCallback($start, $qty) . PHP_EOL;
			}
		} else {
			echo "Usage: php PatternMatchGen.php <min> <max>" . PHP_EOL;
		}
	}
}

class PatternMatchGen {

	public static function matchCallback($start, int $qty) {
		if ($qty === 0) {
			return 'exten => ' . $start;
		}
		$la = strlen($start);
		$lb = strlen($qty);
		$f = (int) substr($qty, 0, 1); # only the first digit and string length are needed. Any additional digits are 9's.
		$fixedDigits = $la - $lb;
		$pDigit = (int) substr($start, $fixedDigits, 1);
		$ext = '';
		$ext .= 'exten => _' . substr($start, 0, $fixedDigits);
		if ($f === 9 && $pDigit === 0) {
			$ext .= 'X';
		} else if ($f === 8 && $pDigit === 1) {
			$ext .= 'Z';
		} else if ($f === 7 && $pDigit === 2) {
			$ext .= 'N';
		} else {
			$ext .= '[' . $pDigit . '-' . ($pDigit + $f) . ']';
		}
		$ext .= str_repeat('X', $lb - 1);
		return $ext;
	}

	private static function asn(&$ptr, $a, $b) {
		if ($b < 0)
			return;
		$ptr[$a] = $b;
	}
	public function __construct(&$ptr, $start, $end) {
		/*
		$rec = 0
		$amt = maximum depth (10 million should be high enough - 10M is more than a whole NPA (area code). No way in heck should we be pattern matching more than this many digits!)
		*/
		static::generate($ptr, 0, 10000000, $start, $end);
	}
	private static function generate(&$ptr, $rec, $amt, $start, $end) { # recursive function
		$rec++;
		if ($amt <= 10) {
			if ($end < $start)
				return;
			$d = ceil( $start / 10 ) * 10;
			if ($d <= $end) {
				static::asn($ptr, $start, $d - $start - 1); # until next 10 (e.g. 23->29)
				$diff = $end - $d;
				if ($diff >= $amt * 2 && (($diff + 1) % $amt > 0)) { # opportunity for something like [0-2]X,31,32
					$groups = floor($diff / $amt);
					$top = $groups * $amt + $d;
					# middle
					static::asn($ptr, $d, ($groups * $amt) - 1);
					# after
					if ($end > $d + $amt) {
						static::generate($ptr, $rec, $amt, $top, $end);
					}
				} else {
					static::asn($ptr, $d, $end - $d);
				}
			} else {
				static::asn($ptr, $start, $end - $start);
			}
		} else {
			$cap = round($start + ($amt / 2) - 1, -(strlen($amt) - 1)); # e.g. should be -2 for up to nearest 100
			# before
			if ($cap > $start) {
				static::generate($ptr, $rec, $amt / 10, $start, min($end, $cap - 1));
			}
			if ($end >= $cap + $amt) {
				if (($end + 1) % $amt === 0 && ($end - ($cap + $amt) + 1) % $amt === 0) {
					static::asn($ptr, $cap + $amt, $end - ($cap + $amt)); # someting like [3-9]X
				} else {
					$diff = $end - $cap;
					if ($diff >= $amt * 2) { # opportunity for something like [0-4]X
						$groups = floor($diff / $amt);
						$top = $groups * $amt + $cap;
						# middle
						static::asn($ptr, $cap, ($groups * $amt) - 1);
						# after
						if ($end > $cap + $amt) {
							static::generate($ptr, $rec, $amt, $top, $end);
						}
					} else {
						# middle
						static::asn($ptr, $cap, $amt - 1);
						# after
						if ($end > $cap + $amt) {
							static::generate($ptr, $rec, $amt, $cap + $amt, $end);
						}
					}
				}
			} else {
				# after
				static::generate($ptr, $rec, $amt / 10, $cap, $end);
			}
		}
	}
}
?>