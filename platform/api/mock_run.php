<?php
// mock_chatbot_run.php
$_SERVER['REQUEST_METHOD'] = 'POST';
// Mocking php://input is hard in CLI without a wrapper, so I'll modify ai_chatbot.php temporarily or just use the previous test_chatbot.php if I can assume XAMPP is up.

// Let's try to just include ai_chatbot.php and set $_POST (though the script reads JSON body)
// The script likely does $input = json_decode(file_get_contents('php://input'), true);

// I will create a wrapper that defines a stream wrapper for php://input or just modifies the script to read from a variable.
// Actually, simpler: I'll use the `run_in_terminal` to run the test_chatbot.php I just created. If XAMPP is up, it should work.
?>