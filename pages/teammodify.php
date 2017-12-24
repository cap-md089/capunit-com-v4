<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
            if (!$l) return false;
            if (!$m->hasPermission('EditTeam')) return ['error' => 402];
			if (!$a->paid) {return ['error' => 501];}

            $butt = new AsyncButton (Null, "Select", 'teamCreateAddUser');

            $team = Team::Get($e['uri'][0]);

            $form = new AsyncForm(Null, 'Edit Team');
            $form
                ->addField('teamName', 'Team name', 'text', Null, Null, $team->Name)
                ->addField('description', 'Description', 'textarea', Null, Null, $team->Description)
                ->addField('teamMentor', 'Team Mentor ('.$butt->getHtml('mentor').')', 'text', 'mentor', Null, $team->Mentor)
                ->addField('teamCoach', 'Team Coach ('.$butt->getHtml('coach').')', 'text', 'coach', Null, $team->Coach)
                ->addField('teamLeader', 'Team Leader ('.$butt->getHtml('leader').')', 'text', 'leader', Null, $team->Lead)
                ->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeTeamUserMultiAdd'))->getHtml(), Null, Null, Null, Null, 'templateAdder')
                ->addField('roles[]', 'Role', 'text', Null, Null, Null, 'templateAdder');
            
            foreach ($team->Members as $cid => $role) {
                $form
                    ->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeTeamUserMultiAdd'))->getHtml(), Null, Null, Null, $cid)
                    ->addField('roles[]', 'Role', 'text', Null, Null, $role);
            }
            
            $form->addField('', (new AsyncButton(Null, "Add Team Member", "addUserToTeam"))->getHtml(), 'textread', Null, Null, Null, Null);

            $form->addHiddenField('teamid', $e['uri'][0]);

            $form->reload = false;

            return [
                'body' => $form.'',
                'title' => "Add a team"
            ];
        }

        public static function doPost ($e, $c, $l, $m, $a) {
            if (!$l) return false;
            if (!$m->hasPermission('EditTeam')) return ['error' => 402];
			if (!$a->paid) {return ['error' => 501];}

            $team = Team::Get($e['form-data']['teamid']);
            $team->set(array(
                'TeamLead' => $e['form-data']['teamLeader'],
                'TeamName' => $e['form-data']['teamName'],
                'TeamDescription' => $e['form-data']['description'],
                'TeamCoach' => $e['form-data']['teamCoach'],
                'TeamMentor' => $e['form-data']['teamMentor']
            ));
            
            foreach ($team->Members as $cid => $role) {
                $team->Members->remove(Member::Estimate($cid));
            }

            for ($i = 1; $i < count($e['form-data']['capids']); $i++) {
                $team->Members->add(Member::Estimate($e['form-data']['capids'][$i]), $e['form-data']['roles'][$i]);
            }

            return "Team updated";
        }
    }