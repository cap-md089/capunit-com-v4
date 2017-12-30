<?php
    class Output {
        public static function doGet ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
            if (!$l) return false;
            if (!$m->hasPermission('AddTeam')) return ['error' => 402];

            $butt = new AsyncButton (Null, "Select", 'teamCreateAddUser');

            $form = new AsyncForm(Null, 'Create Team');
            $form
                ->addField('teamName', 'Team name', 'text', Null, Null)
                ->addField('description', 'Description', 'textarea', Null, Null)
                ->addField('teamMentor', 'Team Mentor ('.$butt->getHtml('mentor').')', 'text', 'mentor', Null)
                ->addField('teamCoach', 'Team Coach ('.$butt->getHtml('coach').')', 'text', 'coach', Null)
                ->addField('teamLeader', 'Team Leader ('.$butt->getHtml('leader').')', 'text', 'leader', Null)
                ->addField("capids[]", (new AsyncButton(Null, 'Remove person', 'removeTeamUserMultiAdd'))->getHtml(), Null, Null, Null, Null, 'templateAdder')
                ->addField('roles[]', 'Role', 'text', Null, Null, Null, 'templateAdder')
                ->addField('', (new AsyncButton(Null, "Add Team Member", "addUserToTeam"))->getHtml(), 'textread', Null, Null, Null, Null);

            $form->reload = false;

            return [
                'body' => [
                    'MainBody' => $form.'',
                    'BreadCrumbs' => UtilCollection::GenerateBreadCrumbs([
                        [
							'Target' => '/',
							'Text' => 'Home'
						],
						[
							'Target' => '/admin',
							'Text' => 'Administration'
						],
						[
							'Target' => '/teamadd',
							'Text' => 'Add a team'
						]
                    ])
                ],
                'title' => "Add a team"
            ];
        }

        public static function doPost ($e, $c, $l, $m, $a) {
			if (!$a->paid) {return ['error' => 501];}
            if (!$l) return false;
            if (!$m->hasPermission('AddTeam')) return ['error' => 402];

            $team = Team::Create(array(
                'TeamLead' => $e['form-data']['teamLeader'],
                'TeamName' => $e['form-data']['teamName'],
                'TeamDescription' => $e['form-data']['description'],
                'TeamCoach' => $e['form-data']['teamCoach'],
                'TeamMentor' => $e['form-data']['teamMentor']
            ));

            for ($i = 1; $i < count($e['form-data']['capids']); $i++) {
                $team->Members->add(Member::Estimate($e['form-data']['capids'][$i]), $e['form-data']['roles'][$i]);
            }
            

            return "Team created";
        }
    }