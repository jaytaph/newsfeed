<?php

namespace Noxlogic\FeedBundle\RssAtom\Driver;

use Debril\RssAtomBundle\Driver\HttpCurlDriver;

class Utf8HttpCurlDriver extends HttpCurlDriver {

    public function getHttpResponse($headerString, $body)
    {
        $response = parent::getHttpResponse($headerString, $body);
        $response->setBody(utf8_encode($response->getBody()));
        return $response;
    }

}
