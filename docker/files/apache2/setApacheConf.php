<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('APACHE');

if (count($argv) < 2) {
	DockerLogger::error("Apache configuration file required as argument");
} elseif (count($argv) > 2) {
	DockerLogger::error("Too many arguments");
}

$apacheConfFile = $argv[1];

if (is_dir($apacheConfFile)) {
	DockerLogger::error("Path is a directory, expected file: {$apacheConfFile}");
}

if (!file_exists($apacheConfFile)) {
	DockerLogger::error("Apache configuration file not found: {$apacheConfFile}");
}

DockerLogger::info("Configuring Apache with: {$apacheConfFile}");

try {
	//Copy the httpd conf file
	$apacheDir = "/etc/apache2";
	$dockerDir = "/usr/local/aspen-discovery/docker";
	$fileName = basename($apacheConfFile);
	
	if (!copy($apacheConfFile, "$apacheDir/sites-enabled/$fileName")) {
		DockerLogger::error("Failed to copy Apache configuration");
	}
	
	DockerLogger::info("Apache configuration copied successfully");

	// Copy data-alias.conf with sitename replacement
	if (file_exists("$dockerDir/files/apache2/data-alias.conf")) {
		$sitename = getenv('SITE_NAME');
		if (!$sitename) {
			DockerLogger::error("SITE_NAME environment variable is required");
		}

		$dataAliasContent = file_get_contents("$dockerDir/files/apache2/data-alias.conf");
		$dataAliasContent = str_replace('{sitename}', $sitename, $dataAliasContent);

		if (file_put_contents("$apacheDir/conf-enabled/data-alias.conf", $dataAliasContent) === false) {
			DockerLogger::warn("Failed to write data-alias configuration");
		} else {
			DockerLogger::info("Data alias configuration copied with sitename: $sitename");
		}
	}

	// Validate Apache configuration
	$output = [];
	$returnCode = 0;
	exec("apache2 -t 2>&1", $output, $returnCode);
	
	if ($returnCode !== 0) {
		DockerLogger::warn("Apache configuration validation failed:");
		foreach ($output as $line) {
			DockerLogger::warn($line);
		}
		DockerLogger::error("Apache configuration is invalid");
	}
	
	DockerLogger::info("Apache configuration setup completed successfully");

} catch (Exception $e) {
	DockerLogger::error("Apache configuration failed: " . $e->getMessage());
}



?>
