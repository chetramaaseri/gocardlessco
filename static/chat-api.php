<?php 
if(isset($_GET['action']) && $_GET['action'] == 'registerQuery'){
    $postData = [
        'name' => sanitizeText($_POST['name']),
        'email' => sanitizeText($_POST['email']),
        'mobile' => sanitizeText($_POST['mobile']),
        'executive_id' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    $queryId = $db->table('queries')->insertGetId($postData);
    $postData['query_id'] = $queryId;
    $_SESSION['query_Id'] = $queryId;
    $_SESSION['name'] = $postData['name'];
    $_SESSION['email'] = $postData['email'];
    $_SESSION['mobile'] = $postData['mobile'];
    $_SESSION['created_at'] = $postData['created_at'];
    header('Content-Type: application/json');
    echo json_encode($postData);
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'executiveAssigned'){
    $db->table('queries')->where('query_id', $_POST['query_id'])->update(['executive_id' => $_POST['executive_id']]);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => 200,
        "executive_id" => $_POST['executive_id'],
        "query_id" => $_POST['query_id'],
        "message" => "Executive assigned successfully"
    ]);
    exit;
}

if(isset($_GET['action']) && $_GET['action'] == 'saveMessage'){
    $db->table('queries')->where('query_id', $_POST['query_id'])->update(['message' => $_POST['message']]);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => 200,
        "message" => $_POST['message'],
        "query_id" => $_POST['query_id'],
        "message" => "Message Saved successfully"
    ]);
    exit;
}
if(isset($_GET['action']) && $_GET['action'] == 'closeChat'){
    $db->table('queries')->where('query_id', $_POST['query_id'])->update(['status' => 'closed']);
    function splitAndInsertChats($db, $query_id, $chatArray, &$partCounter) {
        $json = json_encode($chatArray);
        if (strlen($json) <= 64000) {
            $db->table('chat_history')->insert([
                'query_id' => $query_id,
                'chats' => $json,
                'part' => $partCounter++
            ]);
        } else {
            $mid = floor(count($chatArray) / 2);
            $firstHalf = array_slice($chatArray, 0, $mid);
            $secondHalf = array_slice($chatArray, $mid);
            splitAndInsertChats($db, $query_id, $firstHalf, $partCounter);
            splitAndInsertChats($db, $query_id, $secondHalf, $partCounter);
        }
    }
    $maxLength = 64000;
    $query_id = $_POST['query_id'];
    $chatArray = json_decode($_POST['chats'], true);
    if (is_array($chatArray)) {
        $partCounter = 1;
        splitAndInsertChats($db, $query_id, $chatArray, $partCounter);
    }
    header('Content-Type: application/json');
    echo json_encode([
        "status" => 200,
        "query_id" => $_POST['query_id'],
        "message" => "Query Closed successfully"
    ]);
    exit;
}
?>