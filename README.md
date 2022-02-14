# PatternMatchGen

**Asterisk dialplan pattern match generator**

This is a simple program that can be used to automatically generate dialplan pattern matches for use in the Asterisk dialplan. It does the opposite of what Asterisk does: instead of using a pattern match to represent a collection of extensions, it converts a collection of extensions into the corresponding pattern match representation(s).

The basic premise of this program is actually very simple. Given a starting number and an ending number, generate dialplan pattern matches for this number range, ideally using the fewest possible number of pattern matches, *without encompassing any numbers that do not fall in the provided range*. It's not really that difficult, it just involves a lot of math.

e.g. given 234, 742:
```
exten => _23[4-9]
exten => _2[4-9]XX
exten => _[3-6]XX
exten => _7[0-3]X
exten => _74[0-2]
```

This is what we will call the minimally encompassing set of pattern matches. The goal of this program is to generate this information.

For multiple ranges (disjoint number ranges), call the function multiple times.
Typically, what you'll want to do is retrieve a window range using SQL and then for each range that you get, call this function.

## Usage

### Parameters
```
$ptr = pointer to array to store result.
$min = starting number
$max = ending number
```

#### Return Value
The constructor function does not explicitly return anything, but it fills an array containing key value pairs corresponding to the starting number and the quantity of additional numbers in that range (e.g. 0 for just that number, 1 for two consecutive numbers, etc.). This is sufficient to then generate pattern matches using some trivial logic.

## Example
```
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
```

### Result
```
exten => _23[4-9],1,Return(somethingcool)
exten => _2[4-9]XX,1,Return(somethingcool)
exten => _[3-6]XX,1,Return(somethingcool)
exten => _7[0-3]X,1,Return(somethingcool)
exten => _74[0-2],1,Return(somethingcool)
```

### Demo Usage from the command line:
``php PatternMatchGen.php <min> <max>``
