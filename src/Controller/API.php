<?php
namespace Drupal\post\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\library\Library;
use Drupal\post\Entity\PostData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class API extends ControllerBase {
    public function DefaultController() {
        $call = \Drupal::request()->get('call');
        $re = $this->$call();
        if ( is_array($re) ) {
        }
        else {
            $re = ['result'=>$re];
        }
        $re = json_encode($re);
        $response = new JsonResponse( $re );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }
    public function vote() {
        $request = \Drupal::request();
        $id = $request->get('id');
        $mode = $request->get('mode');
        return PostData::vote($id, $mode);
    }
    public function report() {
        Library::log("report() begins");
        $request = \Drupal::request();
        $post_data_id = $request->get('id');
        $user_id = Library::myUid();
        return PostData::report($post_data_id, $user_id);
    }
}
