<?php
# Create an instance of the License SDK
$sdk_license = new LMFW\SDK\License( 
 'wpcr',   // The plugin name is used to manage internationalization
 'https://estudarti.com.br', //Replace with the URL of your license server (without the trailing slash)
 'ck_7f328eb092d21706bc51aaef370fb5dddc618bda', //Customer key created in the license server
 'cs_3e8f228b97120e074302577d5f87163b2c421f8d', //Customer secret created in the license server
 [], //Set an array of the products IDs on your license server (if no product validation is needed, send an empty array)
 'plugin_license', //Set a unique value to avoid conflict with other plugins
 'plugin-is-valid',  //Set a unique value to avoid conflict with other plugins
 5 //How many days the valid object will be used before calling the license server
);