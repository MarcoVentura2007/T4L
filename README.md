Time4All – Guida al riconoscimento facciale



Questa guida spiega come configurare ed eseguire la sezione di **riconoscimento facciale** del progetto **Time4All**.

---

## **1. Creazione dell’ambiente virtuale**

Creare un ambiente virtuale è consigliato perché:

- Isola le librerie del progetto da quelle di sistema
- Mantiene il sistema pulito e riduce conflitti tra pacchetti

### **1.1 Controlla Python3**

Apri il terminale e digita:

```c
python3 --version
```

dovresti vedere una versione di Python 3.x 

### **1.2 Vai nella cartella del progetto**

```c
cd /percorso/del/progetto
```

### **1.3 Crea l’ambiente virtuale**

```c
python3 -b venv venv
```

### **1.4 Attiva l’ambiente virtuale**

```c
source venv/bin/activate
```

> Quando l’ambiente è attivo, il prompt mostrerà (venv)
> 

---

## 2. Installazione delle librerie

Lo script necessita di due librerie principali:

- **Face_recognition -** per il riconoscimento facciale
- **OpenCV -** per acquisire video dalla webcam

### 2.1 Installa face_recognition

```c
pip install face_recognition
```

> ⚠️  Su alcuni sistemi può essere necessario installare anche *cmake* o strumenti di compilazione (es. *build-essential* su linux)
> 

### 2.2 Installa Open-CV

```c
pip install opencv-python
```

---

## 3. Preparazione della cartella “volti”

1. Crea una cartella chiamata *volti* nella rotto del progetto
2. Inserisci almeno **una foto chiara frontale per ogni persona da riconoscere.**
3. Formati accettati: .*jpg, .jpeg, .png*
4. Suggerimenti:
    1. Evitare occhiali da sole, cappelli o mascherine nella foto principale 
    2. Più foto per persona = maggiore precisione 
    3. Nomi dei file = nome della persona (es *andrea.jpg, marco.jpg*)

---

## 4. Avvio dello script

1. Scaricare o crea il file *script_faceid.py* (contenente il codice del riconoscimento facciale).
2. Avvia lo script con:

```c
python script_faceid.py
```

1. La finestra della webcam mostrerà:
    1. Un rettangolo verde attorno ai volti
    2. Il nome della persona riconosciuta
    3. Se il volto non è presente nella cartella → ***Volto non riconosciuto***
2. Per chiudere la webcam, premi q
