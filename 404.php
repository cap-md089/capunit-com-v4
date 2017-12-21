<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
            ob_start();
?><h2>This is not the page you are looking for</h2>
We are sorry, the page "<?php echo ltrim(explode("?", $_SERVER['REQUEST_URI'])[0], '/'); ?>" does not exist.<br />
<a href="#" onclick="history.go(-1);">Go back a page</a>.
<?php
            global $fromindex;
            if ($fromindex) {
                return [
                    'body' => ob_get_clean()
                ];
            } else {
                echo ob_get_clean();
            }
        }
    }
?>