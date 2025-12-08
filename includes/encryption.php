<?php
// includes/encryption.php

class EndToEndEncryption {
    private static $cipher = "aes-256-gcm";
    private static $key_length = 32; // 256 бит
    
    /**
     * Генерация пары ключей для пользователя
     */
    public static function generateKeyPair() {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        
        $keyPair = openssl_pkey_new($config);
        
        // Извлекаем приватный ключ
        openssl_pkey_export($keyPair, $privateKey);
        
        // Извлекаем публичный ключ
        $publicKey = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKey["key"];
        
        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    /**
     * Шифрование сообщения для получателя
     */
    public static function encryptMessage($message, $receiverPublicKey, $senderPrivateKey = null) {
        // Генерируем случайный симметричный ключ для этого сообщения
        $symmetricKey = random_bytes(self::$key_length);
        
        // Шифруем сообщение симметричным ключом
        $iv = random_bytes(openssl_cipher_iv_length(self::$cipher));
        $tag = '';
        
        $encryptedContent = openssl_encrypt(
            $message,
            self::$cipher,
            $symmetricKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        
        // Шифруем симметричный ключ публичным ключом получателя
        openssl_public_encrypt($symmetricKey, $encryptedKey, $receiverPublicKey);
        
        // Если есть приватный ключ отправителя, подписываем сообщение
        $signature = '';
        if ($senderPrivateKey) {
            openssl_sign($encryptedContent, $signature, $senderPrivateKey, OPENSSL_ALGO_SHA256);
        }
        
        return [
            'encrypted_content' => base64_encode($encryptedContent),
            'encrypted_key' => base64_encode($encryptedKey),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'signature' => $signature ? base64_encode($signature) : '',
            'algorithm' => self::$cipher
        ];
    }
    
    /**
     * Расшифровка сообщения
     */
    public static function decryptMessage($encryptedData, $receiverPrivateKey) {
        // Расшифровываем симметричный ключ
        openssl_private_decrypt(
            base64_decode($encryptedData['encrypted_key']),
            $symmetricKey,
            $receiverPrivateKey
        );
        
        // Расшифровываем сообщение
        $decrypted = openssl_decrypt(
            base64_decode($encryptedData['encrypted_content']),
            self::$cipher,
            $symmetricKey,
            OPENSSL_RAW_DATA,
            base64_decode($encryptedData['iv']),
            base64_decode($encryptedData['tag'])
        );
        
        return $decrypted;
    }
    
    /**
     * Простая версия шифрования (если E2EE не работает)
     */
    public static function simpleEncrypt($message, $password) {
        $iv = random_bytes(16);
        $key = hash('sha256', $password, true);
        
        $encrypted = openssl_encrypt(
            $message,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }
    
    public static function simpleDecrypt($encrypted, $password) {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $key = hash('sha256', $password, true);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}