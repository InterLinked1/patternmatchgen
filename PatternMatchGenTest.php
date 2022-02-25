<?php
declare(strict_types=1);
declare(ticks = 1);

require_once('PatternMatchGen.php');
use \PatternMatchGen as PatternMatchGen;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
#ini_set('zend.assertions', 1); // execute assertions - must be set in cli php.ini
assert_options(ASSERT_ACTIVE, TRUE);
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_CALLBACK, 'assert_handler');
function assert_handler() {
	echo "Assertion failed, aborting" . PHP_EOL;
	exit(-1);
}

function getPatternMatches(int $min, int $max) : array {
	$extensions = array();
	$numberGroups = array();
	$ptr = &$numberGroups;
	new PatternMatchGen($ptr, $min, $max);
	foreach ($numberGroups as $start => $qty) {
		$ext = PatternMatchGen::matchCallback($start, (int) $qty);
		$extensions[] = ltrim(explode('>', $ext)[1]);
	}
	return $extensions;
}
function validatePatternMatches(array $extensions, array $ranges) {
	$c = count($extensions);
	assert($c === count($ranges));
	for ($i = 0; $i < $c; $i++) {
		echo "Comparing " . $extensions[$i] . " with " . $ranges[$i] . PHP_EOL;
		assert($extensions[$i] === $ranges[$i]);
	}
}

assert(true);

/* begin tests */
validatePatternMatches(getPatternMatches(134, 142), array('_13[4-9]', '_14[0-2]'));
validatePatternMatches(getPatternMatches(234, 742), array('_23[4-9]', '_2[4-9]X', '_[3-6]XX', '_7[0-3]X', '_74[0-2]'));
validatePatternMatches(getPatternMatches(636, 6734), array('_63[6-9]', '_6[4-9]X', '_[8-9]XX', '_[1-5]XXX', '_6[0-6]XX', '_67[0-2]X', '_673[0-4]'));
/* end tests */

exit(0);
?>