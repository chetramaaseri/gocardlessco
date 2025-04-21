<footer class="site_footer footer_layout_1 bg-secondary section_decoration"
    style="background-image: url('assets/images/backgrounds/footer_bg_1.webp');">
    <div class="container">
        <div class="footer_newsletter pb-0">
            <div class="row align-items-center justify-content-lg-between">
                <div class="col-lg-6">
                    <div class="heading_block mb-0 text-white">
                        <h2 class="heading_text mb-0">
                            Subscribe for daily update
                        </h2>
                    </div>
                </div>
                <div class="col-lg-4">
                    <form class="newsletter_input" action="#">
                        <label for="footer_newsletter_input">
                            <img src="assets/images/icons/icon_email.svg" alt="Icon Email">
                        </label>
                        <input id="footer_newsletter_input" type="email" name="email" placeholder="Email address">
                        <button class="submit_btn" type="submit">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer_content_wrapper">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 p-5">
                    <div class="site_logo">
                        <a class="site_link" href="#">
                            <img src="assets/images/site_logo/cardlogo.webp" alt="Site Logo">
                        </a>
                    </div>
                    <ul class="iconlist_block unordered_list_block">
                        <li>
                            <a href="tel:+442045797398">
                                <span class="iconlist_icon">
                                    <img src="assets/images/icons/icon_calling_2.svg" alt="Icon Calling">
                                </span>
                                <span class="iconlist_text">+44 20 4579 7398</span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:sukhnarang@gmail.com">
                                <span class="iconlist_icon">
                                    <img src="assets/images/icons/icon_email_2.svg" alt="Icon Email">
                                </span>
                                <span class="iconlist_text">sukhnarang@gmail.com</span>
                            </a>
                        </li>
                    </ul>
                    <p class="mb-0 pe-lg-5">
                        GoCardlessCo Ltd, Sutton Yard, 65 Goswell Road, London, EC1V 7EN, United Kingdom
                    </p>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 pe-lg-5">
                    <h3 class="footer_info_title">Quick LInk</h3>
                    <ul class="iconlist_block unordered_list_block">
                        <li>
                            <a href="<?=BASE_URL?>privacy-policy">
                                <span class="iconlist_text">Privacy Policy </span>
                            </a>
                        </li>
                        <li>
                            <a href="<?=BASE_URL?>legal">
                                <span class="iconlist_text">Legal</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?=BASE_URL?>terms-and-conditions">
                                <span class="iconlist_text">Terms & Conditions</span>
                            </a>
                        </li>

                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 pe-lg-5">
                    <h3 class="footer_info_title">Information</h3>
                    <ul class="iconlist_block unordered_list_block">
                        <li>
                            <a href="<?=BASE_URL?>about">
                                <span class="iconlist_text">About us</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer_bottom">
            <div class="row">
                <div class="col-md-6">
                    <p class="copyright_text mb-0 text-white">
                        Copyright © <?=date('Y')?> Gocardless, All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="decoration_item shape_nate">
        <img src="assets/images/shapes/shape_nate_2.svg" alt="Shape Nate">
    </div>
    <div class="decoration_item shape_dollar_1">
        <img src="assets/images/shapes/shape_dollar_5.webp" data-parallax='{"y" : 100, "smoothness": 10}'
            alt="Shape Nate">
    </div>
    <div class="decoration_item shape_dollar_2">
        <img src="assets/images/shapes/shape_dollar_3.webp" data-parallax='{"y" : -100, "smoothness": 10}'
            alt="Shape Nate">
    </div>
</footer>