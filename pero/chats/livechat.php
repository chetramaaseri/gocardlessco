<?php
require ADMIN_FILES.'auth/authMiddleware.php';
$allChats = $db->table('queries')->where('executive_id', $_SESSION['user_id'])->where('status', '!=', 'closed')->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
</head>
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4"></div>
            </main>
            <?php require_once ADMIN_FILES.'layout/footer.php' ?>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script>
        const allChats = <?=$allChats?>;
        console.log('all chats', allChats);
        
        window.socket = io(`<?=$_ENV['CHAT_SERVER_URL']?>`);
        window.socket.on("connect", () => {
            console.log("Connected to the server");
        });
        window.socket.emit('executive_registered',{
            user_id: "<?=$_SESSION['user_id']?>",
            name: "<?=$_SESSION['name']?>",
            email: "<?=$_SESSION['email']?>",
            preference: "<?=$_SESSION['chat_preference']?>",
            capacity: <?=$_SESSION['max_chats']?>,
            totalAssigned: allChats.length,
            assignedQueries: allChats.map(chat=>chat.query_id),
        });
        window.socket.on('new_query', (data) => {
            console.log(data);
        })
    </script>
    
</body>
</html>