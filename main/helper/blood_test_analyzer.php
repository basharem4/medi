<?php

class BloodTestAnalyzer {
    private $referenceData;

    public function __construct() {
        // Load the reference data
        $jsonFile = __DIR__ . '/blood_test_reference.json';
        $this->referenceData = json_decode(file_get_contents($jsonFile), true);
    }

    public function analyzeValue($testName, $value, $gender = null) {
        // Find the test in reference data
        $testData = null;
        foreach ($this->referenceData['blood_test_parameters'] as $parameter) {
            if ($parameter['test_name'] === $testName) {
                $testData = $parameter;
                break;
            }
        }

        if (!$testData) {
            return ["status" => "unknown", "message" => "Test type not found in reference data"];
        }

        // Get the normal range based on gender if applicable
        $normalRange = isset($testData['normal_range'][$gender]) 
            ? $testData['normal_range'][$gender] 
            : $testData['normal_range']['all'];

        // Parse the range values
        preg_match('/^([\d.]+)-([\d.]+)/', $normalRange, $matches);
        if (count($matches) < 3) {
            return ["status" => "error", "message" => "Could not parse normal range"];
        }

        $minNormal = floatval($matches[1]);
        $maxNormal = floatval($matches[2]);
        $value = floatval($value);

        // Determine the status and get appropriate message
        if ($value < $minNormal) {
            return [
                "status" => "low",
                "description" => $testData['low_range']['description'],
                "recommendation" => $testData['low_range']['recommendation'],
                "value" => $value,
                "normal_range" => $normalRange,
                "gender" => $gender
            ];
        } elseif ($value > $maxNormal) {
            return [
                "status" => "high",
                "description" => $testData['high_range']['description'],
                "recommendation" => $testData['high_range']['recommendation'],
                "value" => $value,
                "normal_range" => $normalRange,
                "gender" => $gender
            ];
        } else {
            return [
                "status" => "normal",
                "description" => "Value is within normal range.",
                "recommendation" => "Continue regular health monitoring.",
                "value" => $value,
                "normal_range" => $normalRange,
                "gender" => $gender
            ];
        }
    }

    public function analyzeResults($results, $gender = 'men') {
        $analysis = [];
        foreach ($results as $testName => $value) {
            // Determine if this test needs gender-specific ranges
            $needsGender = in_array($testName, [
                'Hemoglobin (Hb)',
                'Red Blood Cell Count (RBC)',
                'Hematocrit (HCT)'
            ]);
            
            // Only pass gender if the test needs it
            $analysis[$testName] = $this->analyzeValue(
                $testName, 
                $value, 
                $needsGender ? $gender : null
            );
        }
        return $analysis;
    }
} 