<?php
include_once 'includes/head.php';
include_once 'includes/nav.php';
?>
<main>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" id="reportTitle">Test Analysis Results</h4>
                            <div>
                                <button class="btn" id="printReport">
                                    <i class="fas fa-print mr-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="resultsContainer">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-3">Loading your test results...</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include_once 'includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const resultsContainer = document.getElementById('resultsContainer');
        const reportTitle = document.getElementById('reportTitle');
        const printButton = document.getElementById('printReport');

        // Get the test results from sessionStorage
        const storedResults = sessionStorage.getItem('testResults');

        if (storedResults) {
            try {
                const data = JSON.parse(storedResults);
                const testType = data.testType;
                const timestamp = new Date(data.timestamp).toLocaleString();
                const results = data.results;

                // Update the report title
                reportTitle.textContent = `${testType} Analysis Results`;

                // Create the report content
                let reportHtml = `
                <div class="report-header mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Report Details</h5>
                            <p><strong>Test Type:</strong> ${testType}</p>
                            <p><strong>Generated On:</strong> ${timestamp}</p>
                            <p><strong>Report ID:</strong> ${generateReportId()}</p>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <img src="assets/img/medicallogo.png" alt="Logo" style="max-height: 60px;">
                            <p class="mt-2">Medical Test Analysis</p>
                            <p>Your Health, Our Priority</p>
                        </div>
                    </div>
                    <hr>
                </div>
                
                <div class="report-summary mb-4">
                    <h5>Summary</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h6>Normal Values</h6>
                                    <div class="display-4" id="normalCount">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning mb-3">
                                <div class="card-body text-center">
                                    <h6>Low Values</h6>
                                    <div class="display-4" id="lowCount">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white mb-3">
                                <div class="card-body text-center">
                                    <h6>High Values</h6>
                                    <div class="display-4" id="highCount">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="report-details">
                    <h5>Detailed Analysis</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Test Name</th>
                                    <th>Your Value</th>
                                    <th>Normal Range</th>
                                    <th>Status</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>`;

                // Counters for summary
                let normalCount = 0;
                let lowCount = 0;
                let highCount = 0;

                // Add each test result to the table
                Object.entries(results.analysis).forEach(([testName, analysis]) => {
                    const statusClass = analysis.status === 'normal' ? 'text-success' :
                        analysis.status === 'high' ? 'text-danger' : 'text-warning';
                    const statusText = analysis.status === 'normal' ? 'Normal' :
                        analysis.status === 'high' ? 'High' : 'Low';

                    // Update counters
                    if (analysis.status === 'normal') normalCount++;
                    else if (analysis.status === 'high') highCount++;
                    else lowCount++;

                    reportHtml += `
                    <tr>
                        <td><strong>${testName}</strong></td>
                        <td class="${statusClass} font-weight-bold">${analysis.value}</td>
                        <td>${analysis.normal_range}</td>
                        <td class="${statusClass}">${statusText}</td>
                        <td>${analysis.recommendation}</td>
                    </tr>
                    <tr class="bg-light">
                        <td colspan="5">
                            <small><strong>Description:</strong> ${analysis.description}</small>
                        </td>
                    </tr>`;
                });

                reportHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="report-footer mt-4">
                    <div class="alert alert-info">
                        <h6>Important Note</h6>
                        <p>This analysis is based on general medical guidelines and should not replace professional medical advice. 
                        Please consult with your healthcare provider for a complete evaluation of your health status.</p>
                    </div>
                </div>`;

                // Update the results container
                resultsContainer.innerHTML = reportHtml;

                // Update the summary counters
                document.getElementById('normalCount').textContent = normalCount;
                document.getElementById('lowCount').textContent = lowCount;
                document.getElementById('highCount').textContent = highCount;

                // Set up print functionality
                printButton.addEventListener('click', function() {
                    window.print();
                });

            } catch (error) {
                console.error('Error processing stored results:', error);
                resultsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Error</h5>
                    <p>Could not load test results: ${error.message}</p>
                </div>
            `;
            }
        } else {
            // Check if there's an ID in the URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const resultId = urlParams.get('id');

            if (resultId) {
                resultsContainer.innerHTML = `
                <div class="alert alert-warning">
                    <h5 class="alert-heading">Results Not Found</h5>
                    <p>The test results you're looking for (ID: ${resultId}) could not be found. This could be because:</p>
                    <ul>
                        <li>The results have expired</li>
                        <li>You're using a different browser than the one used for the test</li>
                        <li>Your browser's session storage has been cleared</li>
                    </ul>
                    <p>Please try analyzing your test again.</p>
                </div>
            `;
            } else {
                resultsContainer.innerHTML = `
                <div class="alert alert-warning">
                    <h5 class="alert-heading">No Results Found</h5>
                    <p>Please upload or scan a test report to view analysis results.</p>
                    <a href="index.php#tests" class="btn btn-primary mt-3">Go to Test Upload</a>
                </div>
            `;
            }
        }

        // Helper function to generate a random report ID
        function generateReportId() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let id = 'MTA-';
            for (let i = 0; i < 8; i++) {
                id += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return id;
        }
    });
</script>
<style>
    @media print {

        .btn,
        .navbar,
        footer,
        .text-center.mt-4 {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
    }

    /* Custom green styling */
    .btn-success {
        background-color: #234821 !important;
        border-color: #234821 !important;
    }

    .btn-success:hover {
        background-color: #5a9d3d !important;
        border-color: #5a9d3d !important;
    }

    .card-header {
        background-color: #234821 !important;
    }

    /* Ensure report title is white */
    #reportTitle {
        color: white !important;
    }
</style>