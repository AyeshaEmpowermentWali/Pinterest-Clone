<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$error = '';
$success = '';

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $source_url = sanitize($_POST['source_url']);
    
    if (empty($title)) {
        $error = 'Title is required';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select an image';
    } else {
        $image = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($image['type'], $allowed_types)) {
            $error = 'Invalid image type. Please use JPEG, PNG, GIF, or WebP';
        } elseif ($image['size'] > 5 * 1024 * 1024) { // 5MB limit
            $error = 'Image size must be less than 5MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/pins/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($image['tmp_name'], $file_path)) {
                // Save pin to database
                $stmt = $pdo->prepare("INSERT INTO pins (user_id, title, description, image_url, category_id, source_url) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$user['id'], $title, $description, $file_path, $category_id ?: null, $source_url ?: null])) {
                    $success = 'Pin created successfully!';
                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Failed to create pin';
                    unlink($file_path); // Delete uploaded file
                }
            } else {
                $error = 'Failed to upload image';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Pin - Pinterest Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f1f1;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 16px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e60023;
            text-decoration: none;
        }

        .nav-title {
            font-size: 20px;
            font-weight: 600;
            margin-left: 16px;
        }

        .nav-actions {
            margin-left: auto;
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #e60023;
            color: white;
        }

        .btn-primary:hover {
            background-color: #d50020;
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e1e1e1;
        }

        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .create-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .create-header {
            padding: 32px 32px 0;
            text-align: center;
        }

        .create-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .create-subtitle {
            color: #666;
            font-size: 16px;
        }

        .create-form {
            padding: 32px;
        }

        .form-row {
            display: flex;
            gap: 32px;
            align-items: flex-start;
        }

        .image-upload-section {
            flex: 1;
            max-width: 300px;
        }

        .details-section {
            flex: 1;
        }

        .image-upload {
            border: 2px dashed #e1e1e1;
            border-radius: 16px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            background: #fafafa;
        }

        .image-upload:hover {
            border-color: #e60023;
            background: #fff5f5;
        }

        .image-upload.dragover {
            border-color: #e60023;
            background: #fff5f5;
        }

        .upload-icon {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }

        .upload-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .upload-hint {
            font-size: 14px;
            color: #666;
        }

        .image-preview {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 16px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s;
            outline: none;
            font-family: inherit;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            border-color: #e60023;
            box-shadow: 0 0 0 3px rgba(230, 0, 35, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 1px solid #e1e1e1;
        }

        .error-message {
            background: #fee;
            color: #c53030;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            border-left: 4px solid #c53030;
        }

        .success-message {
            background: #f0fff4;
            color: #22543d;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            border-left: 4px solid #22543d;
        }

        .hidden {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 24px;
            }

            .image-upload-section {
                max-width: none;
            }

            .create-form {
                padding: 24px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">Pinterest</a>
            <div class="nav-title">Create Pin</div>
            <div class="nav-actions">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" form="createForm" class="btn btn-primary">Publish</button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="create-container">
            <div class="create-header">
                <h1 class="create-title">Create Pin</h1>
                <p class="create-subtitle">Share your ideas with the world</p>
            </div>

            <form class="create-form" method="POST" enctype="multipart/form-data" id="createForm">
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="image-upload-section">
                        <div class="image-upload" id="imageUpload" onclick="document.getElementById('imageInput').click()">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Choose a file</div>
                            <div class="upload-hint">Or drag and drop it here</div>
                            <img id="imagePreview" class="image-preview hidden" alt="Preview">
                        </div>
                        <input type="file" id="imageInput" name="image" accept="image/*" class="hidden" required>
                    </div>

                    <div class="details-section">
                        <div class="form-group">
                            <label class="form-label" for="title">Title *</label>
                            <input type="text" id="title" name="title" class="form-input" placeholder="Add a title" value="<?php echo $_POST['title'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-textarea" placeholder="Tell everyone what your Pin is about"><?php echo $_POST['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="source_url">Source URL</label>
                            <input type="url" id="source_url" name="source_url" class="form-input" placeholder="Add a link" value="<?php echo $_POST['source_url'] ?? ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Publish Pin</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Image upload handling
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', handleImageSelect);

        // Drag and drop functionality
        imageUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUpload.classList.add('dragover');
        });

        imageUpload.addEventListener('dragleave', () => {
            imageUpload.classList.remove('dragover');
        });

        imageUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                handleImageSelect();
            }
        });

        function handleImageSelect() {
            const file = imageInput.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                    imageInput.value = '';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB');
                    imageInput.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    
                    // Hide upload text
                    imageUpload.querySelector('.upload-icon').style.display = 'none';
                    imageUpload.querySelector('.upload-text').style.display = 'none';
                    imageUpload.querySelector('.upload-hint').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        // Form validation
        document.getElementById('createForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const image = document.getElementById('imageInput').files[0];

            if (!title) {
                e.preventDefault();
                alert('Please enter a title for your pin');
                document.getElementById('title').focus();
                return;
            }

            if (!image) {
                e.preventDefault();
                alert('Please select an image');
                return;
            }
        });

        // Auto-resize textarea
        const textarea = document.getElementById('description');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>
