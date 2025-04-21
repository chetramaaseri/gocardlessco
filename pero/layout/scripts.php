<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js" integrity="sha512-BkpSL20WETFylMrcirBahHfSnY++H2O1W+UnEEO4yNIl+jI2+zowyoGJpbtk6bx97fBXf++WJHSSK2MV4ghPcg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?=ADMIN_ASSET?>assets/js/scripts.js"></script>

<script>
    <?php if(!empty($_SESSION['alertAsync'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?=$_SESSION['alertAsync']?>',
            icon: 'error',
            confirmButtonText: 'Okay',
        });
    <?php unset($_SESSION['alertAsync']); endif; ?>
    <?php if(!empty($_SESSION['messageAsync'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?=$_SESSION['messageAsync']?>',
            icon: 'success',
            confirmButtonText: 'Okay',
        });
    <?php unset($_SESSION['messageAsync']); endif; ?>
</script>