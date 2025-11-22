<?php
// test_chatbot.php
$url = 'http://localhost/Ibdaa-Taiz/platform/api/ai_chatbot.php';
$data = array('action' => 'chat', 'message' => 'hello', 'session_id' => 'test_session');

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "--- RAW RESPONSE START ---\n";
echo $result;
echo "\n--- RAW RESPONSE END ---\n";
?>