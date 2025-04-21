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
?>