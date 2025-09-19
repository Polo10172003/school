function validateParentsForm() {
    let isValid = true;
    let messages = [];

    // Father's info
    const fatherLast = document.getElementById("father_lastname").value.trim();
    const fatherFirst = document.getElementById("father_firstname").value.trim();
    const fatherMobile = document.getElementById("father_mobnumber").value.trim();
    const fatherEmail = document.getElementById("father_emailaddress").value.trim();

    if (fatherLast === "" || fatherFirst === "") {
        messages.push("Father's last name and first name are required.");
        isValid = false;
    }
    if (fatherMobile === "" || !/^09\d{9}$/.test(fatherMobile)) {
        messages.push("Father's mobile number must be valid (e.g. 09XXXXXXXXX).");
        isValid = false;
    }
    if (fatherEmail === "" || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(fatherEmail)) {
        messages.push("Father's email must be valid.");
        isValid = false;
    }

    // Mother's info
    const motherLast = document.getElementById("mother_lastname").value.trim();
    const motherFirst = document.getElementById("mother_firstname").value.trim();
    const motherMobile = document.getElementById("mother_mobnumber").value.trim();
    const motherEmail = document.getElementById("mother_emailaddress").value.trim();

    if (motherLast === "" || motherFirst === "") {
        messages.push("Mother's last name and first name are required.");
        isValid = false;
    }
    if (motherMobile === "" || !/^09\d{9}$/.test(motherMobile)) {
        messages.push("Mother's mobile number must be valid.");
        isValid = false;
    }
    if (motherEmail === "" || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(motherEmail)) {
        messages.push("Mother's email must be valid.");
        isValid = false;
    }

    // Guardian's info
    const guardianLast = document.getElementById("guardian_lastname").value.trim();
    const guardianFirst = document.getElementById("guardian_firstname").value.trim();
    const guardianMobile = document.getElementById("guardian_mobnumber").value.trim();
    const guardianEmail = document.getElementById("guardian_emailaddress").value.trim();

    if (guardianLast === "" || guardianFirst === "") {
        messages.push("Guardian's last name and first name are required.");
        isValid = false;
    }
    if (guardianMobile === "" || !/^09\d{9}$/.test(guardianMobile)) {
        messages.push("Guardian's mobile number must be valid (e.g. 09XXXXXXXXX).");
        isValid = false;
    }
    if (guardianEmail === "" || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(guardianEmail)) {
        messages.push("Guardian's email must be valid.");
        isValid = false;
    }

    // Show all errors
    if (!isValid) {
        alert(messages.join("\n"));
    }

    return isValid;
}