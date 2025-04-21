<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ".ADMIN_URL."auth/login"); // Redirect to login if not logged in
    exit();
}
?>
