<?php

class UserUpload {

	private $options = [];

	/**
	 * u - MySQL username
	 * p - MySQL password
	 * h - MySQL host
	 *
	 * --file CSV filename being parsed
	 * --create_table MYSQL users table build
	 * --dry_run used with --file run script without db alteration (useful for tests)
	 *
	 */
	__construct() {
		$this->cli_get_params(array("file:", "create_table", "dry_run", "help"), "u:p:h:");
	}

	private function cli_get_params(array longos, string shortos): void {
		$this->options = getopt($longos, $shortos);

		// Since this is in the constructor we can show the help first
		if (isset($this->options['help'])) {
			$this->showHelp();
			exit;
		}
	}


}
