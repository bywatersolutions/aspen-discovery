// This script loads the site-specific test config files and injects the variables into the Selenium IDE test
var fs = require('fs');
var path = require('path');

// Function to process a single site
function processSite(siteName) {
  console.log('Processing site:', siteName);

  // Path to the site-specific config file
  var configPath = path.join(__dirname, 'selenium-ide', 'sites', siteName, siteName + '-test-config.json');

  // Load the configuration file
  var config;
  try {
    var configData = fs.readFileSync(configPath, 'utf8');
    config = JSON.parse(configData);
    console.log('Configuration loaded successfully from:', configPath);
  } catch (error) {
    console.error('Error loading configuration file for site ' + siteName + ':', error.message);
    console.error('Please make sure ' + siteName + '-test-config.json exists in the tests/e2e/selenium-ide/sites/' + siteName + ' directory');
    return null;
  }

  // Create the site directory if it doesn't exist
  var siteDir = path.join(__dirname, 'selenium-ide', 'sites', siteName);

  try {
    if (!fs.existsSync(siteDir)) {
      fs.mkdirSync(siteDir, { recursive: true });
      console.log('Created site directory:', siteDir);
    }
  } catch (error) {
    console.error('Error creating site directory:', error.message);
    return null;
  }

  // Path to the Selenium IDE test file
  var sideFilePath = path.join(__dirname, 'selenium-ide', 'AspenDiscovery.side');
  var processedFilePath = path.join(siteDir, 'AspenDiscovery.processed.side');

  try {
    // Read the Selenium IDE test file
    var sideFileContent = fs.readFileSync(sideFilePath, 'utf8');

    // Replace variables with values from config
    var processedContent = sideFileContent
      .replace(/\${url}/g, config.url)
      .replace(/\${username}/g, config.credentials.username)
      .replace(/\${password}/g, config.credentials.password)
      .replace(/\${invalidPassword}/g, config.credentials.invalidPassword);

    // Write the processed content to the site-specific directory
    fs.writeFileSync(processedFilePath, processedContent, 'utf8');
    console.log('Processed Selenium IDE test file created successfully at:', processedFilePath);

    return config;
  } catch (error) {
    console.error('Error processing Selenium IDE test file for site ' + siteName + ':', error.message);
    return null;
  }
}

// Get all site directories
var sitesDir = path.join(__dirname, 'selenium-ide', 'sites');
var processedSites = [];

// Process all sites
if (fs.existsSync(sitesDir)) {
  var siteDirs = fs.readdirSync(sitesDir, { withFileTypes: true })
    .filter(function(dirent) { return dirent.isDirectory(); })
    .map(function(dirent) { return dirent.name; });

  for (var i = 0; i < siteDirs.length; i++) {
    var siteName = siteDirs[i];
    var config = processSite(siteName);
    if (config) {
      processedSites.push(siteName);
    }
  }
}

if (processedSites.length === 0) {
  console.error('No site-specific config files were processed. Please make sure at least one config file exists.');
  process.exit(1);
}

console.log('Successfully processed ' + processedSites.length + ' site(s):', processedSites.join(', '));
console.log('Please open the processed file in Selenium IDE instead of the original file.');

// Export the configuration variables for use in Selenium IDE
var defaultSite = processedSites[0];
var defaultConfigPath = path.join(__dirname, 'selenium-ide', 'sites', defaultSite, defaultSite + '-test-config.json');

try {
  var defaultConfigData = fs.readFileSync(defaultConfigPath, 'utf8');
  var defaultConfig = JSON.parse(defaultConfigData);

  module.exports = {
    url: defaultConfig.url,
    username: defaultConfig.credentials.username,
    password: defaultConfig.credentials.password,
    invalidPassword: defaultConfig.credentials.invalidPassword
  };
} catch (error) {
  console.error('Error loading default configuration for export:', error.message);
  process.exit(1);
}
