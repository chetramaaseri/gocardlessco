<!DOCTYPE html>
<html lang="en">
<head>
	<?php require_once 'layout/head.php' ?>
</head>
<body id="#top">
    <?php require_once 'layout/navbar.php' ?>

    <!-- Page Header Start -->
	<div class="page-header parallaxie">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-12">
					<!-- Page Header Box Start -->
					<div class="page-header-box">
						<h1 class="wow fadeInUp" data-cursor="-opaque">Page not found</h1>
						<nav class="wow fadeInUp">
                            <ol class="breadcrumb">
								<li class="breadcrumb-item"><a href="index-2.html">home</a></li>
								<li class="breadcrumb-item active" aria-current="page">404 error page</li>
							</ol>
						</nav>
					</div>
					<!-- Page Header Box End -->
				</div>
			</div>
		</div>
	</div>
	<!-- Page Header End -->

    <!-- error section start -->
    <div class="error-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="error-page-image wow fadeInUp">
                        <img src="images/404-error-img.png" alt="">
                    </div>
                    <div class="error-page-content">
                        <div class="section-title">
                            <h2 class="wow fadeInUp" data-cursor="-opaque">Oops! <span>Page not found</span></h2>
                        </div>
                        <div class="error-page-content-body">
                            <p class="wow fadeInUp" data-wow-delay="0.25s">Sorry, We can't find the page you're looking.</p>
                            <a class="btn-default wow fadeInUp" data-wow-delay="0.5s" href="index-2.html"><span>back to home</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- error section end -->
     
    <?php require_once 'layout/footer.php' ?>
    <?php require_once 'layout/scripts.php' ?>
</body>
</html>