<?php
$botToken = "7442196956:AAH7Y_POXcas9D8LMkO-SCFSNa6ZVyc1tNg";
$apiURL = "https://api.telegram.org/bot$botToken/";

require 'vendor/autoload.php';
define('MONGO_URI', 'mongodb+srv://biniroy852:ncDkdEgo2x8BTY9b@cluster0.6tmnl.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
$mongoClient = new MongoDB\Client(MONGO_URI);
$collection = $mongoClient->telegram->users;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? null;
$user_id = $update["message"]["from"]["id"] ?? null;

if (isset($update["message"])) {
    if ($text === "/start") {
        sendMessage($chat_id, "Welcome!\nUse /setsource <channel_id>\nUse /setdestination <channel_id>");
    }
    elseif (strpos($text, "/setsource") === 0) {
        $parts = explode(" ", $text);
        if (isset($parts[1])) {
            $source = $parts[1];
            $collection->updateOne(
                ["user_id" => $user_id],
                ['$set' => ["source" => $source]],
                ['upsert' => true]
            );
            sendMessage($chat_id, "✅ Source channel set to: `$source`", true);
        } else {
            sendMessage($chat_id, "Usage: /setsource -100xxxxxxxxxx");
        }
    }
    elseif (strpos($text, "/setdestination") === 0) {
        $parts = explode(" ", $text);
        if (isset($parts[1])) {
            $dest = $parts[1];
            $collection->updateOne(
                ["user_id" => $user_id],
                ['$set' => ["destination" => $dest]],
                ['upsert' => true]
            );
            sendMessage($chat_id, "✅ Destination channel set to: `$dest`", true);
        } else {
            sendMessage($chat_id, "Usage: /setdestination -100xxxxxxxxxx");
        }
    }
}

if (isset($update["message"]["forward_from_chat"])) {
    $forward_from_chat_id = $update["message"]["forward_from_chat"]["id"];
    $message_id = $update["message"]["message_id"];
    $users = $collection->find();

    foreach ($users as $user) {
        if (isset($user['source']) && isset($user['destination'])) {
            if ($user['source'] == $forward_from_chat_id) {
                file_get_contents($apiURL . "forwardMessage?chat_id=" . $user['destination'] . "&from_chat_id=" . $chat_id . "&message_id=" . $message_id);
            }
        }
    }
}

function sendMessage($chat_id, $text, $markdown = false) {
    global $apiURL;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    if ($markdown) {
        $params['parse_mode'] = 'Markdown';
    }
    file_get_contents($apiURL . "sendMessage?" . http_build_query($params));
}
?>
