<?php
use Illuminate\Database\Capsule\Manager as Capsule;
require ADMIN_FILES.'auth/authMiddleware.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token'])) {
    try {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Request Forbidden!, Try Again", 403);
        }
        $response = [];
        if(isset($_GET['delete'])){
            $itemId = $_GET['delete'];
            $deleted = Capsule::table('dynamic_routes')
                ->where('entity_type', 'redirect')
                ->where('route_id', $itemId)
                ->delete();
            
            $response = [
                'message' => 'Route Deleted Successfully!!!'
            ];
            
        } else {
            $draw = intval($_POST['draw']);
            $start = intval($_POST['start']);
            $length = intval($_POST['length']);
            $searchValue = sanitizeText($_POST['search']['value']);
            $orderColumnIndex = $_POST['order'][0]['column'];
            $orderDirection = $_POST['order'][0]['dir'];
            
            $columns = [
                'dynamic_routes.route_id',
                'dynamic_routes.route',
                'dynamic_routes.entity_type',
                'dynamic_routes.entity_id',
                'dynamic_routes.updated_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex - 1];
            
            $query = Capsule::table('dynamic_routes')
                ->select(
                    'dynamic_routes.route_id',
                    'dynamic_routes.route',
                    'dynamic_routes.redirect',
                    'dynamic_routes.entity_type',
                    'dynamic_routes.entity_id',
                    'dynamic_routes.updated_at'
                );
        
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('dynamic_routes.route', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('dynamic_routes')->count();
            $filteredRecords = $query->count();
            $query->orderBy($orderColumnName, $orderDirection)
                  ->offset($start)
                  ->limit($length);
            
            $data = $query->get();
            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $filteredRecords,
                "data" => $data
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (\Throwable $th) {
        $_SESSION['error_message'] = $th->getMessage();
        header("Refresh:0");
        exit;
    }
}
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
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Routes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Seo / Routes</li>
                    </ol>
                    
                    <?php if(isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    Routes List
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Actions</th>
                                            <th>ID</th>
                                            <th>Route</th>
                                            <th>Entity Type</th>
                                            <th>Entity ID</th>
                                            <th>Updated At</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require_once ADMIN_FILES.'layout/footer.php' ?>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
    <script>
        $('#datatable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "?",
                "type": "POST",
                "data": function(d) {
                    d.csrf_token = '<?= $_SESSION['csrf_token'] ?>';
                },
                "error": function(xhr, error, thrown) {
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        Swal.fire({
                            title: 'Error!',
                            text: "Error: " + xhr.responseJSON.error,
                            icon: 'error',
                            confirmButtonText: 'Okay',
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: "An error occurred while fetching data. Please try again",
                            icon: 'error',
                            confirmButtonText: 'Okay',
                        });
                    }
                }
            },
            "columns": [
                {
                    "data": "route_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        if(row.redirect){
                            return `
                                <button onclick="deleteItem(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                            `;
                        }else{
                            return `No Actions`;
                        }
                    }
                },
                { "data": "route_id" },
                { 
                    "data": "route",
                    "render": function(data, type, row) {
                        console.log(row);
                        
                        let html = `<a href="<?=BASE_URL?>${data}">${data}</a>`;
                        if(row.redirect){
                            html += `<br><i class="fa-solid fa-turn-up" style="transform: rotate(90deg);"></i>&nbsp;&nbsp;`;
                            html += `<a href="<?=BASE_URL?>${row.redirect}">${row.redirect}</a>`;
                        }
                        return html;
                    }
                },
                {
                    "data": "entity_type",
                    "render": function(data) {
                        return data.replace(/_/g, ' ')
                            .replace(/\b\w/g, function(char) { return char.toUpperCase(); });
                    }
                },
                { "data": "entity_id" },
                { 
                    "data": "updated_at",
                    "render": function(data) {
                        return data ? new Date(data).toLocaleString() : '-';
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function deleteItem(itemId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '?delete=' + itemId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Entry has been deleted.',
                                'success'
                            ).then(() => {
                                $('#datatable').DataTable().ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong',
                                'error'
                            );
                        }
                    });
                }
            });
        }
        
    </script>
</body>
</html>