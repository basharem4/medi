document.addEventListener("DOMContentLoaded", function () {
  // Disable Dropzone auto discover
  Dropzone.autoDiscover = false;

  // Find all test upload areas
  const testAreas = document.querySelectorAll(".test-upload-area");
  console.log("Found test areas:", testAreas.length);

  testAreas.forEach((area, index) => {
    // Find the test type from the closest single-cat div
    const testContainer = area.closest(".single-cat");
    if (!testContainer) {
      console.error("Could not find parent container with class single-cat");
      return;
    }

    const testTypeElement = testContainer.querySelector("h5");
    if (!testTypeElement) {
      console.error("Could not find h5 element with test type");
      return;
    }

    const testType = testTypeElement.textContent.trim();
    console.log("Processing test area for:", testType);

    // Find the form element that's already in the HTML
    const form = area.querySelector("form.dropzone");
    if (!form) {
      console.error("Could not find dropzone form element");
      return;
    }

    // Remove any existing Dropzone instance
    if (form.dropzone) {
      form.dropzone.destroy();
    }

    // Initialize Dropzone on the existing form
    const dropzone = new Dropzone(form, {
      url: "/medicaltests/main/helper/analyze_test.php",
      acceptedFiles: ".jpg,.jpeg,.png,.pdf",
      autoProcessQueue: false,
      maxFiles: 1,
      paramName: "file",
      params: {
        test_type: testType,
      },
      headers: {
        Accept: "application/json",
      },
      init: function () {
        const dz = this;
        const uploadArea = dz.element.closest(".test-upload-area");
        const button = uploadArea.querySelector(".analyze-btn");
        const resultArea = uploadArea.querySelector(".test-results");

        if (!button) {
          console.error("Analyze button not found");
          return;
        }

        this.on("addedfile", function (file) {
          if (this.files.length > 1) {
            this.removeFile(this.files[0]);
          }
          // Enable the analyze button when a file is added
          button.disabled = false;
        });

        // Set up event handlers once
        this.on("sending", function (file, xhr, formData) {
          // Clear any existing test_type to prevent duplicates
          formData.delete("test_type");
          // Add test type to form data
          formData.append("test_type", testType);

          // Log the form data for debugging
          console.log("Form data being sent:", {
            test_type: testType,
            file_name: file.name,
            file_type: file.type,
          });

          // Log the entire FormData contents
          console.log("FormData entries:");
          for (let pair of formData.entries()) {
            console.log(pair[0] + ": " + pair[1]);
          }
        });

        this.on("success", function (file, response) {
          try {
            // Check if response is already an object
            const results =
              typeof response === "string" ? JSON.parse(response) : response;

            if (results.success) {
              // Create modal for results if it doesn't exist
              const resultModalId = `resultModal-${testType
                .replace(/\s+/g, "-")
                .toLowerCase()}`;
              let resultModal = document.getElementById(resultModalId);

              if (!resultModal) {
                const modalHtml = `
<div class="modal fade" id="${resultModalId}" tabindex="-1" role="dialog" aria-labelledby="${resultModalId}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6ab04c; color: white;">
                <h5 class="modal-title" id="${resultModalId}Label">${testType} Analysis Results</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="results-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn " data-dismiss="modal">Close</button>
                <a href="results.php?id=${encodeURIComponent(
                  Date.now()
                )}" class="btn">View Full Report</a>
            </div>
        </div>
    </div>
</div>`;
                document.body.insertAdjacentHTML("beforeend", modalHtml);
                resultModal = document.getElementById(resultModalId);
              }

              // Format the analysis results
              let analysisHtml = '<div class="results-summary mb-4">';
              analysisHtml += `<h6 class="mb-3">Analysis completed on ${new Date().toLocaleString()}</h6>`;
              analysisHtml += "</div>";

              // Add a container for the results
              analysisHtml += '<div class="results-details">';

              // Iterate through each test result
              Object.entries(results.data.analysis).forEach(
                ([testName, analysis]) => {
                  const statusClass =
                    analysis.status === "normal"
                      ? "text-success"
                      : analysis.status === "high"
                      ? "text-danger"
                      : "text-warning";
                  const statusBg =
                    analysis.status === "normal"
                      ? "bg-light"
                      : analysis.status === "high"
                      ? "bg-danger text-white"
                      : "bg-warning";

                  analysisHtml += `
    <div class="test-result card mb-3">
        <div class="card-header ${statusBg}">
            <strong>${testName}</strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Value:</strong> <span class="${statusClass} font-weight-bold">${analysis.value}</span></p>
                    <p><strong>Normal Range:</strong> ${analysis.normal_range}</p>
                </div>
                <div class="col-md-6">
                    <p>${analysis.description}</p>
                    <p><strong>Recommendation:</strong> ${analysis.recommendation}</p>
                </div>
            </div>
        </div>
    </div>
    `;
                }
              );

              analysisHtml += "</div>";

              // Update the modal content
              const resultsContainer =
                resultModal.querySelector(".results-container");
              resultsContainer.innerHTML = analysisHtml;

              // Store results in sessionStorage for the results page
              sessionStorage.setItem(
                "testResults",
                JSON.stringify({
                  testType: testType,
                  timestamp: Date.now(),
                  results: results.data,
                })
              );

              // Show the modal
              $(resultModal).modal("show");

              // Also update the inline results area with a summary
              resultArea.innerHTML = `
<div class="alert alert-success">
    <h6>Analysis Complete!</h6>
    <p>Your ${testType} has been analyzed successfully.</p>
    <button class="btn btn-sm btn-primary view-results-btn" data-modal="${resultModalId}">View Results</button>
</div>`;

              // Add event listener to the view results button
              const viewResultsBtn =
                resultArea.querySelector(".view-results-btn");
              if (viewResultsBtn) {
                viewResultsBtn.addEventListener("click", function () {
                  const modalId = this.getAttribute("data-modal");
                  $(`#${modalId}`).modal("show");
                });
              }
            } else {
              throw new Error(results.message || "Analysis failed");
            }
          } catch (error) {
            console.error("Error processing response:", error);
            resultArea.innerHTML = `
<div class="alert alert-danger">
    <p>Error: ${error.message}</p>
</div>
`;
          }

          // Reset button state and clear the queue
          button.disabled = false;
          button.textContent = "Analyze";
          dz.removeAllFiles();
        });

        this.on("error", function (file, errorMessage) {
          resultArea.innerHTML = `
<div class="alert alert-danger">
    <p>Error: ${errorMessage}</p>
</div>
`;

          // Reset button state
          button.disabled = false;
          button.textContent = "Analyze";
        });

        // Disable analyze button initially
        button.disabled = true;

        button.addEventListener("click", function () {
          if (dz.files.length === 0) {
            alert("Please upload or capture a test report first");
            return;
          }

          // Show loading state
          button.disabled = true;
          button.textContent = "Analyzing...";

          // Process the queue to start the upload
          dz.processQueue();
        });

        // Setup camera functionality if needed
        setupCameraModal(dz);
      },
    });
  });

  // Function to get test type from element ID
  function getTestType(element) {
    // Find the closest parent with class 'single-cat' and get the test type from the h5
    const testContainer = element.closest(".single-cat");
    if (!testContainer) {
      console.error("Could not find parent with class single-cat");
      return null;
    }

    const testTypeElement = testContainer.querySelector("h5");
    if (!testTypeElement) {
      console.error("Could not find h5 element with test type");
      return null;
    }

    const testType = testTypeElement.textContent.trim();
    console.log("Found test type:", testType); // Debug log
    return testType;
  }

  // Function to setup camera modal for a dropzone instance
  function setupCameraModal(dropzone) {
    const modalId = "cameraModal";
    const modalHtml = `
<div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Take Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <video style="width: 100%" autoplay playsinline></video>
                <canvas style="display: none;"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary capture-btn">Capture</button>
            </div>
        </div>
    </div>
</div>
`;

    // Add modal to document if it doesn't exist
    if (!document.getElementById(modalId)) {
      document.body.insertAdjacentHTML("beforeend", modalHtml);
    }

    const modal = document.getElementById(modalId);
    const video = modal.querySelector("video");
    const canvas = modal.querySelector("canvas");
    const captureBtn = modal.querySelector(".capture-btn");
    const cameraBtn = dropzone.element
      .closest(".test-upload-area")
      .querySelector(".camera-btn");

    if (cameraBtn) {
      // Handle camera button click
      cameraBtn.addEventListener("click", function () {
        $(modal).modal("show");
        initializeCamera(video);
      });

      // Handle capture button click
      captureBtn.addEventListener("click", async function () {
        const file = await captureImage(video, canvas);
        dropzone.addFile(file);
        $(modal).modal("hide");

        // Stop camera stream
        const stream = video.srcObject;
        if (stream) {
          stream.getTracks().forEach((track) => track.stop());
        }
      });

      // Clean up camera stream when modal is closed
      $(modal).on("hidden.bs.modal", function () {
        const stream = video.srcObject;
        if (stream) {
          stream.getTracks().forEach((track) => track.stop());
        }
      });
    }
  }

  // Function to initialize camera
  async function initializeCamera(videoElement) {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      videoElement.srcObject = stream;
    } catch (error) {
      console.error("Error accessing camera:", error);
      alert(
        "Could not access camera. Please make sure you have granted camera permissions."
      );
    }
  }

  // Function to capture image from video stream
  function captureImage(video, canvas) {
    return new Promise((resolve) => {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      canvas.getContext("2d").drawImage(video, 0, 0);

      canvas.toBlob((blob) => {
        const file = new File([blob], "camera-capture.jpg", {
          type: "image/jpeg",
        });
        resolve(file);
      }, "image/jpeg");
    });
  }
});
