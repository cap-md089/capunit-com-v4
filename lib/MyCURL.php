<?php
    /**
     * @package lib/DB_Utils
     *
     * A wrapper for cURL for those who can't wrap their heads around cURL
     *
     * @author Andrew Rioux <arioux303931@gmail.com>
     *
     * @copyright 2016-2017 Rioux Development Team
     */

    /**
     * A wrapper for cURL for those who can't wrap their heads around cURL
     */
    class MyCURL {
        /**
         * Creates a handle for the class
         */
        public function __construct () {
            $this->handle = curl_init();
        }

        /**
         * Clean up
         */
        public function destroy () {
            if (isset($this->handle)) {
                curl_close($this->handle);
                $this->handle = null;
            }
        }

        /**
         * Also clean up
         */
        public function __destruct () {
            $this->destroy();
        }

        /**
         * Sets options for the cURL handle
         *
         * @param array $opts An associative array of opts, see curl_setopt_array (http://php.net/manual/en/function.curl-setopt-array.php)
         * @param bool $dev Should there be verbose printing? Prints HTTP headers sent/received, etc
         */
        public function setOpts ($opts, $dev=Null) {
            curl_setopt_array($this->handle, $opts);
            if (isset($dev)) {
                curl_setopt($this->handle, CURLOPT_VERBOSE, $dev);
            }
        }

        /**
         * Downloads a page using the current options set
         *
         * For data returned, see curl_getinfo (http://php.net/manual/en/function.curl-getinfo.php)
         *
         * @param str $url URL to load
         * @param bool $keepalive Self-destructs after making request if set to false
         *
         * @return array Associative array of curl_getinfo, with 'raw_response' being entire transaction, 'headers' being headers received, 'body' being the response
         */
        public function download ($url, $keepalive=false) {
            $this->setOpts(array (
                CURLOPT_URL => $url,
                CURLOPT_HEADER => true,
                CURLOPT_RETURNTRANSFER => true
            ));
            $resp = curl_exec($this->handle);
            $hs = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);
            $data = curl_getinfo($this->handle);
            $data['headers'] = Util_Collection::ParseHeaders(substr($resp, 0, $hs));
            $data['body'] = substr($resp, $hs);
            $data['raw_response'] = $resp;
            if (!$keepalive) {
                $this->destroy();
            }
            return $data;
        }
    }
?>
