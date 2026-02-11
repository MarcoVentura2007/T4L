import sys
import os
import json
import warnings

# ---- SOPPRESSIONE WARNING GENERALI ----
warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'  # Nasconde warning TensorFlow

try:
    print("DEBUG: Starting process_image.py", file=sys.stderr)
    from deepface import DeepFace
    print("DEBUG: DeepFace imported", file=sys.stderr)

    # ---- CONTROLLO ARGOMENTI ----
    if len(sys.argv) < 2:
        print("DEBUG: No image provided", file=sys.stderr)
        print(json.dumps({"known": False, "error": "Nessuna immagine fornita"}))
        sys.exit(0)

    image_path = sys.argv[1]
    print(f"DEBUG: Image path: {image_path}", file=sys.stderr)

    if not os.path.exists(image_path):
        print("DEBUG: Image file not found", file=sys.stderr)
        print(json.dumps({"known": False, "error": "File immagine non trovato"}))
        sys.exit(0)

    # ---- DIRECTORY VOLTI NOTI ----
    BASE_DIR = os.path.dirname(__file__)
    KNOWN_DIR = os.path.join(BASE_DIR, "known_faces")
    print(f"DEBUG: Known faces dir: {KNOWN_DIR}", file=sys.stderr)

    if not os.path.exists(KNOWN_DIR):
        print("DEBUG: Known faces dir not found", file=sys.stderr)
        print(json.dumps({"known": False, "error": "Cartella known_faces mancante"}))
        sys.exit(0)

    known_images = []
    known_names = []

    for filename in os.listdir(KNOWN_DIR):
        file_path = os.path.join(KNOWN_DIR, filename)
        known_images.append(file_path)
        known_names.append(os.path.splitext(filename)[0])
        print(f"DEBUG: Found known face: {filename}", file=sys.stderr)

    print(f"DEBUG: Total known faces: {len(known_images)}", file=sys.stderr)

    # ---- VERIFICA VOLTO ----
    found_match = False
    match_name = None

    for i, known_img in enumerate(known_images):
        try:
            print(f"DEBUG: Verifying against {known_names[i]}", file=sys.stderr)
            result = DeepFace.verify(
                img1_path=image_path,
                img2_path=known_img,
                model_name='Facenet',   # opzioni: Facenet, VGG-Face, ArcFace, OpenFace
                enforce_detection=True
            )
            print(f"DEBUG: Verification result: {result}", file=sys.stderr)
            if result["verified"]:
                found_match = True
                match_name = known_names[i]
                print(f"DEBUG: Match found: {match_name}", file=sys.stderr)
                break
        except Exception as e:
            print(f"DEBUG: Exception during verification: {e}", file=sys.stderr)
            continue

    if found_match:
        print(f"DEBUG: Final result: known=True, name={match_name}", file=sys.stderr)
        print(json.dumps({"known": True, "name": match_name}))
    else:
        print("DEBUG: Final result: known=False", file=sys.stderr)
        print(json.dumps({"known": False, "name": None}))

except Exception as e:
    print(f"DEBUG: Global exception: {e}", file=sys.stderr)
    print(json.dumps({"known": False, "error": str(e)}))
