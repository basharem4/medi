<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class BloodTestPDFParser
{
    private $parser;

    public function __construct()
    {
        $this->parser = new \Smalot\PdfParser\Parser();
    }

    public function extractValues($pdfPath)
    {
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $text = $pdf->getText();

            // Initialize results array
            $results = [];

            // Define patterns to match test values
            $patterns = [
                'Hemoglobin \(Hb\):\s*([\d.]+)\s*g\/dL',
                'White Blood Cell Count \(WBC\):\s*([\d.]+)\s*x10\^9\/L',
                'Red Blood Cell Count \(RBC\):\s*([\d.]+)\s*x10\^12\/L',
                'Platelet Count \(PLT\):\s*([\d.]+)\s*x10\^9\/L',
                'Hematocrit \(HCT\):\s*([\d.]+)\s*%',
                'Mean Corpuscular Volume \(MCV\):\s*([\d.]+)\s*fL',
                'Mean Corpuscular Hemoglobin \(MCH\):\s*([\d.]+)\s*pg'
            ];

            $testNames = [
                "Hemoglobin (Hb)",
                "White Blood Cell Count (WBC)",
                "Red Blood Cell Count (RBC)",
                "Platelet Count (PLT)",
                "Hematocrit (HCT)",
                "Mean Corpuscular Volume (MCV)",
                "Mean Corpuscular Hemoglobin (MCH)"
            ];

            // Extract values using regex
            foreach ($patterns as $index => $pattern) {
                if (preg_match('/' . $pattern . '/', $text, $matches)) {
                    if (isset($matches[1]) && is_numeric($matches[1])) {
                        $results[$testNames[$index]] = floatval($matches[1]);
                    }
                }
            }

            // Check if we found any values
            if (empty($results)) {
                throw new Exception('No test values could be extracted from the PDF');
            }

            return $results;
        } catch (Exception $e) {
            throw new Exception('Error parsing PDF: ' . $e->getMessage());
        }
    }
}
