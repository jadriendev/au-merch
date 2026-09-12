function toggleSize(radio) {
    if (radio.dataset.checked === "true") {
        radio.checked = false;
        radio.dataset.checked = "false";
    } else {
        document.querySelectorAll('input[name="size"]').forEach(input => {
            input.dataset.checked = "false";
        });

        radio.checked = true;
        radio.dataset.checked = "true";
    }
}

function toggleColor(radio) {
    if (radio.dataset.checked === "true") {
        radio.checked = false;
        radio.dataset.checked = "false";
    } else {
        document.querySelectorAll('input[name="color"]').forEach(input => {
            input.dataset.checked = "false";
        });

        radio.checked = true;
        radio.dataset.checked = "true";
    }
}