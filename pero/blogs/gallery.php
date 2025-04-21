<?php
use Illuminate\Database\Capsule\Manager as Capsule;
require ADMIN_FILES.'auth/authMiddleware.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Request Forbidden! Try Again", 403);
        }

        $response = [];
        
        if(isset($_FILES['upload'])){
            $uploadDir = 'public/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = basename($_FILES['upload']['name']);
            $targetPath = $uploadDir . $filename;
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['upload']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Only JPG, PNG, GIF, and WEBP files are allowed.", 400);
            }
            
            if (!move_uploaded_file($_FILES['upload']['tmp_name'], $targetPath)) {
                throw new Exception("Error uploading file.", 500);
            }
            
            $response = [
                'success' => true,
                'message' => 'File uploaded successfully!!!'
            ];
            
        } elseif (isset($_POST['delete'])) {
            $fileToDelete = 'public/uploads/' . basename($_POST['delete']);
            
            if (!file_exists($fileToDelete) || !is_file($fileToDelete)) {
                throw new Exception("File not found", 404);
            }
            
            if (!unlink($fileToDelete)) {
                throw new Exception("Unable to delete file", 500);
            }
            
            $response = [
                'success' => true,
                'message' => 'File deleted successfully!!!'
            ];
        } else {
            throw new Exception("Invalid request", 400);
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Scan the uploads directory
$uploadPath = 'public/uploads/';
$images = [];
if (file_exists($uploadPath)) {
    $files = scandir($uploadPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadPath . $file;
            $mimeType = mime_content_type($filePath);
            
            if (is_file($filePath) && in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $images[] = [
                    'name' => $file,
                    'path' => BASE_URL . 'public/uploads/' . $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }
    }
    
    // Sort by modification time (newest first)
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <style>
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 15px;
        }
        .gallery-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .gallery-item:hover {
            transform: scale(1.02);
        }
        .gallery-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .gallery-actions {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            background: #f8f9fa;
        }
        .copy-btn {
            cursor: pointer;
            color: #0d6efd;
        }
        .copy-btn:hover {
            text-decoration: underline;
        }
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        .upload-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .upload-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .upload-title {
            font-size: 1rem;
            font-weight: 500;
            margin: 0;
            color: #495057;
        }
        .upload-form {
            display: flex;
            gap: 10px;
            align-items: center;
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
                    <h1 class="mt-4">Image Gallery</h1>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="upload-container mt-4">
                        <div class="upload-header">
                            <h5 class="upload-title">Upload Image</h5>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="upload-form">
                            <input class="form-control form-control-sm" type="file" name="upload" accept="image/*" required>
                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        </form>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-images me-1"></i>
                            Gallery (<?php echo count($images); ?> images)
                        </div>
                        <div class="card-body">
                            <?php if (empty($images)): ?>
                                <div class="alert alert-info">No images found in the uploads directory.</div>
                            <?php else: ?>
                                <div class="gallery">
                                    <?php foreach ($images as $image): ?>
                                        <div class="gallery-item">
                                            <img src="<?php echo htmlspecialchars($image['path']); ?>" class="gallery-img" alt="<?php echo htmlspecialchars($image['name']); ?>">
                                            <div class="gallery-actions">
                                                <span class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($image['path']); ?>')">
                                                    Copy URL
                                                </span>
                                                <button class="delete-btn" onclick="deleteImage('<?php echo htmlspecialchars($image['name']); ?>')">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </main>
            <?php require_once ADMIN_FILES.'layout/footer.php' ?>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Handle form submission with AJAX
            $('.upload-form').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON && xhr.responseJSON.error 
                            ? xhr.responseJSON.error 
                            : 'Something went wrong';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    }
                });
            });
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const tooltip = document.createElement('div');
                tooltip.style.position = 'fixed';
                tooltip.style.backgroundColor = '#333';
                tooltip.style.color = '#fff';
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = '4px';
                tooltip.style.zIndex = '10000';
                tooltip.style.top = '20px';
                tooltip.style.right = '20px';
                tooltip.textContent = 'URL copied!';
                document.body.appendChild(tooltip);
                
                setTimeout(() => {
                    document.body.removeChild(tooltip);
                }, 2000);
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        function deleteImage(imageName) {
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
                        url: window.location.href,
                        type: 'POST',
                        data: {
                            delete: imageName,
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = xhr.responseJSON && xhr.responseJSON.error 
                                ? xhr.responseJSON.error 
                                : 'Something went wrong';
                            Swal.fire(
                                'Error!',
                                errorMsg,
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