<?php

    require_once(BASE_DIR."lib/templates.php");
    require_once(BASE_DIR."lib/general.php");
    
    class ErrOutput {
        public static function doGet ($ERROR) {
            global $_USER;
            $capid = isset($_USER) ? $_USER->uname : '0';
            $pdo = DB_Utils::CreateConnection();
            $stmt = $pdo->prepare('INSERT INTO '.DB_TABLES['ErrorMessages'].' (timestamp, enumber, errname, message, badfile, badline, context, capid) 
            VALUES
            (:timestamp, :enumber, :errname, :message, :badfile, :badline, :context, :cid);');
            $time = time();
            $stmt->bindValue(':enumber', $ERROR['enumber']);
            $stmt->bindValue(':errname', $ERROR['errname']);
            $stmt->bindValue(':message', $ERROR['message']);
            $stmt->bindValue(':badfile', $ERROR['badfile']);
            $stmt->bindValue(':badline', $ERROR['badline']);
            $stmt->bindValue(':context', print_r($ERROR['context'], true));
            $stmt->bindValue(':cid', $capid);
            $stmt->bindValue(':timestamp', $time);
            $val = $stmt->execute();
            $form = new AsyncForm('errremark', 'Would you tell us what you were trying to do?');
            $form->addField('remarks', 'Remarks', 'textarea');
            $form->addHiddenField('id', $pdo->lastInsertId());
            $form->setOption('reload', false);
            $html = '<h1>Uh oh! Something bad happened on our end...</h1>';
            $html .= $form->getHtml();
            global $_FUNC;
            $bc = UtilCollection::GenerateBreadCrumbs([
                [
                    'Target' => '/',
                    'Text' => 'Home'
                ],
                [
                    'Target' => '/'.$_FUNC,
                    'Text' => $_FUNC
                ]
            ]);
            $sn = UtilCollection::GenerateSideNavigation([]);
			$th = $html;
            $html = "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\n";
			$html .= "Name: MainBody\n\n$th\n\n";
			$html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\n";
			$html .= "Name: BreadCrumbs\n\n$bc\n\n";
			$html .= "--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING\n";
			$html .= "Name: SideNavigation\n\n$sn\n\n";

            return $html;
        }
    }
