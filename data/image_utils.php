<?php
// image_utils.php — includi con require_once in entrambi i file API

/**
 * Salva e comprime un'immagine uploadata.
 * Stessa logica dell'altro progetto: move_uploaded_file prima, poi elaborazione GD.
 *
 * - JPEG / WebP → convertiti in WebP
 * - PNG         → ricompresso livello 8
 * - GIF         → copiato senza elaborazione
 *
 * @param string $tmpPath   $_FILES['foto']['tmp_name']
 * @param string $destPath  Path di destinazione completo (con nome file)
 * @param string $mime      MIME type già validato dal chiamante
 * @param int    $maxDim    Lato massimo in pixel
 * @param int    $quality   Qualità WebP/JPEG (0-100)
 * @return string|false     Path finale del file (può diventare .webp), false se errore
 */
function compressAndSaveImage(
    string $tmpPath,
    string $destPath,
    string $mime,
    int $maxDim  = 1920,
    int $quality = 82
): string|false {

    // GIF o tipo non supportato: copia diretta senza elaborazione
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return move_uploaded_file($tmpPath, $destPath) ? $destPath : false;
    }

    // 1) Salva prima il file (identico all'altro progetto)
    if (!move_uploaded_file($tmpPath, $destPath)) {
        return false;
    }

    // 2) Elaborazione GD (identico all'altro progetto)
    if (function_exists('imagecreatefromjpeg')) {

        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($destPath),
            'image/png'  => @imagecreatefrompng($destPath),
            'image/webp' => @imagecreatefromwebp($destPath),
        };

        if ($src !== false) {
            $origW = imagesx($src);
            $origH = imagesy($src);

            // Ridimensiona solo se necessario
            if ($origW > $maxDim || $origH > $maxDim) {
                if ($origW >= $origH) {
                    $newW = $maxDim;
                    $newH = (int) round($origH * $maxDim / $origW);
                } else {
                    $newH = $maxDim;
                    $newW = (int) round($origW * $maxDim / $origH);
                }

                $dst = imagecreatetruecolor($newW, $newH);

                if ($mime === 'image/png') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                }

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                imagedestroy($src);
                $src = $dst;
            }

            // JPEG e WebP → converti in WebP
            if ($mime === 'image/jpeg' || $mime === 'image/webp') {
                $newPath = preg_replace('/\.(jpg|jpeg|webp)$/i', '.webp', $destPath);
                if (function_exists('imagewebp') && imagewebp($src, $newPath, $quality)) {
                    @unlink($destPath);
                    $destPath = $newPath;
                } else {
                    // Fallback: risalva come JPEG
                    imagejpeg($src, $destPath, $quality);
                }
            } elseif ($mime === 'image/png') {
                imagepng($src, $destPath, 8);
            }

            imagedestroy($src);
        }
    }

    return $destPath;
}