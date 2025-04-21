<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Admin Options</div>
                <a class="nav-link <?=trim(getCurrentUrl(),"/") == trim(ADMIN_URL,"/") ? 'active' : '' ?>" href="<?=ADMIN_URL?>">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-bars"></i></div>
                    Dashboard
                </a>
                <a class="nav-link <?=trim(getCurrentUrl(),"/") == trim(ADMIN_URL.'users',"/") ? 'active' : '' ?>" href="<?=ADMIN_URL?>users">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-layer-group"></i></div>
                    Users
                </a>
                <a class="nav-link <?=trim(getCurrentUrl(),"/") == trim(ADMIN_URL.'chats/chatwindow',"/") ? 'active' : '' ?>" href="<?=ADMIN_URL?>chats/chatwindow">
                    <div class="sb-nav-link-icon"><i class="fa-brands fa-envira"></i></div>
                    Chat Window
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?=ucwords($_SESSION['name'])?>
        </div>
    </nav>
</div>