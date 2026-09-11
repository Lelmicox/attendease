import QrScanner from "./qr-scanner.min.js"; 
QrScanner.WORKER_PATH = "../assets/js/qr-scanner-worker.min.js";


const videoElem = document.getElementById("preview");

const qrScanner = new QrScanner(videoElem, result => {
    console.log("QR Code scanned:", result);

    // Capture location
    navigator.geolocation.getCurrentPosition(position => {
        let lat = position.coords.latitude;
        let long = position.coords.longitude;

        // Send to backend
        fetch("../api/mark_attendance.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                course_id: result,   // QR encodes course_id
                latitude: lat,
                longitude: long
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
        })
        .catch(err => console.error(err));
    });
});

qrScanner.start();
