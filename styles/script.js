//Profile dropdown
document.addEventListener("DOMContentLoaded", function () {
    const profileDropdown = document.querySelector(".profile-dropdown");
    const dropdownContent = document.querySelector(".dropdown-content");

    profileDropdown.addEventListener("mouseenter", function () {
        dropdownContent.style.display = "block";
        dropdownContent.style.transition = "opacity 0.3s ease-in-out";
        dropdownContent.style.opacity = 1;
    });

    profileDropdown.addEventListener("mouseleave", function () {
        dropdownContent.style.transition = "opacity 0.3s ease-in-out";
        dropdownContent.style.opacity = 0;
        setTimeout(function () {
            dropdownContent.style.display = "none";
        }, 300); // Wait for the fade-out effect before hiding
    });
});



//Navigation and Profile 
document.addEventListener("DOMContentLoaded", function () {
    const dashboardOverview = document.querySelector(".overview");
    const profileSection = document.getElementById("profile-section");
    const sections = document.querySelectorAll(".section");

    function toggleSections(sectionId) {
        // Hide all sections
        sections.forEach(section => section.classList.add("collapsed"));
        if (dashboardOverview) dashboardOverview.style.display = "none";
        if (profileSection) profileSection.style.display = "none";

        // Show the selected section
        const sectionToShow = document.getElementById(sectionId);
        if (sectionToShow) sectionToShow.classList.remove("collapsed");
    }

    // Home link - show dashboard
    const homeLink = document.querySelector(".nav-link[href='code.php']");
    if (homeLink) {
        homeLink.addEventListener("click", function (e) {
            e.preventDefault();
            if (dashboardOverview) dashboardOverview.style.display = "block";
            if (profileSection) profileSection.style.display = "none";
            sections.forEach(section => section.classList.add("collapsed"));
        });
    }

    // Profile link
    const profileLink = document.getElementById("my-profile");
    if (profileLink) {
        profileLink.addEventListener("click", function () {
            if (dashboardOverview) dashboardOverview.style.display = "none";
            if (profileSection) profileSection.style.display = "block";
            sections.forEach(section => section.classList.add("collapsed"));
        });
    }

    // Sidebar navigation links
    const navItems = {
        "view-clients": "clients-section",
        "view-admins": "admins-section",
        "view-payments": "payments-section",
        "view-tokens": "tokens-section",
        "view-logs": "logs-section"
    };

    for (let linkId in navItems) {
        const link = document.getElementById(linkId);
        if (link) {
            link.addEventListener("click", () => {
                toggleSections(navItems[linkId]);
            });
        }
    }
});


//Fade Effects 
document.addEventListener("DOMContentLoaded", function () {
    const topBar = document.querySelector(".top-bar");
    const dashboardContent = document.querySelector(".dashboard-content");

    dashboardContent.addEventListener("scroll", function () {
        if (dashboardContent.scrollTop > 50) {
            topBar.classList.add("scrolled"); // Fade effect when scrolling down
        } else {
            topBar.classList.remove("scrolled"); // Restore when at the top
        }
    });
});


//My profile nav toggles 
function showSection(section) {
// Hide all sections
const sections = document.querySelectorAll('.section-content');
sections.forEach((section) => {
    section.style.display = 'none';
});

// Remove active class from all navbar buttons
const buttons = document.querySelectorAll('.nav-btn');
buttons.forEach((btn) => {
    btn.classList.remove('active');
});

// Show the clicked section
document.getElementById(section).style.display = 'block';

// Add active class to the clicked button
const activeBtn = document.querySelector(`.nav-btn[onclick="showSection('${section}')"]`);
activeBtn.classList.add('active');
}



//table search 
function searchTable(tableId, searchKeyId, searchInputId) {
    let key = document.getElementById(searchKeyId).value;
    let input = document.getElementById(searchInputId).value.toLowerCase();
    let rows = document.querySelectorAll(`#${tableId} tbody tr`);

    rows.forEach(row => {
        let cell = row.querySelector(`[data-key="${key}"]`);
        row.style.display = cell && cell.textContent.toLowerCase().includes(input) ? "" : "none";
    });
}


//my profile-image
document.addEventListener("DOMContentLoaded", function () {
    const profileImg = document.getElementById("profile_image-img");
    const defaultProfileIcon = document.getElementById("default-profile_image");
    const fileInput = document.getElementById("profile_image-input");
    const uploadButton = document.getElementById("upload-profile_image");

    // Clicking the profile image or default icon opens the file input
    [profileImg, defaultProfileIcon].forEach(element => {
        if (element) element.addEventListener("click", () => fileInput.click());
    });

    // When a file is selected, preview it and upload
    fileInput.addEventListener("change", function () {
        if (fileInput.files.length === 0) return; // No file selected

        const file = fileInput.files[0];

        // Check file type
        const allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
        if (!allowedTypes.includes(file.type)) {
            alert("Only JPG, JPEG, and PNG files are allowed.");
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            if (profileImg) {
                profileImg.src = e.target.result;
            } else {
                const newImg = document.createElement("img");
                newImg.src = e.target.result;
                newImg.className = "file-view";
                newImg.id = "profile_image-img";
                defaultProfileIcon.replaceWith(newImg);
                newImg.addEventListener("click", () => fileInput.click());
            }
        };
        reader.readAsDataURL(file);

        uploadProfileImage(file);
    });

    // Function to upload profile image
    async function uploadProfileImage(file) {
        try {
            let formData = new FormData();
            formData.append("profile_image", file);

            let response = await fetch("includes/upload_profile.php", {
                method: "POST",
                body: formData,
            });

            let result = await response.text();
            alert(result); // Show response message
            location.reload(); // Refresh page to reflect the new profile image
        } catch (error) {
            console.error("Upload error:", error);
        }
    }
});


//View Client 
function loadClientData(clientId) {
    // Make an AJAX request to fetch client data based on clientId
    $.ajax({
        url: 'includes/get_client_data.php', // This should be the PHP file to query the database
        method: 'GET',
        data: { client_id: clientId },
        success: function(response) {
            // Parse the response (assumed to be in JSON format)
            var client = JSON.parse(response);
            
            if(client.error) {
                alert(client.error); // Handle if client not found
            } else {
                // Populate modal fields with client data
                $('#clientId').text(client.client_id);
                $('#clientName').text(client.name);
                $('#clientEmail').text(client.email);
                $('#clientAddress').text(client.address);
                $('#clientMeterNo').text(client.meter_no);
            }
        }
    });
}

//edit client View input
function loadEditClientData(clientId) {
    // Make an AJAX request to fetch client data based on clientId
    $.ajax({
        url: 'includes/get_client_data.php', // This should be the PHP file to query the database
        method: 'GET',
        data: { client_id: clientId },
        success: function(response) {
            // Parse the response (assumed to be in JSON format)
            var client = JSON.parse(response);
            
            if(client.error) {
                alert(client.error); // Handle if client not found
            } else {
                // Populate modal input fields with client data
                $('#edit_client_id').val(client.client_id);
                $('#edit_name').val(client.name);
                $('#edit_email').val(client.email);
                $('#edit_address').val(client.address);
                $('#edit_meter_no').val(client.meter_no);
            }
        }
    });
}


