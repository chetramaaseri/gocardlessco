<?php 
$client = new Google_Client();
$client->setClientId('1097019561604-iec12v7m7uo336mend96ssh4f0t5lr24.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri(ADMIN_URL."auth/google-auth");
$client->addScope('email');
$client->addScope('profile');
$authUrl = $client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
        <style>
            body {
                background-image : url('<?=ADMIN_ASSET?>assets/images/login-bg.avif');
                background-repeat : no-repeat;
                background-position : center center; 
                position: relative;
            }
            .mask {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: -1;
                background-color : rgba(0,0,0,0.6)
            }
            .google-login-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                background: white;
                color: #757575;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
                width: 100%;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.3s;
                margin-bottom: 15px;
                text-decoration: none;
            }
            
            .google-login-btn:hover {
                background: #f8f8f8;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .google-login-btn img {
                width: 18px;
                height: 18px;
                margin-right: 10px;
            }
        </style>
    </head>
<?php 
if (isset($_SESSION['user_id'])) {
    header('Location: '.ADMIN_URL);
    exit();
}
if(isset($_POST['email']) && isset($_POST['password']) && $_POST['csrf_token']){
    try {
        // Reset the CSRF token
        if($_POST['csrf_token'] !== $_SESSION['csrf_token']){
            throw new Exception("Request Forbidden!, Try Again", 403);
        }
        // Reset the CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Enter a Valid Email", 401);
        }
        $password = $_POST['password'];
        if(isset($_POST['rememberMe'])){
            setcookie('rememberMe', 'true', time() + (86400 * 30), "/");
            setcookie('email', $email, time() + (86400 * 30), "/");
            setcookie('password', $password, time() + (86400 * 30), "/");
        }
        $user = (array) $db->table('users')
                ->where('email', $email)
                ->first();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            foreach ($user as $key => $value) {
                $_SESSION[$key] = $value;
            }
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_last_login'] = date('Y-m-d H:i:s');
            header('Location: '.ADMIN_URL);
            exit;
        } else {
            throw new Exception("Enter a Valid Email or Password", 401);
        }
    } catch (\Throwable $th) {
        $_SESSION['alertAsync'] = $th->getMessage();
        header("Refresh:0");
        exit;
    }
}
?>
<body>
    <div class="mask"></div>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-3">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <img width="220" class="mx-auto mt-3" style="filter:invert(1)" src="<?=ADMIN_ASSET?>assets/images/pero-cms.png" alt="" srcset="">
                    <div class="card-body pt-2">
                        <a href="<?=$authUrl?>" class="google-login-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" class="me-2">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Continue with Google
                        </a>

                        <div class="d-flex align-items-center mb-2">
                            <hr class="flex-grow-1">
                            <span class="px-2">OR</span>
                            <hr class="flex-grow-1">
                        </div>

                        <form method="POST">
                            <input hidden name="csrf_token" value="<?=$_SESSION['csrf_token']?>">
                            <div class="mb-2">
                                <label for="email" class="form-label fw-semibold mb-1" >Email address</label>
                                <input type="text" class="form-control" id="email" name="email" value="<?=isset($_COOKIE['email'])? $_COOKIE['email'] :''?>" required>
                            </div>
                            <div class="mb-2">
                                <label for="inputPassword" class="form-label fw-semibold mb-1">Password</label>
                                <input type="password" class="form-control" id="inputPassword" name="password" value="<?=isset($_COOKIE['password'])? $_COOKIE['password'] :''?>"  required>
                            </div>
                            <div class="form-check mb-3">
                                <input <?=isset($_COOKIE['rememberMe'])?'checked':''?> name="rememberMe" value="1" class="form-check-input" id="inputRememberPassword" type="checkbox" value="">
                                <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                            </div>
                            <button type="submit" name="proceed" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
</body>

</html>
