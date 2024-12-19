<?php
$directory = 'uploads/vision_boards';

// Test if directory exists
if (is_dir($directory)) {
    echo "Directory exists<br>";
} else {
    echo "Directory does not exist<br>";
}

// Test if directory is writable
if (is_writable($directory)) {
    echo "Directory is writable<br>";
} else {
    echo "Directory is NOT writable<br>";
}

// Try to create a test file
$testFile = $directory . '/test.txt';
if (file_put_contents($testFile, 'Test content')) {
    echo "Successfully created test file<br>";
    unlink($testFile); // Delete the test file
    echo "Successfully deleted test file<br>";
} else {
    echo "Could not create test file<br>";
}
?>