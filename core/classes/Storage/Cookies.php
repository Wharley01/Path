<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/29/2018
 * @Time 7:21 AM
 * @Project Path
 */

namespace Path\Core\Storage;


class Cookies
{
    public const ONE_DAY = 86400;
    public const ONE_WEEK = 604800;
    public  $expire = null;
    public  $path = "/";
    public  $domain = "";
    public  $secure = false;
    public  $httponly = false;
    public function __construct(
        $expire = null,
        $path = "/",
        $domain = "",
        $secure = false,
        $httponly = false
    )
    {
        $this->expire = null;
        $this->path = "/";
        $this->domain = "";
        $this->secure = false;
        $this->httponly = false;
    }
    public function store(
        $key,
        $value
    ) {
        if (is_null($this->expire))
            $expire = time() + self::ONE_DAY;
        else
            $expire = time() + $this->expire;

                setcookie($key, $value, $expire, $this->path, $this->domain, $this->secure, $this->httponly);
    }
    public function overwrite(
        $key,
        $new_value
    ) {
        if ($this->exists($key)) {
            $this->store($key,$new_value);
        }
    }
    public function delete($key)
    {
        $cookie = new static(time() - 3600);
        $cookie->overwrite($key,null);
        return $this;
    }

    public function getAll(): array
    {
        return $_COOKIE;
    }

    public function get($key)
    {
        return $_COOKIE[$key] ?? null;
    }
    public function isEnabled(): bool
    {
        self::store("path_12__cookie_test", "test", 3600);
        if (isset($_COOKIE['path_12__cookie_test'])) {
            self::delete('path_12__cookie_test');
            return true;
        } else {
            return false;
        }
    }
    public function exists($key)
    {
        return isset($_COOKIE[$key]);
    }
    public static function DAYS(int $days): int
    {
        return ($days * 86400);
    }
    public static function WEEKS(int $weeks): int
    {
        return ($weeks * 604800);
    }
    public static function MONTHS(int $months): int
    {
        return ($months * 2.628e+6);
    }
}
