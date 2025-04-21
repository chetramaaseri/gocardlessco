<?php
session_start();
$sess_file = session_save_path() . "/sess_" . session_id();
$_SESSION = [];
session_destroy();
if (file_exists($sess_file)) {
    unlink($sess_file);
}
header("Location: ".ADMIN_URL."auth/login");
exit();
?>
