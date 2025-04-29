<?php
header('Content-Type: application/json');
require_once __DIR__ . '/blood_test_analyzer.php';
require_once __DIR__ . '/sugar_test_analyzer.php';
require_once __DIR__ . '/blood_test_parser.php';
require_once __DIR__ . '/sugar_test_parser.php';

try {
    // Log the received data
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));

    // Check if file was uploaded
    if (empty($_FILES)) {
        throw new Exception('No files were uploaded');
    }

    if (!isset($_FILES['file'])) {
        throw new Exception('File upload field "file" not found');
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        );
        $errorMessage = isset($uploadErrors[$_FILES['file']['error']])
            ? $uploadErrors[$_FILES['file']['error']]
            : 'Unknown upload error';
        throw new Exception($errorMessage);
    }

    if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
        throw new Exception('File was not uploaded via HTTP POST');
    }

    // Get test type
    if (!isset($_POST['test_type'])) {
        throw new Exception('Test type not specified');
    }

    $testType = $_POST['test_type'];
    error_log("Test type received: " . $testType);

    if (empty($testType)) {
        throw new Exception('Test type cannot be empty');
    }

    // Validate file type
    $allowedTypes = ['application/pdf'];
    $fileType = $_FILES['file']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only PDF files are allowed');
    }

    // Process based on test type
    switch (strtolower(trim($testType))) {
        case 'blood test':
            $parser = new BloodTestPDFParser();
            $analyzer = new BloodTestAnalyzer();
            $gender = isset($_POST['gender']) ? $_POST['gender'] : 'men';
            $extractedData = $parser->extractValues($_FILES['file']['tmp_name']);
            $analysis = $analyzer->analyzeResults($extractedData, $gender);
            break;

        case 'sugar test':
            $parser = new SugarTestPDFParser();
            $analyzer = new SugarTestAnalyzer();
            $extractedData = $parser->extractValues($_FILES['file']['tmp_name']);
            $analysis = $analyzer->analyzeResults($extractedData);
            break;

        default:
            throw new Exception('Invalid test type. Supported types are: Blood Test, Sugar Test');
    }

    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'test_type' => $testType,
            'analysis' => $analysis,
            'extracted_values' => $extractedData,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in analyze_test.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
