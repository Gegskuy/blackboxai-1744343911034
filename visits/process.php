<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateVisit();
            break;
        case 'update':
            handleUpdateVisit();
            break;
        default:
            header('Location: ../dashboard.php?error=Invalid action');
            exit;
    }
}

function handleCreateVisit() {
    global $pdo;
    
    // Validate input
    $visit_date = $_POST['visit_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $description = $_POST['description'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!$visit_date || !$start_time || !$end_time || !$description) {
        header('Location: create.php?error=Please fill in all required fields');
        exit;
    }
    
    // Validate date and time
    $visit_datetime = strtotime($visit_date);
    $current_datetime = strtotime(date('Y-m-d'));
    
    if ($visit_datetime < $current_datetime) {
        header('Location: create.php?error=Visit date cannot be in the past');
        exit;
    }
    
    if ($start_time >= $end_time) {
        header('Location: create.php?error=End time must be after start time');
        exit;
    }

    // Handle photo upload
    $photo_path = '';
    if (isset($_FILES['visit_photo']) && $_FILES['visit_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['visit_photo'];
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            header('Location: create.php?error=File size must be less than 5MB');
            exit;
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            header('Location: create.php?error=Invalid file type. Please upload an image (JPEG, PNG, or GIF)');
            exit;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/visits';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Move uploaded file
        $photo_path = 'uploads/visits/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], '../' . $photo_path)) {
            header('Location: create.php?error=Failed to upload file');
            exit;
        }
    } else {
        header('Location: create.php?error=Please upload a photo');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO visits (
                visitor_id, 
                visit_date, 
                start_time, 
                end_time, 
                photo_path,
                description,
                notes, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $visit_date,
            $start_time,
            $end_time,
            $photo_path,
            $description,
            $notes
        ]);
        
        // Log the activity
        logActivity($_SESSION['user_id'], 'create_visit', 'Created new visit request for ' . $visit_date);
        
        header('Location: create.php?success=1');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error creating visit: " . $e->getMessage());
        header('Location: create.php?error=Failed to create visit request');
        exit;
    }
}

function handleUpdateVisit() {
    global $pdo;
    
    if (!hasPermission('employee')) {
        header('Location: ../dashboard.php?error=Unauthorized');
        exit;
    }
    
    $visit_id = $_POST['visit_id'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $description = $_POST['description'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!$visit_id || !$visit_date || !$start_time || !$end_time || !$description) {
        header('Location: edit.php?id=' . $visit_id . '&error=Please fill in all required fields');
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT visitor_id, photo_path FROM visits WHERE id = ?");
        $stmt->execute([$visit_id]);
        $visit = $stmt->fetch();
        
        if (!$visit || $visit['visitor_id'] != $_SESSION['user_id']) {
            header('Location: ../dashboard.php?error=Unauthorized');
            exit;
        }
        
        // Handle new photo upload if provided
        $photo_path = $visit['photo_path'];
        if (isset($_FILES['visit_photo']) && $_FILES['visit_photo']['error'] === UPLOAD_ERR_OK) {
            // Delete old photo if it exists
            if ($visit['photo_path'] && file_exists('../' . $visit['photo_path'])) {
                unlink('../' . $visit['photo_path']);
            }
            
            // Upload new photo
            $file = $_FILES['visit_photo'];
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                header('Location: edit.php?id=' . $visit_id . '&error=File size must be less than 5MB');
                exit;
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                header('Location: edit.php?id=' . $visit_id . '&error=Invalid file type. Please upload an image (JPEG, PNG, or GIF)');
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/visits';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Move uploaded file
            $photo_path = 'uploads/visits/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], '../' . $photo_path)) {
                header('Location: edit.php?id=' . $visit_id . '&error=Failed to upload file');
                exit;
            }
        }
        
        // Update visit
        $stmt = $pdo->prepare("
            UPDATE visits 
            SET visit_date = ?, 
                start_time = ?, 
                end_time = ?, 
                photo_path = ?,
                description = ?,
                notes = ?, 
                status = 'pending'
            WHERE id = ? AND visitor_id = ?
        ");
        
        $stmt->execute([
            $visit_date,
            $start_time,
            $end_time,
            $photo_path,
            $description,
            $notes,
            $visit_id,
            $_SESSION['user_id']
        ]);
        
        // Log the activity
        logActivity($_SESSION['user_id'], 'update_visit', 'Updated visit request #' . $visit_id);
        
        header('Location: view.php?id=' . $visit_id . '&success=1');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error updating visit: " . $e->getMessage());
        header('Location: edit.php?id=' . $visit_id . '&error=Failed to update visit');
        exit;
    }
}
?>
