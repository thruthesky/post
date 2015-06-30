<?php
use Drupal\post\Entity\PostData;
PostData::deletePost(2002);

print_r( \Drupal\library\Library::getError() );
