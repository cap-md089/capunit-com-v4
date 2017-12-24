<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
            if (!$l) return false;
            if (!$m->hasPermission("FileManagement")) return ['error' => 402];

            $form = new AsyncForm ();
            $form->addField("files", "Add a file", "file")->addField('comment', 'Comments?', 'textarea')->
            addHiddenField('func', 'upload');
            $form->reload = true;
            $html = $form->getHtml();
            $pdo = DBUtils::CreateConnection();
            $stmt = $pdo->prepare("SELECT ID FROM FileData WHERE AccountID = :aid OR AccountID = 'www';");
            $stmt->bindValue(':aid', $a->id);
            $data = DBUtils::ExecutePDOStatement($stmt);

            $html .= "<div id=\"filelist\">";

            $template = '<div class="file"><div class="img"><img src="%s" /></div><div class="desc"><h2 class="title">%s</h2><ul>
            <li>File size: %s</li><li>Download link: %s</a></li><li>File ID: %s</li><li>%s</li>
            </ul>%s</div></div>';

            $butt = new AsyncButton(Null, "Delete file", "fileDeleted"); 

            foreach ($data as $datum) {
                $f = File::Get($datum['ID']);

                $url = $f->IsPhoto ? '/filedownloader/'.$f->ID.'?ajax=true' : '/images/doc.png';

                $fd = new FileDownloader('Save file', $f->ID);

                $fo = new AsyncForm();
                $fo->addField("comments", "Comments:", "textarea", Null, Null, $f->Comments)
                    ->addHiddenField("fileID", $f->ID)
                    ->addHiddenField("func", "revise");
                $fo->reload = true;

                $html .= sprintf($template, $url, $f->Name, 
                    UtilCollection::formatSizeUnits($f->Size), $fd,
                    $f->ID, $butt->getHtml($f->ID), $fo->getHtml());
            }

            $html .= "</div>";

            return [
                'body' => $html,
                'title' => 'File Manager'
            ];
        }
            
        public static function doPost ($e, $c, $l, $m, $a) {
            if (!$l) return false;
            if (!$m->hasPermission("FileManagement")) return ['error' => 402];
            if ($e['raw']['func'] == 'upload') return '';
            else if ($e['raw']['func'] == 'revise') {
                $f = File::Get($e['raw']['fileID']);
                if ($f && ($f->AccountID !== 'www' || $m->uname == 542488)) {
                    $f->Comments = $e['raw']['comments'];
                    $f->save();
                }
            }
        }

        public static function doPut ($e, $c, $l, $m, $a) {
            if (!$l) return false;
            if (!$m->hasPermission("FileManagement")) return ['error' => 402];
            
            $f = File::Get($e['raw']['data']);
            if ($f->AccountID == 'www') {
                return "That file cannot be deleted";
            }
            return $f->remove() ? "File removed" : "Something went wrong";
        }
    }