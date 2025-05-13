<?php

namespace App\Http\Controllers;

use App\Models\Document\RoutingSlipFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    /**
     * Download a file with proper headers to force download
     *
     * @param int $id
     * @return StreamedResponse
     */
    public function download($id)
    {
        try {
            $file = RoutingSlipFile::findOrFail($id);
            
            // Log the download attempt with user info for debugging
            $userId = auth()->id() ?? 'unauthenticated';
            $userName = auth()->user() ? auth()->user()->name : 'unauthenticated';
            \Illuminate\Support\Facades\Log::info("Download attempt for file ID: {$id}, path: {$file->file_path}, by user: {$userName} (ID: {$userId})");
            
            // Check if file path is empty or null
            if (empty($file->file_path) && empty($file->file_name)) {
                \Illuminate\Support\Facades\Log::warning("Both file path and file name are empty for ID: {$id}, user: {$userId}");
                abort(404, 'File path information is missing. Please contact system administrator.');
            }
            
            // ENHANCED: Try multiple approaches to find the file
            $actualPath = null;
            $searchPaths = [];
            
            // 1. Try with file_path first
            if (!empty($file->file_path)) {
                $actualPath = $this->resolveFilePath($file->file_path);
                $searchPaths[] = $file->file_path;
            }
            
            // 2. If not found, try with file_name
            if (!$actualPath && !empty($file->file_name)) {
                $actualPath = $this->resolveFilePath($file->file_name);
                $searchPaths[] = $file->file_name;
            }
            
            // 3. If still not found, try with both combined in different ways
            if (!$actualPath && !empty($file->file_path) && !empty($file->file_name)) {
                $dirPath = dirname($file->file_path);
                $combinedPath = $dirPath . '/' . $file->file_name;
                $actualPath = $this->resolveFilePath($combinedPath);
                $searchPaths[] = $combinedPath;
            }
            
            // 4. ENHANCED: Try direct Windows path format
            if (!$actualPath && !empty($file->file_path)) {
                $windowsPath = str_replace('/', '\\', $file->file_path);
                $actualPath = file_exists($windowsPath) ? $windowsPath : null;
                if ($actualPath) {
                    \Illuminate\Support\Facades\Log::info("File found using direct Windows path format: {$windowsPath}");
                }
                $searchPaths[] = $windowsPath;
            }
            
            // 5. ENHANCED: Try with absolute storage path
            if (!$actualPath && !empty($file->file_name)) {
                $storageAppPath = storage_path('app/public/' . $file->file_name);
                $actualPath = file_exists($storageAppPath) ? $storageAppPath : null;
                if ($actualPath) {
                    \Illuminate\Support\Facades\Log::info("File found using absolute storage path: {$storageAppPath}");
                }
                $searchPaths[] = $storageAppPath;
            }
            
            // If all attempts failed, log detailed information and abort
            if (!$actualPath) {
                $searchPathsStr = implode(', ', $searchPaths);
                \Illuminate\Support\Facades\Log::warning("File not found for ID: {$id}, tried paths: {$searchPathsStr}, user: {$userId}");
                abort(404, 'File not found. The file may have been moved or deleted. Please contact system administrator.');
            }
            
            // Get the file content callback with improved handling
            $fileContentCallback = $this->getFileContent($actualPath);
            if (!$fileContentCallback) {
                \Illuminate\Support\Facades\Log::warning("File content could not be retrieved for ID: {$id}, resolved path: {$actualPath}, user: {$userId}");
                abort(404, 'File could not be retrieved. You may not have permission to access this file. Please contact system administrator.');
            }
            
            // Add current date and time in Manila timezone to filename for better organization
            $originalFilename = $file->file_name;
            $fileInfo = pathinfo($originalFilename);
            $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            $filenameWithoutExt = isset($fileInfo['filename']) ? $fileInfo['filename'] : $originalFilename;
            
            // Use Carbon to get Manila (Philippines) time for the timestamp
            $currentDateTime = \Carbon\Carbon::now('Asia/Manila')->format('Y-m-d_h-i-a'); // Format: Year-Month-Day_Hour-Minute-AM/PM in Manila time
            $filename = $filenameWithoutExt . '_' . $currentDateTime . $extension;
            
            // Ensure proper filename encoding for all browsers
            $encodedFilename = rawurlencode($filename);
            
            // Log successful download
            \Illuminate\Support\Facades\Log::info("File download successful for ID: {$id}, original filename: {$originalFilename}, download filename: {$filename}, user: {$userId}, actual path: {$actualPath}");
            
            // Create headers array with enhanced browser compatibility
            $headers = [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
                // RFC 6266 compliant header for international filenames
                'Content-Disposition' => "attachment; filename=\"$filename\"; filename*=UTF-8''$encodedFilename",
                // Prevent caching issues
                'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                // Additional headers to prevent buffering issues
                'X-Accel-Buffering' => 'no'
            ];
            
            // ENHANCED: For large files, use X-Sendfile if available
            if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
                \Illuminate\Support\Facades\Log::info("Using X-Sendfile for efficient file transfer: {$actualPath}");
                return response()->make('', 200, array_merge($headers, [
                    'X-Sendfile' => $actualPath
                ]));
            }
            
            // Return the file as a download using the callback with date-appended filename
            return response()->streamDownload(
                $fileContentCallback,
                $filename, 
                $headers
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log when file record is not found
            \Illuminate\Support\Facades\Log::warning("File record not found for ID: {$id}");
            abort(404, 'The requested file does not exist in our records.');
        } catch (\Exception $e) {
            // Log any unexpected errors with detailed information
            \Illuminate\Support\Facades\Log::error("Error downloading file ID: {$id}, error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            abort(500, 'An error occurred while downloading the file. Please try again later or contact system administrator.');
        }
    }
    
    /**
     * Resolve the actual file path by checking various locations
     *
     * @param string $filePath
     * @return string|false
     */
    private function resolveFilePath($filePath)
    {
        // Log the file path for debugging
        \Illuminate\Support\Facades\Log::info("Attempting to resolve file path: {$filePath}");
        
        // Handle null or empty paths
        if (empty($filePath)) {
            \Illuminate\Support\Facades\Log::warning("Empty file path provided to resolveFilePath");
            return false;
        }
        
        // Normalize file path to handle Windows backslashes (ensure double backslashes are properly replaced)
        $filePath = str_replace(['\\\\', '\\'], '/', $filePath);
        
        // Decode URL-encoded characters (for files with spaces or special characters)
        $filePath = urldecode($filePath);
        
        // Remove any leading/trailing whitespace that might cause issues
        $filePath = trim($filePath);
        
        // Store original filename for recursive search
        $originalFilename = basename($filePath);
        
        // Check if the path is a direct path first (absolute path)
        if (file_exists($filePath)) {
            \Illuminate\Support\Facades\Log::info("File found at direct path: {$filePath}");
            return $filePath;
        }
        
        // Check in public disk (where files are uploaded according to FileUploadController)
        if (Storage::disk('public')->exists($filePath)) {
            $resolvedPath = Storage::disk('public')->path($filePath);
            \Illuminate\Support\Facades\Log::info("File found in public disk: {$resolvedPath}");
            return $resolvedPath;
        }
        
        // Check in local storage
        if (Storage::disk('local')->exists($filePath)) {
            $resolvedPath = Storage::disk('local')->path($filePath);
            \Illuminate\Support\Facades\Log::info("File found in local storage: {$resolvedPath}");
            return $resolvedPath;
        }
        
        // Check in public folder with storage symlink
        $publicPath = public_path("storage/$filePath");
        if (file_exists($publicPath)) {
            \Illuminate\Support\Facades\Log::info("File found in public folder: {$publicPath}");
            return $publicPath;
        }
        
        // Check if the path already contains 'storage/app/public'
        if (strpos($filePath, 'storage/app/public/') === 0) {
            $strippedPath = str_replace('storage/app/public/', '', $filePath);
            if (Storage::disk('public')->exists($strippedPath)) {
                $resolvedPath = Storage::disk('public')->path($strippedPath);
                \Illuminate\Support\Facades\Log::info("File found after stripping path: {$resolvedPath}");
                return $resolvedPath;
            }
        }
        
        // Check if the path is relative to the storage path
        $storagePath = storage_path("app/public/$filePath");
        if (file_exists($storagePath)) {
            \Illuminate\Support\Facades\Log::info("File found in storage path: {$storagePath}");
            return $storagePath;
        }
        
        // Check for routing-slip-attachments directory specifically
        if (strpos($filePath, 'routing-slip-attachments/') !== false || strpos($filePath, 'routing-slip-attachments\\') !== false) {
            // Try with just the filename (in case the path is stored differently)
            $filename = basename($filePath);
            
            // Check in routing-slip-attachments directory
            if (Storage::disk('public')->exists("routing-slip-attachments/$filename")) {
                $resolvedPath = Storage::disk('public')->path("routing-slip-attachments/$filename");
                \Illuminate\Support\Facades\Log::info("File found by filename in routing-slip-attachments: {$resolvedPath}");
                return $resolvedPath;
            }
            
            // Check in local storage
            if (Storage::disk('local')->exists("routing-slip-attachments/$filename")) {
                $resolvedPath = Storage::disk('local')->path("routing-slip-attachments/$filename");
                \Illuminate\Support\Facades\Log::info("File found by filename in local routing-slip-attachments: {$resolvedPath}");
                return $resolvedPath;
            }
        }
        
        // Try to find the file by just the filename in common storage locations
        $filename = basename($filePath);
        
        // Handle filenames with spaces and special characters
        $encodedFilename = rawurlencode($filename);
        $decodedFilename = rawurldecode($filename);
        
        // Check in public disk root with various filename formats
        foreach ([$filename, $encodedFilename, $decodedFilename] as $filenameVariant) {
            if (Storage::disk('public')->exists($filenameVariant)) {
                $resolvedPath = Storage::disk('public')->path($filenameVariant);
                \Illuminate\Support\Facades\Log::info("File found by filename in public disk root: {$resolvedPath}");
                return $resolvedPath;
            }
        }
        
        // Check in local disk root with various filename formats
        foreach ([$filename, $encodedFilename, $decodedFilename] as $filenameVariant) {
            if (Storage::disk('local')->exists($filenameVariant)) {
                $resolvedPath = Storage::disk('local')->path($filenameVariant);
                \Illuminate\Support\Facades\Log::info("File found by filename in local disk root: {$resolvedPath}");
                return $resolvedPath;
            }
        }
        
        // Check in common subdirectories where files might be stored
        $commonDirs = ['uploads', 'documents', 'files', 'attachments', 'employees', 'employee-files', 'employee-documents', 'employee_files', 'employee_documents', 'routing-slips', 'routing_slips'];
        
        // Add more specific employee-related directories that might be used
        $employeeDirs = ['employee', 'employees', 'staff', 'personnel', 'hr', 'human-resources'];
        foreach ($employeeDirs as $baseDir) {
            $commonDirs[] = $baseDir . '/files';
            $commonDirs[] = $baseDir . '/documents';
            $commonDirs[] = $baseDir . '/attachments';
            $commonDirs[] = $baseDir . '-files';
            $commonDirs[] = $baseDir . '-documents';
            $commonDirs[] = $baseDir . '_files';
            $commonDirs[] = $baseDir . '_documents';
        }
        foreach ($commonDirs as $dir) {
            foreach ([$filename, $encodedFilename, $decodedFilename] as $filenameVariant) {
                // Check in public disk
                if (Storage::disk('public')->exists("$dir/$filenameVariant")) {
                    $resolvedPath = Storage::disk('public')->path("$dir/$filenameVariant");
                    \Illuminate\Support\Facades\Log::info("File found in public disk common directory: {$resolvedPath}");
                    return $resolvedPath;
                }
                
                // Check in local disk
                if (Storage::disk('local')->exists("$dir/$filenameVariant")) {
                    $resolvedPath = Storage::disk('local')->path("$dir/$filenameVariant");
                    \Illuminate\Support\Facades\Log::info("File found in local disk common directory: {$resolvedPath}");
                    return $resolvedPath;
                }
            }
        }
        
        // Try checking in storage/app directory (not just public)
        $appStoragePath = storage_path("app/{$filename}");
        if (file_exists($appStoragePath)) {
            \Illuminate\Support\Facades\Log::info("File found in app storage path: {$appStoragePath}");
            return $appStoragePath;
        }
        
        // Try with Windows path format (backslashes) in case that's how it was stored
        $windowsStylePath = str_replace('/', '\\', $filePath);
        if (file_exists($windowsStylePath)) {
            \Illuminate\Support\Facades\Log::info("File found with Windows path format: {$windowsStylePath}");
            return $windowsStylePath;
        }
        
        // Try absolute path with XAMPP document root
        $xamppPath = 'c:\\xampp\\htdocs\\pms\\pms\\storage\\app\\public\\' . $filename;
        if (file_exists($xamppPath)) {
            \Illuminate\Support\Facades\Log::info("File found in XAMPP path: {$xamppPath}");
            return $xamppPath;
        }
        
        // NEW: Perform a recursive search in storage directories for the file
        $foundPath = $this->findFileRecursively(storage_path('app'), $originalFilename);
        if ($foundPath) {
            \Illuminate\Support\Facades\Log::info("File found through recursive search: {$foundPath}");
            return $foundPath;
        }
        
        // NEW: Also search in public directory
        $foundPath = $this->findFileRecursively(public_path(), $originalFilename);
        if ($foundPath) {
            \Illuminate\Support\Facades\Log::info("File found through recursive search in public: {$foundPath}");
            return $foundPath;
        }
        
        // Log failure for debugging
        \Illuminate\Support\Facades\Log::warning("Failed to resolve file path: {$filePath}");
        return false;
    }
    
    /**
     * Recursively search for a file in a directory
     *
     * @param string $directory Directory to search in
     * @param string $filename Filename to search for
     * @param int $maxDepth Maximum recursion depth
     * @return string|false Path to the file if found, false otherwise
     */
    private function findFileRecursively($directory, $filename, $maxDepth = 5)
    {
        if ($maxDepth <= 0) {
            return false;
        }
        
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }
        
        try {
            $files = scandir($directory);
            
            // First check for exact filename match
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                
                if (is_file($path) && (strtolower($file) === strtolower($filename))) {
                    return $path;
                }
            }
            
            // Then recursively check subdirectories
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                
                if (is_dir($path)) {
                    $result = $this->findFileRecursively($path, $filename, $maxDepth - 1);
                    if ($result) {
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Error during recursive file search: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get file content safely with improved error handling
     * Simplified for better Windows compatibility
     *
     * @param string $filePath
     * @return callable|false
     */
    private function getFileContent($filePath)
    {
        // Normalize path for Windows - ensure proper backslash handling
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        
        // Log the attempt
        \Illuminate\Support\Facades\Log::info("Attempting to access file: {$filePath}");
        
        // Basic file existence check
        if (!file_exists($filePath)) {
            \Illuminate\Support\Facades\Log::warning("File does not exist at path: {$filePath}");
            
            // Try case-insensitive match (important for Windows servers)
            $directory = dirname($filePath);
            $basename = basename($filePath);
            
            if (is_dir($directory) && is_readable($directory)) {
                $files = scandir($directory);
                foreach ($files as $file) {
                    if (strtolower($file) === strtolower($basename)) {
                        $correctedPath = $directory . DIRECTORY_SEPARATOR . $file;
                        \Illuminate\Support\Facades\Log::info("Found file with different case: {$correctedPath}");
                        return $this->getFileContent($correctedPath);
                    }
                }
            }
            
            return false;
        }
        
        // Check file permissions
        if (!is_readable($filePath)) {
            \Illuminate\Support\Facades\Log::warning("File exists but is not readable: {$filePath}");
            return false;
        }
        
        // Get file size for logging
        $fileSize = @filesize($filePath);
        \Illuminate\Support\Facades\Log::info("File found. Size: " . ($fileSize !== false ? $fileSize . ' bytes' : 'unknown') . " for {$filePath}");
        
        // Return a simple callable that will be used by Laravel's streamDownload
        return function() use ($filePath) {
            // Clear output buffers to prevent corruption
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set appropriate server settings for file downloads
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes should be enough for most files
            
            // Disable compression
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }
            ini_set('zlib.output_compression', '0');
            
            // Use direct file reading with binary mode for Windows compatibility
            $handle = @fopen($filePath, 'rb');
            
            if ($handle !== false) {
                // Stream file in chunks to handle large files efficiently
                while (!feof($handle) && connection_status() === CONNECTION_NORMAL) {
                    $buffer = fread($handle, 8192); // 8KB chunks
                    if ($buffer === false) {
                        break;
                    }
                    echo $buffer;
                    flush();
                }
                fclose($handle);
                \Illuminate\Support\Facades\Log::info("File served successfully: {$filePath}");
            } else {
                \Illuminate\Support\Facades\Log::error("Failed to open file for reading: {$filePath}");
                echo "Error: Unable to read the requested file. Please contact system administrator.";
            }
        };
    }
}
