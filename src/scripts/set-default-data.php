<?php
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;

/*
Post::emptyData();
$config = PostConfig::create()
    ->set('name', 'freetalk');
$config->save();
*/

$config = PostConfig::loadByName('freetalk');

Library::loginUser('root');

for( $i=0; $i<999; $i++ ) {

    $data = PostData::create()
        ->setOwnerId(Library::myUid())
        ->set('config_id', $config->id())
        ->set('title', "아. 아이디를 잘못 입력 했네요.. - $i ")
        ->set('content', "<h1>그럼, 안녕하세요.</h1><p style='color:red;'>반갑습니다.</p> No. $i")
        ->set('content_stripped', "안녕하세요.반갑습니다. $i")
        ->save();
}
