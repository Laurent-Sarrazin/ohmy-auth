<?php namespace ohmy\Auth1\Flow;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth1\Flow,
    ohmy\Auth1\Security\Signature,
    ohmy\Auth1\Flow\ThreeLegged\Request,
    ohmy\Http\Rest;

class ThreeLegged extends Flow {

    private $client;

    public function __construct($callback, Rest $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function request($url, $options=array()) {

        return (new Request(function($resolve, $reject) use($url, $options) {

            if ($this->value['oauth_token']) {
                $resolve($this->value);
                return;
            }

            $signature = new Signature(
                'POST',
                $url,
                array_intersect_key(
                    $this->value,
                    array_flip(array(
                        'oauth_callback',
                        'oauth_consumer_key',
                        'oauth_consumer_secret',
                        'oauth_nonce',
                        'oauth_timestamp',
                        'oauth_signature_method',
                        'oauth_version'
                    ))
                )
            );

            $this->client->POST($url, array(), array(
                'Authorization'  => $signature,
                'Content-Length' => 0
            ))
            ->then(function($response) use($resolve) {
                $resolve($response->text());
            });

        }, $this->client))

    ->then(function($data) {
            if (is_array($data)) return $data;
            parse_str($data, $array);
            $_SESSION['oauth_token_secret'] = $array['oauth_token_secret'];
            return array_merge($this->value, $array);
        });
    }

}