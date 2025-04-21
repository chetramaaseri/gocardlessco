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
            $quoteId = $_GET['delete'];
            Capsule::table('quotes')
                ->where('quote_id', $quoteId)
                ->delete();
            $response = [
                'message' => 'Quote deleted successfully!'
            ];
            
        } elseif(isset($_GET['update_status'])){
            $quoteId = $_GET['update_status'];
            $newStatus = $_POST['status'];
            Capsule::table('quotes')
                ->where('quote_id', $quoteId)
                ->update(['status' => sanitizeText($newStatus)]);
            $response = [
                'message' => 'Status updated successfully!'
            ];
            
        } else {
            // Handle datatable request
            $draw = intval($_POST['draw']);
            $start = intval($_POST['start']);
            $length = intval($_POST['length']);
            $searchValue = sanitizeText($_POST['search']['value']);
            $orderColumnIndex = $_POST['order'][0]['column'];
            $orderDirection = $_POST['order'][0]['dir'];
            
            $columns = [
                'quotes.quote_id',
                'quotes.full_name',
                'quotes.email',
                'quotes.phone',
                'quotes.project_type',
                'quotes.status',
                'quotes.submitted_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex - 1];
            
            $query = Capsule::table('quotes')
                ->select(
                    'quotes.quote_id',
                    'quotes.full_name',
                    'quotes.email',
                    'quotes.phone',
                    'quotes.project_type',
                    'quotes.home_type',
                    'quotes.square_footage',
                    'quotes.timeline',
                    'quotes.status',
                    'quotes.submitted_at'
                );
        
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('quotes.full_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('quotes.email', 'like', '%' . $searchValue . '%')
                      ->orWhere('quotes.phone', 'like', '%' . $searchValue . '%')
                      ->orWhere('quotes.project_type', 'like', '%' . $searchValue . '%')
                      ->orWhere('quotes.status', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('quotes')->count();
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
        header('Content-Type: application/json');
        echo json_encode([
            "error" => $th->getMessage()
        ]);
        exit;
    }
}

// Get quote data for viewing
$quoteDetails = null;
if (isset($_GET['view'])) {
    $quoteDetails = Capsule::table('quotes')
        ->where('quote_id', $_GET['view'])
        ->first();
    if (!$quoteDetails) {
        $_SESSION['error_message'] = 'Quote not found';
        header("Location: ?");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-new { background-color: #d1ecf1; color: #0c5460; }
        .status-in_review { background-color: #fff3cd; color: #856404; }
        .status-contacted { background-color: #c3e6cb; color: #155724; }
        .status-completed { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php if(isset($_GET['view'])): ?>
                        <h1 class="mt-4">Quote Details</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item">Enquiry</li>
                            <li class="breadcrumb-item">Quote Requests</li>
                            <li class="breadcrumb-item">View Request</li>
                        </ol>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-file-alt me-1"></i>
                                        Quote #<?= $quoteDetails->quote_id ?>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= $quoteDetails->status ?>">
                                            <?= ucfirst(str_replace('_', ' ', $quoteDetails->status)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Personal Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Full Name</label>
                                            <p class="form-control-static"><?= htmlspecialchars($quoteDetails->full_name) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Email</label>
                                            <p class="form-control-static"><?= htmlspecialchars($quoteDetails->email) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Phone</label>
                                            <p class="form-control-static"><?= htmlspecialchars($quoteDetails->phone) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Address</label>
                                            <p class="form-control-static"><?= $quoteDetails->address ? htmlspecialchars($quoteDetails->address) : 'Not provided' ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Project Details</h5>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Project Type</label>
                                            <p class="form-control-static"><?= htmlspecialchars($quoteDetails->project_type) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Home Type</label>
                                            <p class="form-control-static"><?= $quoteDetails->home_type ? htmlspecialchars($quoteDetails->home_type) : 'Not specified' ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Square Footage</label>
                                            <p class="form-control-static"><?= $quoteDetails->square_footage ? htmlspecialchars($quoteDetails->square_footage) : 'Not specified' ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Timeline</label>
                                            <p class="form-control-static"><?= $quoteDetails->timeline ? htmlspecialchars($quoteDetails->timeline) : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-3">
                                        <h5 class="mb-3">Additional Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Project Description</label>
                                            <div class="border p-3 rounded bg-light">
                                                <?= $quoteDetails->project_description ? nl2br(htmlspecialchars($quoteDetails->project_description)) : 'No description provided' ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Newsletter Consent</label>
                                            <p class="form-control-static">
                                                <?= $quoteDetails->newsletter_consent ? 'Yes' : 'No' ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <label class="form-label text-muted">Submitted At</label>
                                                <p class="form-control-static">
                                                    <?= date('M j, Y g:i A', strtotime($quoteDetails->submitted_at)) ?>
                                                </p>
                                            </div>
                                            <div>
                                                <label class="form-label text-muted">IP Address</label>
                                                <p class="form-control-static">
                                                    <?= $quoteDetails->ip_address ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" onclick="updateStatus(<?= $quoteDetails->id ?>)">
                                    <i class="fas fa-sync-alt me-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Quote Requests</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item">Enquiry</li>
                            <li class="breadcrumb-item">Quote Requests</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Quote Requests
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
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Project Type</th>
                                                <th>Status</th>
                                                <th>Submitted At</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
                }
            },
            "columns": [
                {
                    "data": "quote_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <button onclick="updateStatus(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-pen-to-square"></i></button>
                            <a href="?view=${data}" class="btn p-0 ps-1"><i class="fa-solid fa-eye"></i></a>
                            <button onclick="deleteItem(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                        `;
                    }
                },
                { "data": "quote_id" },
                { "data": "full_name" },
                { "data": "email" },
                { "data": "phone" },
                { "data": "project_type" },
                { 
                    "data": "status",
                    "render": function(data) {
                        const statusMap = {
                            'new': 'New',
                            'in_review': 'In Review',
                            'contacted': 'Contacted',
                            'completed': 'Completed'
                        };
                        return `<span class="status-badge status-${data}">${statusMap[data] || data}</span>`;
                    }
                },
                { 
                    "data": "submitted_at",
                    "render": function(data) {
                        return data ? new Date(data).toLocaleString() : '-';
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function updateStatus(quoteId) {
            Swal.fire({
                title: 'Update Status',
                input: 'select',
                inputOptions: {
                    'new': 'New',
                    'in_review': 'In Review',
                    'contacted': 'Contacted',
                    'completed': 'Completed'
                },
                inputValue: 'new',
                showCancelButton: true,
                confirmButtonText: 'Update',
                preConfirm: (status) => {
                    return $.ajax({
                        url: '?update_status=' + quoteId,
                        type: 'POST',
                        data: {
                            status: status,
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        }
                    }).then(response => {
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage('Error updating status');
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        'Updated!',
                        'Status has been updated.',
                        'success'
                    ).then(() => {
                        $('#datatable').DataTable().ajax.reload();
                        if (window.location.href.includes('view=')) {
                            location.reload();
                        }
                    });
                }
            });
        }
        
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
                                'Quote has been deleted.',
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