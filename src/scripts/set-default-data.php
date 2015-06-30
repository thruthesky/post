<?php
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;


Post::emptyData();
$config = PostConfig::create()
    ->set('name', 'freetalk');
$config->save();

/**
$config = PostConfig::loadByName('freetalk');
Library::loginUser('root');
 */

for( $i=0; $i<999; $i++ ) {
    $p = [
        'post_config_name' => 'freetalk',
        'username' => 'firefox',
        'title' => "이것은 제목입니다. $i",
        'content' => "<h1>이것은 내용!</h1>그럼",
    ];
    PostData::insert($p);
}
