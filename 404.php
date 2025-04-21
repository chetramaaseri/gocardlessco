<!doctype html>
<html lang="en">

<head>
    <?php require_once 'layout/head.php'; ?>
</head>

<body class="investment_solution">
    <div class="page_wrapper">
        <?php require_once 'layout/navbar.php' ?>
        <main class="page_content">

            <!-- Error Section - Start
        ================================================== -->
            <section class="error_section text-center section_decoration">
                <div class="container">
                    <div class="error_image">
                        <img src="assets/images/404_error_image.webp" alt="404 Error Image">
                    </div>
                    <h1>Hi 👋 Sorry We Can’t Find That Page!</h1>
                    <p>
                        The page you are looking for was moved, removed, renamed or never existed.
                    </p>
                    <div class="form-group">
                        <input class="form-control" type="search" name="search" placeholder="Search your keyword">
                        <button type="submit" class="icon_block">
                            <img src="assets/images/icons/icon_search_2.svg" alt="Icon Search">
                        </button>
                    </div>
                    <div class="btns_group pb-0">
                        <a class="btn bg-dark" href="#">
                            <span class="btn_label">Take Me Home</span>
                            <span class="btn_icon"><svg width="20" height="16" viewBox="0 0 20 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M19.7071 8.70711C20.0976 8.31658 20.0976 7.68342 19.7071 7.29289L13.3431 0.928932C12.9526 0.538408 12.3195 0.538408 11.9289 0.928932C11.5384 1.31946 11.5384 1.95262 11.9289 2.34315L17.5858 8L11.9289 13.6569C11.5384 14.0474 11.5384 14.6805 11.9289 15.0711C12.3195 15.4616 12.9526 15.4616 13.3431 15.0711L19.7071 8.70711ZM0 9H19V7H0V9Z"
                                        fill="white" />
                                </svg></span>
                        </a>
                    </div>
                </div>
                <div class="decoration_item shape_dollar_1 wow fadeInUp" data-wow-delay=".1s">
                    <img src="assets/images/shapes/shape_dollar_1.webp" alt="Shape Dollar">
                </div>
                <div class="decoration_item shape_dollar_2 wow fadeInUp" data-wow-delay=".1s">
                    <img src="assets/images/shapes/shape_dollar_2.webp" alt="Shape Dollar">
                </div>
                <div class="decoration_item shape_dollar_3 wow fadeInUp" data-wow-delay=".2s">
                    <img src="assets/images/shapes/shape_dollar_3.webp" alt="Shape Dollar">
                </div>
                <div class="decoration_item shape_dollar_4 wow fadeInUp" data-wow-delay=".2s">
                    <img src="assets/images/shapes/shape_dollar_4.webp" alt="Shape Dollar">
                </div>
            </section>
            <!-- Error Section - End
        ================================================== -->

        </main>
        <?php require_once 'layout/chatbox.php'; ?>
        <?php require_once 'layout/footer.php'; ?>
    </div>
    <?php require_once 'layout/scripts.php'; ?>
</body>

</html>