// This script loads the site-test-config.json file and injects the variables into the Selenium IDE test
var fs = require('fs');
var path = require('path');

// Load the configuration file
var configPath = path.join(__dirname, 'site-test-config.json');
var config;

try {
  var configData = fs.readFileSync(configPath, 'utf8');
  config = JSON.parse(configData);
  console.log('Configuration loaded successfully');
} catch (error) {
  console.error('Error loading configuration file:', error.message);
  console.error('Please make sure site-test-config.json exists in the tests/e2e directory');
  console.error('You can copy site-test-config.json.example to site-test-config.json and update the values');
  process.exit(1);
}

// Export the configuration variables for use in Selenium IDE
module.exports = {
  url: config.url,
  username: config.credentials.username,
  password: config.credentials.password,
  invalidPassword: config.credentials.invalidPassword
};
