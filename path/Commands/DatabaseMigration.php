<?php


namespace Path\App\Commands;

use Path\App\Database\Model\Audio;
use Path\App\Database\Model\User;
use Path\App\Database\Model\Category;
use Path\App\Database\Model\Post;
use Path\App\Database\Model\UserSettings;
use Path\App\Database\Model\Video;
use Path\App\Database\Model\Wallet;
use Path\App\Facades\BBCode;
use Path\App\Facades\GoogleAuthenticator\src\GoogleAuthenticator;
use Path\App\Facades\GoogleAuthenticator\src\GoogleQrUrl;
use Path\Core\CLI\CInterface;
use Path\Core\Database\Connections\MySql;
use PDO;

class DatabaseMigration extends CInterface
{

    const user = 'migration_user';
    const password = 'dev!!@@##1234';

    const host_ip = 'localhost';
    const db_name = 'admin_wl';

    const per_batch = 100;

    private static $connection = null;
    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "database-migration";
    public $description = "Migrate Waploaded Database from the existing";

    public $arguments = [
        "-data" => [
            "desc" => "The data to begin migrating"
        ]
    ];

    

    /**
     * @param $params
     */
    public function entry($params)
    {
        if (!@$params['-data']){
            $this->write("data source not provided\n");
        }

        $this->write("Starting migration sequence \n");

        switch (@$params['-data']) {

            case 'user':{
                $this->migrateUsers();
                break;
            }

            case 'forum':{
                $this->migrateForumCat();
                break;
            }

            case 'music': {
                $this->migrateMusicCat();
                break;
            }

            case 'video': {
                $this->migrateVideoCat();
                break;
            }

            case 'story': {
                $this->migrateStoryCat();
                break;
            }

            case 'album': {
                $this->migrateAlbumAll();
                break;
            }

            case 'all': {
                $this->migrateForumCat();
                $this->migrateStoryCat();
                $this->migrateMusicCat();
                $this->migrateVideoCat();
                $this->migrateAlbumAll();
                break;
            }

            case '2fa': {
                $this->generateUsers2fa();
                break;
            }

            default:{
                $this->write("Nothing\n");
            }

        }

        self::$connection = null;
    }
    public static function connection():PDO{
        if (self::$connection) {
            try {
                self::$connection->query('SELECT 1');
            } catch (\PDOException $e) {
                self::init();
            }
        } else {
            self::init();
        }

        return self::$connection;
    }
    private static function init(){
        $dsn = 'mysql:dbname='.self::db_name.';host='.self::host_ip.';charset=utf8mb4';
        self::$connection = new PDO($dsn, self::user, self::password, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 600 * 3,
            PDO::ATTR_EMULATE_PREPARES => true
        ]);
    }


    public function migrateUsers($batch = 0){
        $this->write("Starting migration for users: batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT * FROM b_users ORDER BY userID ASC LIMIT {$start}, {$end}");
        $statement->execute();

        while ($user = $statement->fetch(PDO::FETCH_ASSOC)){

            if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $user['email'] = "::::{$user['username']}@waploaded.com";
            }

            $insert_data = [
                'username' => $user['username'],
                'password' => '',
                'email' => $user['email'],
                'full_name' => $user['name'] ? $user['name'] : $user['username'],
                'email_is_verified' => 1,
                'is_verified' => 1,
                'is_banned' => $user['banned'],
                'phone_number' => $user['number']
            ];

            try{

                $i = (new User())->insert($insert_data);
                $user_id = $i->last_insert_id;
                (new Wallet())->insert(['user_id' => $user_id]);
                (new UserSettings())->insert(['user_id' => $user_id]);

            }catch(\Exception $exception){
                $data = json_encode($insert_data);
                $this->write("Unable to add user with ($data) failed\n");
            }

        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateUsers(++$batch);

    }

    public function generateUsers2fa($batch = 0){
        $this->write("Starting 2FA for users: batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT id, full_name, google_auth_secrete FROM waploaded_db.user WHERE google_auth_secrete IS NULL LIMIT {$start}, {$end}");
        $statement->execute();

        while ($user = $statement->fetch(PDO::FETCH_ASSOC)){
            if ($user['google_auth_secrete'])
                continue;

            $input = [];

            $user['full_name'] = $input['full_name'] = preg_replace('/[^a-zA-Z ]/', '', $user['full_name']);

            $googleAuthenticator = new GoogleAuthenticator();
            $secret = $googleAuthenticator->generateSecret();
            $qr_code = GoogleQrUrl::generate("Waploaded ({$user['full_name']})", $secret);

            $input['google_auth_secrete'] = $secret;
            $input['google_2fa_qrcode_link'] = $qr_code;

            try{
                (new User())->identify($user['id'])->update($input);
            }catch(\Exception $exception){
                $this->write("Unable to update user with id ({$user['id']}) failed\n");
            }

        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->generateUsers2fa(++$batch);

    }


    public function migrateStoryCat(){
        $base_category = (new Category())->insert(['name' => 'Story']);
        $base_story_id = $base_category->last_insert_id;

        $this->write("Created Category Story \n");

        $statement = self::connection()->prepare('SELECT * FROM b_storiescat');
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $insert_data = [
                'name' => $row['name'],
                'parent_id' => $base_story_id
            ];

            $i = (new Category())->insert($insert_data);

            $this->write("Migrating story {$row['id']} \n");

            $this->migrateStory( $row, $i->last_insert_id);
        }

    }

    public function migrateStory($old_category, $new_category_id, $batch = 0){
        $this->write("Starting migration for story category {$old_category['id']} - batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT * FROM b_stories WHERE catid = {$old_category['id']} ORDER BY id ASC LIMIT {$start}, {$end}");
        $statement->execute();

        while ($story = $statement->fetch(PDO::FETCH_ASSOC)){
            $parsed_body = BBCode::parse($story['comm']);
            $youtube_iframe = "<br><br><center><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$story['streamid']}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></center>";

            $i = (new Post())->insert([
                'type' => 'story',
                'title' => $story['title'],
                'category_id' => $new_category_id,
                'body' => $parsed_body.($story['streamid'] ? $youtube_iframe : ''),
                'cover_img' => basename($story['imgpath'], PATHINFO_BASENAME),
                'is_approved' => 1,
                'identifier' => 'story--'.$story['id'],
                'fmt_date' => date('Y-m-d', $story['time']),
                'date_added' => $story['time'],
                'last_update_date' => $story['time']
            ]);

            $post_id = $i->last_insert_id;

            if ($story['isseries']){
                $story['post_id'] = $post_id;
                $this->migrateStoryEpisodes($story, $new_category_id);
            }

        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateStory($old_category, $new_category_id, ++$batch);

    }

    public function migrateStoryEpisodes($series, $new_category_id){
        $this->write("Starting migration for STORY {$series['id']}\n");

        $statement = self::connection()->prepare("SELECT * FROM b_storiesepisodes WHERE seriesid = {$series['id']} ORDER BY seasonid, episodeid ASC");
        $statement->execute();

        $inserted_season = [];

        while ($episode = $statement->fetch(PDO::FETCH_ASSOC)){
            $parsed_body = BBCode::parse($episode['about']);

            if ( !isset($inserted_season[$episode['seasonid']]) ) {
                $s = (new Post())->insert([
                    'parent_id' => $series['post_id'],
                    'type' => 'season',
                    'title' => $series['title']." Season {$episode['seasonid']}",
                    'category_id' => $new_category_id,
                    'body' => $parsed_body,
                    'cover_img' => basename($episode['img'], PATHINFO_BASENAME),
                    'is_approved' => 1,
                    'identifier' => "story_season_{$episode['seasonid']}--".$series['post_id'],
                    'fmt_date' => date('Y-m-d', $episode['time']),
                    'date_added' => $episode['time'],
                    'last_update_date' => $episode['time']
                ]);
                (new Post())->identify($series['post_id'])->increment('children_count')->update();
                $inserted_season[$episode['seasonid']] = $s->last_insert_id;
            }

            $i = (new Post())->insert([
                'parent_id' => $inserted_season[$episode['seasonid']],
                'type' => 'episode',
                'title' => $episode['title'],
                'category_id' => $new_category_id,
                'body' => $parsed_body,
                'cover_img' => basename($episode['img'], PATHINFO_BASENAME),
                'is_approved' => 1,
                'identifier' => 'story_episode--'.$episode['id'],
                'fmt_date' => date('Y-m-d', $episode['time']),
                'date_added' => $episode['time'],
                'last_update_date' => $episode['time']
            ]);
            (new Post())->identify($inserted_season[$episode['seasonid']])->increment('children_count')->update();

        }

    }



    public function migrateVideoCat(){
        $base_category = (new Category())->insert(['name' => 'Video']);
        $base_video_id = $base_category->last_insert_id;

        $this->write("Created Category Video \n");

        // WHERE b_videocat.id > 60
        $statement = self::connection()->prepare('SELECT * FROM b_videocat');
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $insert_data = [
                'name' => $row['name'],
                'parent_id' => $base_video_id
            ];

            $i = (new Category())->insert($insert_data);

            $this->write("Migrating {$row['id']} \n");

            $this->migrateVideo( $row, $i->last_insert_id);
        }

    }

    public function migrateVideo($old_category, $new_category_id, $batch = 0){
        $this->write("Starting migration for video: category {$old_category['id']} - batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT * FROM b_video WHERE catid = {$old_category['id']} ORDER BY id ASC LIMIT {$start}, {$end}");
        $statement->execute();

        while ($video = $statement->fetch(PDO::FETCH_ASSOC)){
            $parsed_body = BBCode::parse($video['comm']);
            $youtube_iframe = "<br><br><center><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$video['streamid']}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></center>";
            $trailer_iframe = "<br><br><center><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$video['trailer']}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></center>";

            $post_insert = [
                'type' => $video['isseries'] ? 'series' : 'video',
                'title' => $video['title'],
                'category_id' => $new_category_id,
                'body' => $parsed_body.($video['streamid'] ? $youtube_iframe : '').($video['trailer'] ? $trailer_iframe : ''),
                'cover_img' => basename($video['img'], PATHINFO_BASENAME),
                'is_approved' => 1,
                'identifier' => 'video--'.$video['id'],
                'fmt_date' => date('Y-m-d', $video['time']),
                'date_added' => $video['time'],
                'last_update_date' => $video['time']
            ];
            if ($video['isseries']){
                $pass = 'pass';
            }elseif ($video['catid'] == 10){
                $post_insert['type'] = 'movie';
            }
            $i = (new Post())->insert($post_insert);

            $post_id = $i->last_insert_id;

            if ($video['link']) {
                $video['link'] = $this->filterVideo($video['link']);
                $insert_data = [
                    'title' => $video['title'],
                    'cover_img' => basename($video['img'], PATHINFO_BASENAME),
                    'file_size' => $video['getsize'],
                    'description' => $parsed_body,
                    'filename' => basename($video['link'], PATHINFO_BASENAME),
                    'is_processing' => 0,
                    'post_id' => $post_id,
                    'date_added' => $video['time'],
                    'subtitle' => basename($video['subtitle'], PATHINFO_BASENAME),
                    'last_update_date' => $video['time']
                ];

                (new Video())->insert($insert_data);
            }

            if ($video['isseries']){
                $video['post_id'] = $post_id;
                $this->migrateVideoEpisodes($video, $new_category_id);
            }
        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateVideo($old_category, $new_category_id, ++$batch);

    }

    public function migrateVideoEpisodes($series, $new_category_id){

        $this->write("Starting migration for SERIES {$series['id']}\n");

        $statement = self::connection()->prepare("SELECT * FROM b_videoepisodes WHERE seriesid = {$series['id']} ORDER BY seasonid, episodeid ASC");
        $statement->execute();

        $inserted_season = [];

        while ($episode = $statement->fetch(PDO::FETCH_ASSOC)){
            $parsed_body = BBCode::parse($episode['about']);

            if ( !isset($inserted_season[$episode['seasonid']]) ) {
                $s = (new Post())->insert([
                    'parent_id' => $series['post_id'],
                    'type' => 'season',
                    'title' => $series['title']." SEASON {$episode['seasonid']}",
                    'category_id' => $new_category_id,
                    'body' => $parsed_body,
                    'cover_img' => basename($episode['img'], PATHINFO_BASENAME),
                    'is_approved' => 1,
                    'identifier' => "video_season_{$episode['seasonid']}--".$series['post_id'],
                    'fmt_date' => date('Y-m-d', $episode['time']),
                    'date_added' => $episode['time'],
                    'last_update_date' => $episode['time']
                ]);
                (new Post())->identify($series['post_id'])->increment('children_count')->update();
                $inserted_season[$episode['seasonid']] = $s->last_insert_id;
            }

            $episode['ytcode'] = $episode['ytcode'] ? $episode['ytcode'] : $episode['yt'];
            $youtube_iframe = "<br><br><center><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$episode['ytcode']}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></center>";

            $i = (new Post())->insert([
                'parent_id' => $inserted_season[$episode['seasonid']],
                'type' => 'episode',
                'title' => $episode['title'],
                'category_id' => $new_category_id,
                'body' => $parsed_body.($episode['ytcode'] ? $youtube_iframe : ''),
                'cover_img' => basename($episode['img'], PATHINFO_BASENAME),
                'is_approved' => 1,
                'identifier' => 'video_episode--'.$episode['id'],
                'fmt_date' => date('Y-m-d', $episode['time']),
                'date_added' => $episode['time'],
                'last_update_date' => $episode['time']
            ]);
            (new Post())->identify($inserted_season[$episode['seasonid']])->increment('children_count')->update();

            $episode['url'] = $this->filterVideo($episode['url']);

            $insert_data = [
                'title' => $episode['title'],
                'cover_img' => basename($episode['img'], PATHINFO_BASENAME),
                'file_size' => $episode['getsize'],
                'description' => $parsed_body,
                'filename' => basename($episode['url'], PATHINFO_BASENAME),
                'is_processing' => 0,
                'post_id' => $i->last_insert_id,
                'subtitle' => basename($episode['subtitle'], PATHINFO_BASENAME),
                'date_added' => $episode['time'],
                'last_update_date' => $episode['time']
            ];

            (new Video())->insert($insert_data);

        }

    }


    public function migrateMusicCat(){
        $base_category = (new Category())->insert(['name' => 'Music']);
        $base_music_id = $base_category->last_insert_id;

        $this->write("Created Category Music \n");

        $statement = self::connection()->prepare('SELECT * FROM b_musiccat');
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $insert_data = [
                'name' => $row['name'],
                'parent_id' => $base_music_id
            ];

            $i = (new Category())->insert($insert_data);

            $this->write("Migrating {$row['id']} \n");

            $this->migrateMusic($row, $i->last_insert_id);
        }

        $this->migrateMusic( ['id' => 0], $base_music_id); // Migrate no category
    }

    public function migrateMusic($old_category, $new_category_id, $batch = 0){
        $this->write("Starting migration for music: category {$old_category['id']} - batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT * FROM b_music WHERE catid = {$old_category['id']} ORDER BY id ASC LIMIT {$start}, {$end}");
        $statement->execute();

        while ($music = $statement->fetch(PDO::FETCH_ASSOC)){
            $youtube_iframe = "<br><br><center><iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$music['yt']}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></center>";

            $i = (new Post())->insert([
                'type' => 'music',
                'title' => $music['title'],
                'category_id' => $new_category_id,
                'body' => BBCode::parse($music['comma']).($music['yt'] ? $youtube_iframe : ''),
                'cover_img' => basename($music['pic'], PATHINFO_BASENAME),
                'is_approved' => 1,
                'identifier' => 'music--'.$music['id'],
                'fmt_date' => date('Y-m-d', $music['time']),
                'date_added' => $music['time'],
                'last_update_date' => $music['time']
            ]);

            $music['linka'] = $this->filterAudio($music['linka']);

            $insert_data = [
                'title' => $music['title'],
                'cover_img' => basename($music['pic'], PATHINFO_BASENAME),
                'artist' => $music['artiste'],
                'file_size' => $music['size'],
                'album' => $music['alb'],
                'lyrics' => BBCode::parse($music['lyrics']),
                'description' => BBCode::parse($music['comma']),
                'filename' => basename($music['linka'], PATHINFO_BASENAME),
                'is_processing' => 0,
                'post_id' => $i->last_insert_id,
                'date_added' => $music['time'],
                'last_update_date' => $music['time'],
                'hide_on_desktop' => $music['yt'] ? 1 : 0
            ];

            (new Audio())->insert($insert_data);
        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateMusic($old_category, $new_category_id, ++$batch);

    }



    public function migrateAlbumAll(){
        $i = (new Category())->insert(['name' => 'Album']);
        $this->write("Created category Album\n");

        $this->migrateMusicAlbum($i->last_insert_id);
    }

    public function migrateMusicAlbum($new_category_id, $batch = 0){
        $this->write("Starting migration for album batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT DISTINCT album as album_name, artist FROM waploaded_db.audio WHERE album != '' LIMIT {$start}, {$end}");
        $statement->execute();

        while ($music_album = $statement->fetch(PDO::FETCH_ASSOC)){

            $count = (new Audio())->where([
                'audio.album' => $music_album['album_name'],
                'audio.artist' => $music_album['artist']
            ])->count();

            $first_audio = (new Audio())->where([
                'audio.album' => $music_album['album_name'],
                'audio.artist' => $music_album['artist']
            ])->getFirst();

            $first_audio = (array) $first_audio;

            echo $count;

            $i = (new Post())->insert([
                'type' => 'album',
                'title' => "{$music_album['album_name']} BY {$music_album['artist']}",
                'category_id' => $new_category_id,
                'body' => "",
                'cover_img' => $first_audio['cover_img'],
                'is_approved' => 1,
                'identifier' => 'album--0',
                'fmt_date' => date('Y-m-d', $first_audio['date_added']),
                'date_added' => $first_audio['date_added'],
                'last_update_date' => $first_audio['last_update_date'],
                'children_count' => $count
            ]);

            $album_id = $i->last_insert_id;

            if ($count){
                $s = MySql::connection()->prepare("UPDATE waploaded_db.post LEFT JOIN waploaded_db.audio ON post.id = audio.post_id SET parent_id = {$album_id} WHERE audio.album = :album AND audio.artist = :artiste");
                $s->execute([
                    'album' => $music_album['album_name'],
                    'artiste' => $music_album['artist']
                ]);
            }

            $this->write("Migrated \n");
        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateMusicAlbum($new_category_id, ++$batch);

    }



    public function migrateForumCat(){
        $base_category = (new Category())->insert(['name' => 'Forum']);
        $base_forum_id = $base_category->last_insert_id;

        $this->write("Created Category Forum \n");

        $statement = self::connection()->prepare('SELECT * FROM b_forums');
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $insert_data = [
                'name' => $row['name'],
                'parent_id' => $base_forum_id
            ];

            $i = (new Category())->insert($insert_data);

            $this->write("Migrating {$row['id']} \n");

            $this->migrateForumPosts($row, $i->last_insert_id);
        }
    }

    private function migrateForumPosts($old_forum, $new_cat_id, $batch = 0){
        $this->write("Starting migration for forum posts: forum {$old_forum['id']} batch $batch \n");

        $start = $batch == 0 ? 0 : $batch * self::per_batch;
        $end = self::per_batch;

        $statement = self::connection()->prepare("SELECT * FROM b_topics WHERE forumid = {$old_forum['id']} ORDER BY id ASC LIMIT {$start}, {$end}");
        $statement->execute();

        while ($r = $statement->fetch(PDO::FETCH_ASSOC)){

            $insert_data = [
                'category_id' => $new_cat_id,
                'title' => $r['subject'],
                'body' => BBCode::parse($r['message']),
                'is_approved' => 1,
                'type' => 'forum',
                'fmt_date' => date('Y-m-d', $r['date']),
                'identifier' => 'forum--'.$r['id'],
                'cover_img' => pathinfo($r['pic'], PATHINFO_BASENAME),
                'date_added' => $r['date'],
                'last_update_date' => $r['date']
            ];

            (new Post())->insert($insert_data);

        }

        if ($statement->rowCount() and $statement->rowCount() == self::per_batch)
            $this->migrateForumPosts($old_forum, $new_cat_id, ++$batch);
    }

    private function filterVideo(string $name){
        if (!$name) return '';
        $invalid = [
            'bitcasa',
            's02.waploaded',
            'server.wap',
            'toxicunrated.com',
            'toxicwap.com'
        ];
        foreach ($invalid as $value){
            if (preg_match("/{$value}/i", $name)) {
                return '';
            }
        }
        return $name;
    }

    private function filterAudio(string $name){
        if (!$name) return '';
        $invalid = [
            'youtube',
            'bitcasa',
            's02.waploaded',
            'server.wap',
            'bit.ly',
            'zippy',
            '70tunes',
            'datafilehost',
            'dopefile'
        ];
        foreach ($invalid as $value){
            if (preg_match("/{$value}/i", $name)) {
                return '';
            }
        }
        return $name;
    }
}
