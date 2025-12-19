import face_recognition # type: ignore
import cv2 # type: ignore

 #FASE 1 - Inizializzazione della videocamera
video_capture = cv2.VideoCapture(0) #0 per la videocamera predefinita

#FASE 2 - Caricamento delle facce note
image_file = 'Andrea.jpg' #Percorso dell'immagine della faccia nota
target_image = face_recognition.load_image_file(image_file)

#FASE 3 - Encoding delle facce note
target_encoded = face_recognition.face_encodings(target_image)[0] #Prende il primo encoding trovato nell'immagine

print("Image loaded. 128-dimension face encoding generated. \n")

#FASE 4 - Nome del target
target_name = 'Andrea'

#FASE 5 - Riduco la dimensione del frame per velocizzare l'elaborazione
process_this_frame = True

while True:
    ret, frame = video_capture.read()
    small_frame = cv2.resize(frame, None, fx=0.25, fy=0.25) #Riduce la dimensione del frame al 25%
    rgb_small_frame = cv2.cvtColor(small_frame, 4) #Converti da BGR, utilizzato da opencv a RGB - il codice per effettuare la conversione è 4

    if process_this_frame: #se il flag è a true
        face_locations = face_recognition.face_locations(rgb_small_frame) #Trova tutte le facce nel frame ridotto
        face_encodings = face_recognition.face_encodings(rgb_small_frame) #Trova gli encodings delle facce nel frame ridotto

        #verifico se sono presenti dei volti nel frame
        if face_encodings:
            frame_face_encoding = face_encodings[0] #Prendo il primo volto rilevato nel frame
            matches = face_recognition.compare_faces([target_encoded], frame_face_encoding) #Confronto l'encoding del volto rilevato con quello noto
            label = target_name if matches else 'Unknown' #Assegno il nome se c'è una corrispondenza, altrimenti 'Unknown'

    process_this_frame = not process_this_frame #Inverto il flag per processare ogni altro frame

    #FASE 6 - controllo che la lista delle location non sia vuota
    if face_locations:
        top, right, bottom, left = face_locations[0] #Prendo la prima location rilevata

        #FASE 7 - Ridimensiono le coordinate della faccia al frame originale
        top *= 5
        right *= 5
        bottom *= 5
        left *= 5

        #FASE 8 - Disegno un rettangolo intorno alla faccia 
        cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2) #Rettangolo verde
        cv2.rectangle(frame, (left, bottom - 30), (right, bottom), (0, 255, 0), cv2.FILLED) #Rettangolo verde per il testo  
        label_font = cv2.FONT_HERSHEY_DUPLEX #Tipo di font per il testo
        cv2.putText(frame, label, (left + 6, bottom - 6), label_font, 0.8, (255, 255, 255), 1) #Scrivo il testo sul rettangolo

    #FASE 9 - Mostro il frame risultante
    cv2.imshow('Video', frame) 

    #FASE 10 - Premo 'q' per uscire
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break
    
#FASE 11 - Rilascio la videocamera e chiudo le finestre
video_capture.release()
cv2.destroyAllWindows()

