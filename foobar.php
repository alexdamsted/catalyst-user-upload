<?php


for($i = 1; $i <= 100; $i++){
	if (($i%3 == 0) && ($i%5 == 0)) {
		if ($i !== 100) {
			echo "foobar" . ", ";
			continue;
		} else {
			echo "foobar" . "\n";
			continue;
		}
	} else if ($i%3 == 0) { 
		if ($i !== 100) {
			echo "foo" . ", "; 
			continue;
		} else {
			echo "foo" . "\n"; 
			continue;
		}
	} else if ($i%5 == 0) {
		if ($i !== 100) {
			echo "bar" . ", ";
			continue;
		} else {
			echo "bar" . "\n";
			continue;
		}
	}

	if ($i !== 100) {
		echo $i . ", ";
	} else {
		echo $i . "\n";
	}
}


?>
