<?php

function logMessage($message) {
  // Failure to write to the log shouldn't break everything, but we
  // should mention it in STDOUT.

  // First we have to create the file if it doesn't exist.
  try {
    if(!file_exists(LOG_FILE)) {
      $fh = fopen(LOG_FILE, 'w');
      rewind($fh);
      fclose($fh);
    }
  } catch (Exception $e) {
    echo "\nError creating to log file:. $e\n";
    return false;
  }

  // Get the current contents of the log file.
  try {
    $contents = file_get_contents(LOG_FILE);
  } catch (Exception $e) {
    echo "\nError reading to log file:. $e\n";
    $contents = "";
  }

  // Write the log message.
  try {
    $now = date("Y-m-d H:i:s");
    $contents = $contents . "[$now]: $message\n";
    file_put_contents(LOG_FILE, $contents);
    return true;
  } catch (Exception $e) {
    echo "\nError writing to log file:. $e\n";
    return false;
  }
}

