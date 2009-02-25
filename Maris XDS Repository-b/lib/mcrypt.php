<?php
function encrypt($key, $plain_text) {
// returns encrypted text
// incoming: should be the $key that was encrypt
// with and the $plain_text that wants to be encrypted

  $plain_text = trim($plain_text);

  /* Quoting Mcrypt:
      "You must (in CFB and OFB mode) or can (in CBC mode)
       supply an initialization vector (IV) to the respective
       cipher function. The IV must be unique and must be the
       same when decrypting/encrypting."

     Meaning, we need a way to generate a _unique_ initialization vector
     but at the same time, be able to know how to gather our IV at both
     encrypt/decrypt stage.  My personal recommendation would be
     (if you are working with files) is to get the md5() of the file.
     In this example, however, I want more of a broader scope, so I chose
     to md5() the key, which should be the same both times. Note that the IV
     needs to be the size of our algorithm, hence us using substr.
  */

  $iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
  $c_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $plain_text, MCRYPT_ENCRYPT, $iv);

    return trim(chop(base64_encode($c_t)));
}
function decrypt($key, $c_t) {
// incoming: should be the $key that you encrypted
// with and the $c_t (encrypted text)
// returns plain text

  // decode it first :)
  $c_t =  trim(chop(base64_decode($c_t)));

  $iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
  $p_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $c_t, MCRYPT_DECRYPT, $iv);

         return trim(chop($p_t));
}

?>
