import face_recognition
import cv2
import os
import numpy as np

# Cartella con i volti noti
KNOWN_FACES_DIR = "volti"

known_face_encodings = []
known_face_names = []

# CARICAMENTO VOLTI NOTI
for filename in os.listdir(KNOWN_FACES_DIR):
    if filename.endswith(".jpg") or filename.endswith(".png") or filename.endswith(".jpeg"):
        path = os.path.join(KNOWN_FACES_DIR, filename)
        image = face_recognition.load_image_file(path)
        encodings = face_recognition.face_encodings(image)

        if len(encodings) > 0:
            known_face_encodings.append(encodings[0])
            known_face_names.append(os.path.splitext(filename)[0])

print("Volti caricati:", known_face_names)

# AVVIO WEBCAM
video_capture = cv2.VideoCapture(0)

while True:
    ret, frame = video_capture.read()
    if not ret:
        break

    # Riduzione per velocità
    small_frame = cv2.resize(frame, (0, 0), fx=0.25, fy=0.25)
    rgb_small_frame = cv2.cvtColor(small_frame, cv2.COLOR_BGR2RGB)

    face_locations = face_recognition.face_locations(rgb_small_frame)
    face_encodings = face_recognition.face_encodings(rgb_small_frame, face_locations)

    for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):

        matches = face_recognition.compare_faces(
            known_face_encodings, face_encoding, tolerance=0.65
        )

        name = "Volto non riconosciuto"
        

        if True in matches:
            best_match_index = np.argmin(
                face_recognition.face_distance(known_face_encodings, face_encoding)
            )
            name = known_face_names[best_match_index]
            

        # Riporta alle dimensioni originali
        top *= 4
        right *= 4
        bottom *= 4
        left *= 4

        cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2)
        cv2.putText(
            frame,
            name,
            (left, top - 10),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.8,
            (0, 255, 0),
            2
        )

    cv2.imshow("Riconoscimento Volti", frame)

    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

video_capture.release()
cv2.destroyAllWindows()