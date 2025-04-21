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
            $queryId = $_GET['delete'];
            // Delete chat history first
            Capsule::table('chat_history')
                ->where('query_id', $queryId)
                ->delete();
            // Then delete the query
            Capsule::table('queries')
                ->where('query_id', $queryId)
                ->delete();
                
            $response = [
                'message' => 'Query and Chat History Deleted Successfully!!!'
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
                'queries.query_id',
                'queries.name',
                'users.name',
                'queries.status',
                'queries.created_at',
                'queries.updated_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex];
            
            $query = Capsule::table('queries')
                ->leftJoin('users', 'queries.executive_id', '=', 'users.user_id')
                ->select(
                    'queries.query_id',
                    'queries.name as customer_name',
                    'users.name as executive_name',
                    'queries.status',
                    'queries.created_at',
                    'queries.updated_at'
                );
            
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('queries.name', 'like', '%' . $searchValue . '%')
                      ->orWhere('users.name', 'like', '%' . $searchValue . '%')
                      ->orWhere('queries.status', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('queries')->count();
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

// Get chat history for viewing
$chatHistory = [];
$queryDetails = null;
if (isset($_GET['view'])) {
    $queryId = $_GET['view'];
    
    $queryDetails = Capsule::table('queries')
        ->leftJoin('users', 'queries.executive_id', '=', 'users.user_id')
        ->where('query_id', $queryId)
        ->select(
            'queries.*',
            'users.name as executive_name'
        )
        ->first();
        
    if (!$queryDetails) {
        $_SESSION['error_message'] = 'Query not found';
        header("Location: ?");
        exit;
    }
    
    // Get all chat history parts for this query
    $chatParts = Capsule::table('chat_history')
        ->where('query_id', $queryId)
        ->orderBy('part', 'asc')
        ->get();
        
    // Combine all chat parts into single history
    foreach ($chatParts as $part) {
        $messages = json_decode($part->chats, true);
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $chatHistory[] = $message;
            }
        }
    }
    
    // Sort chat history by time
    usort($chatHistory, function($a, $b) {
        return strtotime($a['time']) - strtotime($b['time']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <style>
        .chat-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 80%;
        }
        .user-message {
            background-color: #e3f2fd;
            margin-left: auto;
        }
        .agent-message {
            background-color: #f1f1f1;
            margin-right: auto;
        }
        .message-header {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .message-time {
            font-size: 0.8em;
            color: #666;
            text-align: right;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 10px;
        }
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
                        <h1 class="mt-4">Chat History</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="?">Queries</a></li>
                            <li class="breadcrumb-item active">Chat History</li>
                        </ol>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-comments me-1"></i>
                                        Conversation Details
                                    </div>
                                    <div>
                                        <span class="badge bg-<?= $queryDetails->status === 'closed' ? 'success' : ($queryDetails->status === 'assigned' ? 'primary' : 'warning') ?>">
                                            <?= ucfirst($queryDetails->status) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5>Customer Information</h5>
                                        <p><strong>Name:</strong> <?= htmlspecialchars($queryDetails->name) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($queryDetails->email) ?></p>
                                        <p><strong>Mobile:</strong> <?= htmlspecialchars($queryDetails->mobile) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Assigned Executive</h5>
                                        <p><strong>Name:</strong> <?= htmlspecialchars($queryDetails->executive_name ?? 'Not assigned') ?></p>
                                        <p><strong>Started:</strong> <?= date('Y-m-d H:i:s', strtotime($queryDetails->created_at)) ?></p>
                                        <p><strong>Last Updated:</strong> <?= date('Y-m-d H:i:s', strtotime($queryDetails->updated_at)) ?></p>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3">Chat History</h5>
                                <div class="chat-container">
                                    <?php if(empty($chatHistory)): ?>
                                        <div class="alert alert-info">No chat history found for this query.</div>
                                    <?php else: ?>
                                        <?php foreach ($chatHistory as $message): ?>
                                            <div class="chat-message <?= $message['sender'] === 'user' ? 'user-message' : 'agent-message' ?>">
                                                <div class="message-header">
                                                    <?= $message['sender'] === 'user' ? 'Customer' : 'Agent' ?>
                                                </div>
                                                <div class="message-content">
                                                    <?= nl2br(htmlspecialchars($message['text'])) ?>
                                                </div>
                                                <div class="message-time">
                                                    <?= date('Y-m-d H:i:s', strtotime($message['time'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="?" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Queries
                                    </a>
                                    <button onclick="deleteQuery(<?= $queryDetails->query_id ?>)" class="btn btn-danger float-end">
                                        <i class="fas fa-trash me-1"></i> Delete Query
                                    </button>
                                </div>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Queries</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Chat Queries Management</li>
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
                                        <i class="fas fa-question-circle me-1"></i>
                                        Chat Queries List
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
                                                <th>Customer</th>
                                                <th>Executive</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                                <th>Last Updated</th>
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
                    "data": "query_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <a href="?view=${data}" class="btn btn-sm btn-primary" title="View Chat History">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        `;
                    }
                },
                { "data": "query_id" },
                { 
                    "data": "customer_name",
                    "render": function(data, type, row) {
                        return `<a href="?view=${row.query_id}">${data}</a>`;
                    }
                },
                { 
                    "data": "executive_name",
                    "render": function(data) {
                        return data || 'Not assigned';
                    }
                },
                { 
                    "data": "status",
                    "render": function(data) {
                        const statusClasses = {
                            'new': 'secondary',
                            'filled': 'info',
                            'assigned': 'primary',
                            'closed': 'success'
                        };
                        return `<span class="badge bg-${statusClasses[data] || 'warning'}">${data}</span>`;
                    }
                },
                { 
                    "data": "created_at",
                    "render": function(data) {
                        return new Date(data).toLocaleString();
                    }
                },
                { 
                    "data": "updated_at",
                    "render": function(data) {
                        return new Date(data).toLocaleString();
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function deleteQuery(queryId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the query and all its chat history permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '?delete=' + queryId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'The query and its chat history have been deleted.',
                                'success'
                            ).then(() => {
                                window.location.href = '?';
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