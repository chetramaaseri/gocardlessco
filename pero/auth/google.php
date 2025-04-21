<?php
$client = new Google_Client();
$client->setClientId('1097019561604-iec12v7m7uo336mend96ssh4f0t5lr24.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri(ADMIN_URL."auth/google-auth");
$client->addScope('email');
$client->addScope('profile');
if (isset($_GET['code'])) {
    try {
        $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($accessToken);
        $oauth2Service = new Google_Service_Oauth2($client);
        $userInfo = $oauth2Service->userinfo->get();
        $userEmail = $userInfo->email;
        $userName = $userInfo->name;
        $user = (array) $db->table('users')->where('email', $userEmail)->first();
        if ($user) {
            session_regenerate_id(true);
            foreach ($user as $key => $value) {
                $_SESSION[$key] = $value;
            }
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_last_login'] = date('Y-m-d H:i:s');
            header('Location: /admin/dashboard');
            exit;
        }else{
            throw new Exception("User Not Found", 401);
        }

    } catch (Exception $th) {
        $_SESSION['alertAsync'] = $th->getMessage();
    }
}

header('Location: '.ADMIN_URL);
exit;

?>