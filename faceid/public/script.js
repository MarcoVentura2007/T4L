console.log("script.js caricato");

const video  = document.getElementById("video");
const canvas = document.getElementById("canvas");
const snap   = document.getElementById("snap");
const output = document.getElementById("output");

// ---- WEBCAM ----
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
    })
    .catch(err => {
        console.error("Errore accesso webcam:", err);
        output.textContent = "Errore accesso webcam";
    });

// ---- SCATTO ----
snap.addEventListener("click", () => {
    const ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(blob => {
        const formData = new FormData();
        formData.append("image", blob, "photo.png");

        fetch("upload.php", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    output.textContent = "Errore server: " + data.error;
                    console.error(data);
                    return;
                }
                if (!data.result) {
                    output.textContent = "Risposta non valida dal server";
                    console.error(data);
                    return;
                }

                if (data.result.known) {
                    output.textContent = "VOLTO RICONOSCIUTO: " + data.result.name;
                } else {
                    output.textContent = "VOLTO NON RICONOSCIUTO";
                }
            })
            .catch(err => {
                console.error(err);
                output.textContent = "Errore comunicazione server";
            });
    }, "image/png");
});
