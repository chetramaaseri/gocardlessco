<script src="<?=BASE_URI?>assets/js/jquery.min.js"></script>
<script src="<?=BASE_URI?>assets/js/popper.min.js"></script>
<script src="<?=BASE_URI?>assets/js/bootstrap.min.js"></script>
<script src="<?=BASE_URI?>assets/js/bootstrap-dropdown-ml-hack.min.js"></script>
<script src="<?=BASE_URI?>assets/js/swiper-bundle.min.js"></script>
<script src="<?=BASE_URI?>assets/js/parallaxie.js"></script>
<script src="<?=BASE_URI?>assets/js/parallax-scroll.js"></script>
<script src="<?=BASE_URI?>assets/js/wow.min.js"></script>
<script src="<?=BASE_URI?>assets/js/magnific-popup.min.js"></script>
<script src="<?=BASE_URI?>assets/js/appear.min.js"></script>
<script src="<?=BASE_URI?>assets/js/odometer.min.js"></script>
<script src="<?=BASE_URI?>assets/js/ticker.min.js"></script>
<script src="<?=BASE_URI?>assets/js/main.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const chatWidget = document.querySelector('.fb-chat-widget');
    
    document.querySelectorAll('a').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            // Add and remove highlight class
            chatWidget.classList.add('highlight');
            setTimeout(() => {
                chatWidget.classList.remove('highlight');
            }, 500);
        });
    });
});
</script>