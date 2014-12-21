<?php
/*class PollDeleteAction extends AdminActionHandler {
	
}*/

$validPollTypes = array('OPTION', 'NUMVAL', 'TEXT', 'MULTICHOICE');

class poll_handler extends Page {
    public $parent = '/';
    public $title = "Bans";
    public $image = "/img/polls.png";

    // /poll == list of polls
    // /poll/1 == Poll details
    public function OnBody() {
        global $ADMIN_FLAGS, $validPollTypes;

        if (count($_POST) > 0 && $this->sess != false) {
            if (array_key_exists('delpoll', $_POST)) {
                foreach ($_POST['delpoll'] as $id) {
                    $poll = Poll::GetByID(intval($id));
                    $poll->Delete();
                }
            }
            if (array_key_exists('delpoll_a', $_POST)) {
                $poll = Poll::GetByID(intval($_POST['poll']));
                if ($poll != null) {
                	$poll->LoadOptions();
                    foreach ($_POST['delpoll_a'] as $id) {
                    	$id=intval($id);
                        $poll->options[$id]->Delete();
                    }
                }
            }
            if (array_key_exists('addpoll', $_POST)) {
                $poll = new Poll();
                $poll->question = $_POST['question'];
                if (in_array($_POST['type'], $validPollTypes))
                    UserError('Invalid poll type.');
                $poll->type = $_POST['type'];
                switch($poll->type) {
                    //Polls that have enumerated options
                    case "OPTION" :
                    case "MULTICHOICE" :
						foreach($_POST['answers'] as $answer){
							$poll->InsertTextOption($answer);
						}
						break;
				}
                $poll->Save();
            }
            if (array_key_exists('editpoll', $_POST)) {
                $poll = Poll::GetByID(intval($_POST['pollID']));
                $poll->question = $_POST['question'];
                if (in_array($_POST['type'], $validPollTypes))
                    UserError('Invalid poll type.');
                $poll->type = $_POST['type'];
                $poll->Save();
            }
            if (array_key_exists('addpoll_o', $_POST)) {
                $poll = Poll::GetByID(intval($_POST['pollID']));
                $poll->LoadOptions();

                switch($poll->type) {
                    //Polls that have enumerated options
                    case "OPTION" :
                    case "MULTICHOICE" :
                        // I think
                        return $this->AddMultichoiceOption($poll);
                    case "NUMVAL" :
                        return $this->AddNumvalOption();
                    case "TEXT" :
                        return $this->AddTextOption();
                }
            }
            if (array_key_exists('rmpoll_o', $_POST)) {
                $po = PollOption::GetByID(intval($_POST['pollID']),intval($_POST['rmpoll_o']));
				$po->Delete();
				
			}
        }
        //$db->debug=true;
        if (count($this->path) == 1) {
            $res = DB::Execute("SELECT * FROM erro_poll_question ORDER BY id DESC");
            if (!$res)
                SQLError(DB::ErrorMsg());
            $this->setTemplateVar('polls', $res);
            $this->setTemplateVar('validPollTypes', $validPollTypes);
            return $this->displayTemplate('web/polls/list.tpl.php');
        } else if (count($this->path) > 1) {
            $pollID = intval($this->path[1]);
            $poll = Poll::GetByID($pollID);
            if (!$poll)
                UserError('Unable to find poll ' . $pollID);
            $this->setTemplateVar('poll', $poll);
            return $this->displayTemplate('web/polls/' . strtolower($poll->type) . '.tpl.php');
        }
    }

    public function OnHeader() {
        return '';
    }
	
	public function OptionForm(Poll $poll){
		$form = new Form(fmtURL('poll'),'post','optionform');
		$form->addHidden('act', 'updatepoll');
		$form->addHidden('pollID', $poll->ID);
		$fields['question']=$form->addTextbox('question');
		$fields['type']=$form->addSelect('type', $validPollTypes);
	}

    public function AddMultiChoiceOption(Poll $poll) {
		$npo = new PollOption();
		$npo->pollID = $poll->ID;
		$npo->text = $_POST['choice'];
		$npo->Insert();
    }

}

Page::Register('web_poll', new poll_handler);
