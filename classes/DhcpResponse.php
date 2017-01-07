<?php
namespace Dhcp;

/**
 * Created by PhpStorm.
 * User: pajaja
 * Date: 12/31/2016
 * Time: 1:08 AM
 */
class DhcpResponse {

    /**
     * DhcpResponse constructor.
     */
    public function __construct () {
        $this->success = false;
        $this->code = 500;
        $this->messages = array ();
        $this->data = array ();
    }

    public function clearMessages(){
        $this->messages = array();
    }

    public function addMessage ($message) {
        try {
            $this->messages[] = strval ($message);
        }catch (\Exception $e){
            throw new \InvalidArgumentException("Message must be a string");
        }
    }

    public function getMessages(){
        return $this->messages;
    }

    public function success () {
        $this->success = true;
        $this->setCode(200);
    }

    public function isSuccessful () {
        return $this->success;
    }

    public function fail () {
        $this->success = false;
        $this->setCode(500);
    }

    public function setCode ($code) {
        if (Validator::validateHttpCode($code)) {
            $this->code = (int)$code;
        } else {
            throw new \InvalidArgumentException("Invalid status code");
        }
    }

    public function getCode ($default_code = 200) {
        if($this->code){
            return $this->code;
        }elseif(Validator::validateHttpCode($default_code)){
            return $default_code;
        }else{
            return 500;
        }
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function getJson() {

    }
}