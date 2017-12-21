<?php

    require_once(BASE_DIR."lib/templates.php");
    
    class ErrOutput {
        public static function doGet ($ERROR) {
            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['ErrorMessages'].' (timestamp, enumber, errname, message, badfile, badline, context) 
            VALUES
            (:timestamp, :enumber, :errname, :message, :badfile, :badline, :context);');
            $time = time();
            $stmt->bindValue(':enumber', $ERROR['enumber']);
            $stmt->bindValue(':errname', $ERROR['errname']);
            $stmt->bindValue(':message', $ERROR['message']);
            $stmt->bindValue(':badfile', $ERROR['badfile']);
            $stmt->bindValue(':badline', $ERROR['badline']);
            $stmt->bindValue(':context', print_r($ERROR['context'], true));
            $stmt->bindValue(':timestamp', $time);
            $val = $stmt->execute();
            $form = new AsyncForm('errremark', 'Would you tell us what you were trying to do?');
            $form->addField('remarks', 'Remarks', 'textarea');
            $form->addHiddenField('id', $pdo->lastInsertId());
            $form->setOption('reload', false);
            $html = '<h1>Uh oh! Something bad happened on the server...</h1>';
            $html .= $form->getHtml();
            return $html;
        }
    }
?>