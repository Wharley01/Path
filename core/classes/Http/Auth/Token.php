<?php

namespace Path\Core\Http\Auth;


use Path\App\Database\Model\User;
use Path\Core\Database\Model;
use Path\Core\Http\Request;
use Path\Core\Storage\Cookies;
use Path\Core\Storage\Sessions;

class Token
{
    private const DECRYPTION_KEY = "6c4fd77bfece2330ddf1f3bf423913ee37931600239";
    private const CLIENT_SALT_COOKIE_KEY = "client_access_salt";

    public static $decrypted_token = null;
    private static $encrypted_token;
    private string $use = 'cookie';

    public function __construct($access_token = null)
    {
        $this->use = config("PROJECT->use") ?? 'cookie';
        if($access_token){
            static::$encrypted_token = $access_token;
            if(!static::$decrypted_token){
                static::$decrypted_token = $this->decrypt($access_token);
//            var_dump(static::$decrypted_token);
            }
        }

    }

    public function encrypt(string $data) {
        $key = static::DECRYPTION_KEY;
        $cipher = "AES-128-CBC";
        $hashKey = hash('sha256', $key);
        $key = substr($hashKey, 0, 32);
        $iv = substr($hashKey, 32, 16);
        return base64_encode(openssl_encrypt($data, $cipher, $key, $options=0, $iv));
    }

    /**
     * @param string $data
     * @return string
     */
    public function decrypt(string $data) {
        $key = static::DECRYPTION_KEY;
        $cipher = "AES-128-CBC";
        $hashKey = hash('sha256', $key);
        $key = substr($hashKey, 0, 32);
        $iv = substr($hashKey, 32, 16);
        return openssl_decrypt(base64_decode($data), $cipher, $key, $options=0, $iv);
    }

    public function generateToken($user_id, $validity_days = 90){
        $raw_token = "$user_id";
        $expiry_days = strtotime("+{$validity_days} days");
        $salt = $this->generateSalt();
        $raw_token .= "::".$expiry_days;
        $raw_token .= '::'.$salt;
        $encrypted_token = $this->encrypt($raw_token);

        return $encrypted_token;
    }

    private function generateSalt(){
        $cookie = $this->use == 'cookie' ? new Cookies(Cookies::DAYS(20)):new Sessions();
        $salt = md5(time().random_int(544,9999999999));
        $cookie->store(self::CLIENT_SALT_COOKIE_KEY,$salt);
        return $salt;
    }

    public function tokenIsValid(){
        if(!static::$decrypted_token)
            return false;
//        $salt_is_valid = $this->saltIsValid();
//        echo '>>>'.$salt_is_valid;
        return !$this->tokenHasExpire() && !!$this->getTokenUserId() && $this->saltIsValid();
    }

    public function getTokenUserId(){
        if(!static::$decrypted_token)
            return null;
        return explode('::',static::$decrypted_token)[0];
    }

    public function getTokenUserObject():?object
    {
        if(!static::$decrypted_token)
            return null;

        $seller_id = $this->getTokenUserId();
        return (new User())->identify($seller_id)->getFirst();
    }

    public function getTokenUserInstance():?Model
    {
        if(!static::$decrypted_token)
            return null;

        return User::init($this->getTokenUserId());
    }
    public function getToken(){
        if(!static::$encrypted_token)
            return null;
        return static::$encrypted_token;
    }

    private function tokenHasExpire(){
        if(!static::$decrypted_token)
            return true;
        $expiry_time = explode('::',static::$decrypted_token)[1];
        $has_expired = time() > $expiry_time;
        return $has_expired;
    }
    private function saltIsValid(){
        if(!static::$decrypted_token)
            return true;
        $salt = explode('::',static::$decrypted_token)[2];
        $saved_salt = $this->use == 'cookie' ? (new Cookies())->get(self::CLIENT_SALT_COOKIE_KEY): (new Sessions())->get(self::CLIENT_SALT_COOKIE_KEY);
        return $salt === $saved_salt;
    }

    static function init (Request $request){
        $authorization_header = $request->getHeader('Authorization');
        if (empty($authorization_header)){
            return new self(null);
        }

        [$authorization_token] = sscanf( $authorization_header, 'Bearer %s');
        if (empty($authorization_token)){
            return new self(null);
        }

        return new self($authorization_token);
    }

    public function delete()
    {
        $cookie = $this->use == 'cookie' ? new Cookies():new Sessions();
        return $cookie->delete(self::CLIENT_SALT_COOKIE_KEY);
    }

}
