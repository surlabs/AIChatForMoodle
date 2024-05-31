<?php

require_once('../../config.php');
require_once('lib.php');
require_login();

header('Content-Type: application/json');

$cmd = required_param('cmd', PARAM_RAW_TRIMMED);
$userId = $USER->id;



switch ($cmd) {
    case 'receiveMessages':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $messages = required_param('messages', PARAM_RAW);
            $aichat_id = required_param('id', PARAM_INT);

            $messages = json_decode($messages, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                sendResponseJson(['error' => 'Invalid JSON in messages'], 400);
                return;
            }

            if (!isset($messages['messages']) || !is_array($messages['messages'])) {
                sendResponseJson(['error' => 'Invalid data, array expected'], 400);
                return;
            }

            $request = (object) ['messages' => $messages['messages']];
            sendToApi($request, $userId, $aichat_id);
        } else {
            sendResponseJson(['error' => 'Invalid request method'], 405);
        }
        break;

    case 'getChatMessages':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aichat_id = required_param('id', PARAM_INT);
            $messages = getChatMessages($aichat_id, $userId);
            sendResponseJson(['messages' => $messages]);
        } else {
            sendResponseJson(['error' => 'Invalid request method'], 405);
        }
        break;
		
    case 'removeChatMessages':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $aichat_id = required_param('id', PARAM_INT);
            removeChatMessages($aichat_id, $userId);
            $messages = getChatMessages($aichat_id, $userId);
            sendResponseJson(['messages' => $messages]);
        } else {
            sendResponseJson(['error' => 'Invalid request method'], 405);
        }
        break;
    default:
        sendResponseJson(['error' => 'Unknown command'], 400);
}

function sendToApi($request, $userId, $aichat_id) {
    $messages = $request->messages;
    $apiKey = get_config('mod_aichat', 'apikey');
    $model = get_config('mod_aichat', 'model');

    $openaiUrl = 'https://api.openai.com/v1/chat/completions';

    if (!is_array($messages)) {
        sendResponseJson(['error' => 'Invalid data, array expected'], 400);
        return;
    }

    // Create the request body
    $payload = json_encode([
        'messages' => $messages,
        'model' => $model,
        'temperature' => 0.5
    ]);

    // Initialize cURL session
    $curlSession = curl_init();

    // Set cURL options
    curl_setopt($curlSession, CURLOPT_URL, $openaiUrl);
    curl_setopt($curlSession, CURLOPT_POST, true);
    curl_setopt($curlSession, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);

    // Execute cURL and get the response
    $response = curl_exec($curlSession);
    $httpcode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
    $errNo = curl_errno($curlSession);
    $errMsg = curl_error($curlSession);
    curl_close($curlSession);

    // Handle OpenAI API errors
    if ($errNo) {
        sendResponseJson(['error' => 'OpenAI HTTP Error: ' . $errMsg], $httpcode);
        return;
    }

    if ($httpcode != 200) {
        if ($httpcode === 401) {
            sendResponseJson(['error' => 'Invalid API Key'], $httpcode);
        } else {
            sendResponseJson(['error' => 'OpenAI HTTP Error'], $httpcode);
        }
        return;
    }

    $decodedResponse = json_decode($response, true);

    $messages[] = [
        'role' => 'assistant',
        'content' => $decodedResponse['choices'][0]['message']['content'] ?? ''
    ];

    aichat_add_message($aichat_id, $userId, $messages);
    
    sendResponseJson(['messages' => $messages]);
}

function getChatMessages($aichat_id, $userId) {
    global $DB;

    $record = $DB->get_record('aichat_messages', array('aichat_id' => $aichat_id, 'userid' => $userId));
    if ($record) {
        $messages = json_decode($record->messages, true);
        if (empty($messages)) {
            return [generate_system_prompt()];
        }
        return $messages;
    }

    return [generate_system_prompt()];
}
function get_full_language($lang) {
    $languages = [
        'en' => 'English',
        'es' => 'Spanish',
    ];

    return $languages[$lang] ?? 'English';
}

function sendResponseJson($data, $statusCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
    exit();
}
