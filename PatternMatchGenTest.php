<?php
declare(strict_types=1);
declare(ticks = 1);

require_once('PatternMatchGen.php');
use \PatternMatchGen as PatternMatchGen;

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
	if ($c !== count($ranges)) {
		fprintf(STDERR, "Failed assertion: %d === %d\n", $c, count($ranges));
	}
	for ($i = 0; $i < $c; $i++) {
		if ($extensions[$i] !== $ranges[$i]) {
			/* assert no longer seems to work in later versions of PHP 8,
			 * and assertion support is now deprecated/being removed in PHP. */
			fprintf(STDERR, "Failed assertion: %s === %s\n", $extensions[$i], $ranges[$i]);
			exit(-1);
		}
	}
}

/* begin tests */
validatePatternMatches(getPatternMatches(134, 142), array('_13[4-9]', '_14[0-2]'));
validatePatternMatches(getPatternMatches(234, 742), array('_23[4-9]', '_2[4-9]X', '_[3-6]XX', '_7[0-3]X', '_74[0-2]'));
validatePatternMatches(getPatternMatches(636, 6734), array('_63[6-9]', '_6[4-9]X', '_[8-9]XX', '_[1-5]XXX', '_6[0-6]XX', '_67[0-2]X', '_673[0-4]'));
/* end tests */

exit(0);
?>