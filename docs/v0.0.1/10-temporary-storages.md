## Temporary Storage

There several ways to hold data temporarily in Path, this section explains each of them.

### Sessions

Sessions are temporary and local to each user, which means one user can't read another's Session, to access session you need to instantiate the `Path\Core\Storage\Sessions`, see usage example below:

```php
<?php

use Path\Core\Storage\Sessions;

$session = new Sessions();

// you save a session
$session->store('key','value');
//get a saved a session
$session->get('key');
//delete a session
$session->delete('key');
// overwrite a session
$session->overwrite('key','value');
// returns all session data as array
$session->getAll('key','value');


```

### Cookies

A Cookie is temporary storage but lasts longer than a session, unlike sessions, Cookies does not clear when the users close their browsers, Cookies clear only when the expiration time you set passes or the user manually clears their cookie. the example below shows how cookies can be interacted with in Path.

```php
<?php

use Path\Core\Storage\Cookies;

$cookie = new Cookies(Cookies::ONE_DAY);//there are more static helpers, will be listed below this example

// you save a Cookie
$cookie->store('key','value');
//get a saved a Cookie
$cookie->get('key');
//delete a Cookie
$cookie->delete('key');
// overwrite a Cookie
$cookie->overwrite('key','value');
// returns all Cookie data as array
$cookie->getAll('key','value');


```

There are more Durations helper static methods or constants, few of them are listed below:

| Methods/Properties | Functionality
---------------------| ---------------
| `Cookies::ONE_DAY` | Cookie expires in one day
| `Cookies::ONE_WEEK`| Cookie expires in one week
| `Cookies::DAYS(int $days)` | Specifying number of days
| `Cookies::WEEKS(int $weeks)` | Specifying number of weeks
| `Cookies::MONTHS(int $months)` | Specifying number of Months

### Caches

Caches are permanent unless cleared, it's not meant to be used for sensitive data as can be leaked, caches are stored in `path/.Storage/.Caches/` folder, examples below shows usage of Path's caches.

```php
<?php

use Path\Core\Storage\Caches;

// Caches does not need instantiation, every method is static

//cache a data
Caches::cache('key','Value');
//get cached data
Caches::get('key');
//deleting cached data
Caches::delete('key');
//delete All Caches
Caches::deleteAll();

```