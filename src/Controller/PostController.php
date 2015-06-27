<?php
namespace Drupal\mall\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\library\Library;


class CategoryController extends ControllerBase {
    public static function firstPage() {
        return [
            '#theme' => 'post.index',
            '#data' => []
        ];
    }
}
