<?php
require_once __DIR__ . '/env.php';

function cloudinary_ready(){
    return env('CLOUDINARY_CLOUD_NAME', '') !== ''
        && env('CLOUDINARY_API_KEY', '') !== ''
        && env('CLOUDINARY_API_SECRET', '') !== '';
}

// Saves one uploaded image. Returns:
//   "none"      -> no file was chosen
//   null        -> failed ($error explains why)
//   a string    -> the value to store in items.image (a URL, or a local filename)
function image_upload($file, &$error){

    $error = "";

    if(!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE){
        return "none";
    }

    if($file['error'] !== UPLOAD_ERR_OK){
        $error = "Image upload failed. Please try again.";
        return null;
    }

    if($file['size'] > 2 * 1024 * 1024){
        $error = "Image must be smaller than 2MB.";
        return null;
    }

    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];

    if(!isset($allowed[$mime])){
        $error = "Only JPG, PNG or GIF images are allowed.";
        return null;
    }

    // ---------- Cloudinary ----------
    if(cloudinary_ready()){

        $cloud     = env('CLOUDINARY_CLOUD_NAME');
        $timestamp = time();
        $publicId  = 'campus-lost-found/' . bin2hex(random_bytes(12));

        // Signature = SHA-1 of the sorted params followed by the secret
        $signature = sha1("public_id=$publicId&timestamp=$timestamp" . env('CLOUDINARY_API_SECRET'));

        $ch = curl_init("https://api.cloudinary.com/v1_1/$cloud/image/upload");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'file'      => new CURLFile($file['tmp_name'], $mime, 'upload.' . $allowed[$mime]),
                'api_key'   => env('CLOUDINARY_API_KEY'),
                'timestamp' => $timestamp,
                'public_id' => $publicId,
                'signature' => $signature
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if($body === false){
            error_log('Cloudinary curl error: ' . curl_error($ch));
            $error = "Could not save the image. Please try again.";
            return null;
        }

        $data = json_decode($body, true);

        if($code !== 200 || empty($data['secure_url'])){
            error_log('Cloudinary upload error: ' . $body);
            $error = "Could not save the image. Please try again.";
            return null;
        }

        return $data['secure_url'];
    }

    // ---------- Local fallback (XAMPP) ----------
    $dir = __DIR__ . '/../uploads';

    if(!is_dir($dir)){
        mkdir($dir, 0755, true);
    }

    $name = uniqid("item_", true) . "." . $allowed[$mime];

    if(!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)){
        $error = "Could not save the image.";
        return null;
    }

    return $name;
}

// The src to put in <img>. Handles Cloudinary URLs and old local filenames.
function image_url($ref){

    if($ref === null || $ref === ''){
        return '';
    }

    if(preg_match('#^https://res\.cloudinary\.com/#', $ref)){
        // Let Cloudinary pick the best format and quality
        return str_replace('/upload/', '/upload/f_auto,q_auto/', $ref);
    }

    return 'uploads/' . $ref;
}

// Deletes an image from wherever it lives
function image_delete($ref){

    if(empty($ref)){
        return;
    }

    // Cloudinary image: pull the public ID out of the URL
    if(preg_match('#^https://res\.cloudinary\.com/[^/]+/image/upload/(?:v\d+/)?(.+)\.[A-Za-z0-9]+$#', $ref, $m)){

        if(!cloudinary_ready()){
            return;
        }

        $cloud     = env('CLOUDINARY_CLOUD_NAME');
        $timestamp = time();
        $publicId  = $m[1];
        $signature = sha1("public_id=$publicId&timestamp=$timestamp" . env('CLOUDINARY_API_SECRET'));

        $ch = curl_init("https://api.cloudinary.com/v1_1/$cloud/image/destroy");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'public_id' => $publicId,
                'api_key'   => env('CLOUDINARY_API_KEY'),
                'timestamp' => $timestamp,
                'signature' => $signature
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        curl_exec($ch);
        return;
    }

    // Local file
    $path = __DIR__ . '/../uploads/' . basename($ref);

    if(is_file($path)){
        unlink($path);
    }
}