import sys
import os
import json
import warnings

# ---- SOPPRESSIONE WARNING GENERALI ----
warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'  # Nasconde warning TensorFlow

from deepface import DeepFace

# ---- CONTROLLO ARGOMENTI ----
if len(sys.argv) < 2:
    print(json.dumps({"known": False, "error": "Nessuna immagine fornita"}))
    sys.exit(0)

image_path = sys.argv[1]

if not os.path.exists(image_path):
    print(json.dumps({"known": False, "error": "File immagine non trovato"}))
    sys.exit(0)

# ---- DIRECTORY VOLTI NOTI ----
BASE_DIR = os.path.dirname(__file__)
KNOWN_DIR = os.path.join(BASE_DIR, "known_faces")

if not os.path.exists(KNOWN_DIR):
    print(json.dumps({"known": False, "error": "Cartella known_faces mancante"}))
    sys.exit(0)

known_images = []
known_names = []

for filename in os.listdir(KNOWN_DIR):
    file_path = os.path.join(KNOWN_DIR, filename)
    known_images.append(file_path)
    known_names.append(os.path.splitext(filename)[0])

# ---- VERIFICA VOLTO ----
found_match = False
match_name = None

for i, known_img in enumerate(known_images):
    try:
        result = DeepFace.verify(
            img1_path=image_path,
            img2_path=known_img,
            model_name='Facenet',   # opzioni: Facenet, VGG-Face, ArcFace, OpenFace
            enforce_detection=True
        )
        if result["verified"]:
            found_match = True
            match_name = known_names[i]
            break
    except Exception:
        continue

if found_match:
    print(json.dumps({"known": True, "name": match_name}))
else:
    print(json.dumps({"known": False, "name": None}))
