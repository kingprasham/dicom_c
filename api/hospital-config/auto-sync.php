<?php
/**
 * Auto-Sync API
 * Handles automatic folder monitoring and syncing - SUPPORTS MULTIPLE PATHS
 */
header('Content-Type: application/json');

// Prevent any HTML output before JSON
error_reporting(0);
ini_set('display_errors', 0);

define('DICOM_VIEWER', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../auth/session.php';

try {
    requireLogin();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDbConnection();
    
    // Ensure tables exist with support for multiple paths
    $db->query("
        CREATE TABLE IF NOT EXISTS monitored_paths (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(1000) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_checked DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_path (path(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $db->query("
        CREATE TABLE IF NOT EXISTS known_folders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            folder_path VARCHAR(1000) NOT NULL,
            folder_name VARCHAR(255),
            monitored_path_id INT,
            first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced_at DATETIME,
            UNIQUE KEY unique_path (folder_path(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    if ($method === 'GET') {
        switch ($action) {
            case 'get_path':
                // Return all active paths (backward compatible - first path as main)
                $result = $db->query("SELECT id, path, name, is_active, last_checked FROM monitored_paths ORDER BY id ASC");
                $paths = [];
                $mainPath = '';
                
                while ($row = $result->fetch_assoc()) {
                    $paths[] = $row;
                    if (empty($mainPath) && $row['is_active']) {
                        $mainPath = $row['path'];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'path' => $mainPath, // backward compatibility
                    'paths' => $paths    // new: all paths
                ]);
                break;
            
            case 'get_all_paths':
                // Get all monitored paths
                $result = $db->query("SELECT id, path, name, is_active, last_checked, created_at FROM monitored_paths ORDER BY created_at ASC");
                $paths = [];
                
                while ($row = $result->fetch_assoc()) {
                    $paths[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'paths' => $paths
                ]);
                break;
                
            case 'check_folders':
                // Check folders in ALL active monitored paths
                $result = $db->query("SELECT id, path, name FROM monitored_paths WHERE is_active = 1");
                
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'No monitored paths configured']);
                    exit;
                }
                
                $allFolders = [];
                
                while ($row = $result->fetch_assoc()) {
                    $path = $row['path'];
                    $pathId = $row['id'];
                    $pathName = $row['name'] ?: basename($path);
                    
                    if (!is_dir($path)) {
                        continue;
                    }
                    
                    $iterator = new DirectoryIterator($path);
                    
                    foreach ($iterator as $item) {
                        if ($item->isDir() && !$item->isDot()) {
                            $folderPath = $item->getPathname();
                            $folderName = $item->getFilename();
                            
                            // Check if this folder is already known
                            $stmt = $db->prepare("SELECT id FROM known_folders WHERE folder_path = ?");
                            $stmt->bind_param('s', $folderPath);
                            $stmt->execute();
                            $knownResult = $stmt->get_result();
                            $isNew = $knownResult->num_rows === 0;
                            $stmt->close();
                            
                            $allFolders[] = [
                                'name' => $folderName,
                                'path' => $folderPath,
                                'parent_path' => $pathName,
                                'is_new' => $isNew
                            ];
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'folders' => $allFolders,
                    'total' => count($allFolders)
                ]);
                break;
                
            case 'check_and_sync':
                // Sync ALL active monitored paths
                $result = $db->query("SELECT id, path, name FROM monitored_paths WHERE is_active = 1");
                
                if ($result->num_rows === 0) {
                    echo json_encode(['success' => true, 'new_folders' => [], 'message' => 'No monitored paths configured']);
                    exit;
                }
                
                $newFolders = [];
                $pathsChecked = 0;
                
                while ($row = $result->fetch_assoc()) {
                    $path = $row['path'];
                    $pathId = $row['id'];
                    $pathName = $row['name'] ?: basename($path);
                    
                    if (!is_dir($path)) {
                        continue;
                    }
                    
                    $pathsChecked++;
                    $iterator = new DirectoryIterator($path);
                    
                    foreach ($iterator as $item) {
                        if ($item->isDir() && !$item->isDot()) {
                            $folderPath = $item->getPathname();
                            $folderName = $item->getFilename();
                            
                            // Check if this folder is already known
                            $stmt = $db->prepare("SELECT id FROM known_folders WHERE folder_path = ?");
                            $stmt->bind_param('s', $folderPath);
                            $stmt->execute();
                            $knownResult = $stmt->get_result();
                            
                            if ($knownResult->num_rows === 0) {
                                // This is a new folder
                                $newFolders[] = [
                                    'name' => $folderName,
                                    'path' => $folderPath,
                                    'parent_path' => $pathName,
                                    'is_new' => true
                                ];
                                
                                // Add to known folders
                                $insertStmt = $db->prepare("INSERT INTO known_folders (folder_path, folder_name, monitored_path_id) VALUES (?, ?, ?)");
                                $insertStmt->bind_param('ssi', $folderPath, $folderName, $pathId);
                                $insertStmt->execute();
                                $insertStmt->close();
                            }
                            $stmt->close();
                        }
                    }
                    
                    // Update last checked time
                    $db->query("UPDATE monitored_paths SET last_checked = NOW() WHERE id = $pathId");
                }
                
                echo json_encode([
                    'success' => true,
                    'new_folders' => $newFolders,
                    'total_new' => count($newFolders),
                    'paths_checked' => $pathsChecked
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $postAction = $input['action'] ?? $action;
        
        switch ($postAction) {
            case 'save_path':
            case 'add_path':
                $path = trim($input['path'] ?? '');
                $name = trim($input['name'] ?? '');
                
                if (empty($path)) {
                    echo json_encode(['success' => false, 'error' => 'Path cannot be empty']);
                    exit;
                }
                
                // Validate path exists
                if (!is_dir($path)) {
                    echo json_encode(['success' => false, 'error' => 'Directory does not exist: ' . $path]);
                    exit;
                }
                
                // If no name provided, use folder name
                if (empty($name)) {
                    $name = basename($path);
                }
                
                // Check if path already exists
                $stmt = $db->prepare("SELECT id FROM monitored_paths WHERE path = ?");
                $stmt->bind_param('s', $path);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($existing) {
                    // Update existing path
                    $stmt = $db->prepare("UPDATE monitored_paths SET name = ?, is_active = 1 WHERE id = ?");
                    $stmt->bind_param('si', $name, $existing['id']);
                    $stmt->execute();
                    $stmt->close();
                    echo json_encode(['success' => true, 'message' => 'Path updated successfully', 'id' => $existing['id']]);
                } else {
                    // Insert new path
                    $stmt = $db->prepare("INSERT INTO monitored_paths (path, name, is_active) VALUES (?, ?, 1)");
                    $stmt->bind_param('ss', $path, $name);
                    
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Path added successfully', 'id' => $stmt->insert_id]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to save path']);
                    }
                    $stmt->close();
                }
                break;
                
            case 'remove_path':
                $pathId = intval($input['id'] ?? 0);
                
                if ($pathId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid path ID']);
                    exit;
                }
                
                // Delete the path
                $stmt = $db->prepare("DELETE FROM monitored_paths WHERE id = ?");
                $stmt->bind_param('i', $pathId);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Path removed successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to remove path']);
                }
                $stmt->close();
                break;
                
            case 'toggle_path':
                $pathId = intval($input['id'] ?? 0);
                $isActive = intval($input['is_active'] ?? 1);
                
                if ($pathId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid path ID']);
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE monitored_paths SET is_active = ? WHERE id = ?");
                $stmt->bind_param('ii', $isActive, $pathId);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => $isActive ? 'Path activated' : 'Path deactivated']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update path']);
                }
                $stmt->close();
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    }
    elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $pathId = intval($input['id'] ?? $_GET['id'] ?? 0);
        
        if ($pathId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid path ID']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM monitored_paths WHERE id = ?");
        $stmt->bind_param('i', $pathId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Path removed successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove path']);
        }
        $stmt->close();
    }
    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
