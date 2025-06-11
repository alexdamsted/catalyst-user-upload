<?php


for($i = 1; $i <= 100; $i++){
	if ($i%3 == 0) { 
		echo "foo" . ", "; 
		continue;
	}

		if ($i !== 100) {
			echo $i . ", ";
		} else {	
			echo $i . "\n";
		}
}


?>
