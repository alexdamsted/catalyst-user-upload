<?php


for($i = 1; $i <= 100; $i++){
	if (($i%3 == 0) && ($i%5 == 0)) {
		echo "foobar" . ", ";
		continue;
	} else if ($i%3 == 0) { 
		echo "foo" . ", "; 
		continue;
	} else if ($i%5 == 0) {
		echo "bar" . ", ";
		continue;
	}

	if ($i !== 100) {
		echo $i . ", ";
	} else {
		echo $i . "\n";
	}
}


?>
