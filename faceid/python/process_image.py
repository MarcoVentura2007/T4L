import sys
import os
import json
import warnings

# ---- DEBUG INTERPRETE (solo su stderr) ----
print(f"PYTHON USATO: {sys.executable}", file=sys.stderr)

# ---- SOPPRESSIONE WARNING ----
warnings.filterwarnings("ignore")
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

try:
    print("DEBUG: Starting process_image.py", file=sys.stderr)

    from deepface import DeepFace
    print("DEBUG: DeepFace imported", file=sys.stderr)

    # ---- CONTROLLO ARGOMENTI ----
    if len(sys.argv) < 2:
        raise Exception("Nessuna immagine fornita")

    image_path = sys.argv[1]
    print(f"DEBUG: Image path: {image_path}", file=sys.stderr)

    if not os.path.exists(image_path):
        raise Exception("File immagine non trovato")

    # ---- DIRECTORY VOLTI NOTI ----
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    KNOWN_DIR = os.path.join(BASE_DIR, "known_faces")

    print(f"DEBUG: Known faces dir: {KNOWN_DIR}", file=sys.stderr)

    if not os.path.exists(KNOWN_DIR):
        raise Exception("Cartella known_faces mancante")

    known_images = []
    known_names = []

    for filename in os.listdir(KNOWN_DIR):
        if filename.lower().endswith((".jpg", ".jpeg", ".png")):
            file_path = os.path.join(KNOWN_DIR, filename)
            known_images.append(file_path)
            known_names.append(os.path.splitext(filename)[0])
            print(f"DEBUG: Found known face: {filename}", file=sys.stderr)

    print(f"DEBUG: Total known faces: {len(known_images)}", file=sys.stderr)

    if not known_images:
        raise Exception("Nessuna immagine valida in known_faces")

    # ---- VERIFICA ----
    found_match = False
    match_name = None

    for i, known_img in enumerate(known_images):
        try:
            print(f"DEBUG: Verifying against {known_names[i]}", file=sys.stderr)

            result = DeepFace.verify(
                img1_path=image_path,
                img2_path=known_img,
                model_name="Facenet",
                enforce_detection=True,
                detector_backend="retinaface"  # più robusto di opencv
            )

            print(f"DEBUG: Verification result: {result}", file=sys.stderr)

            if result.get("verified", False):
                found_match = True
                match_name = known_names[i]
                print(f"DEBUG: Match found: {match_name}", file=sys.stderr)
                break

        except Exception as e:
            print(f"DEBUG: Verification error: {e}", file=sys.stderr)
            continue

    # ---- OUTPUT JSON PURO ----
    if found_match:
        output = {"known": True, "name": match_name}
    else:
        output = {"known": False, "name": None}

    print(json.dumps(output))

except Exception as e:
    print(f"DEBUG: Global exception: {e}", file=sys.stderr)
    print(json.dumps({"known": False, "error": str(e)}))
