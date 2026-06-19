<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppKernel\Plugin\Converter;

use AppKernel\Plugin\PluginInterface;

class Pdf implements PluginInterface {

    public function convert($content) {
        echo 'pdf convert here';
        //implementation of convert $content to convert content into pdf
    }

}
