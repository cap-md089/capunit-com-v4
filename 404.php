<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
            ob_start();
?><h2>This is not the page you are looking for</h2>
We are sorry, the page "<?php echo ltrim(explode("?", $_SERVER['REQUEST_URI'])[0], '/'); ?>" does not exist.<br />
<a href="#" onclick="history.go(-1);">Go back a page</a>.
<?php
            return [
                'body' => [
                    'MainBody' => ob_get_clean(),
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
                            'Text' => 'Home',
                            'Target' => '/'
                        ]
                    ])
                ]  
            ];
        }
    }
?>