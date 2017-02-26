<?php
/*class PollDeleteAction extends AdminActionHandler {

}*/

$validPollTypes = array('OPTION', 'NUMVAL', 'TEXT', 'MULTICHOICE');

class DeletePollAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        foreach ($_POST['polls'] as $id) {
            Poll::GetByID(intval($id))->Delete();
        }
    }
}

class DeletePollOptionsAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        $poll = Poll::GetByID(intval($_POST['poll']));
        if ($poll != null) {
            $poll->LoadOptions();
            foreach ($_POST['opts'] as $id) {
                $id=intval($id);
                $poll->options[$id]->Delete();
            }
        }
    }
}

class AddPollAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        $poll = new Poll();
        $poll->question = $_POST['question'];
        if (in_array($_POST['type'], $validPollTypes)) {
            UserError('Invalid poll type.');
        }
        $poll->type = $_POST['type'];
        switch ($poll->type) {
            //Polls that have enumerated options
            case "OPTION":
            case "MULTICHOICE":
                foreach ($_POST['answers'] as $answer) {
                      $poll->InsertTextOption($answer);
                }
                break;
        }
        $poll->Save();
    }
}

class EditPollAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        $poll = Poll::GetByID(intval($_POST['pollID']));
        $poll->question = $_POST['question'];
        if (in_array($_POST['type'], $validPollTypes)) {
            UserError('Invalid poll type.');
        }
        $poll->type = $_POST['type'];
        $poll->Save();
    }
}

class AddPollOptionAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        $poll = Poll::GetByID(intval($_POST['pollID']));
        $poll->LoadOptions();

        switch ($poll->type) {
            //Polls that have enumerated options
            case "OPTION":
            case "MULTICHOICE":
                // I think
                return $this->AddMultichoiceOption($poll);
            case "NUMVAL":
                return $this->AddNumvalOption();
            case "TEXT":
                return $this->AddTextOption();
        }
    }
}

class RemovePollOptionAction extends AdminActionHandler
{
    protected $requiredFlags = R_POLLING;
    public function onRequest()
    {
        $po = PollOption::GetByID(intval($_POST['pollID']), intval($_POST['rmpoll_o']));
        $po->Delete();
    }
}

class PollListPage extends Page
{
    public $relurl = '/poll';
    public $title = "Polls";
    public $image = "/img/polls.png";
    public function __construct()
    {
        parent::__construct();
        $this->RegisterAction('delpoll', new DeletePollAction($this, false));
        $this->RegisterAction('addpoll', new AddPollAction($this, false));
        $this->RegisterAction('editpoll', new EditPollAction($this, false));
    }

    // /poll == list of polls
    // /poll/1 == Poll details
    public function OnBody()
    {
        global $validPollTypes;
        $res = DB::Execute("SELECT * FROM erro_poll_question ORDER BY id DESC");
        if (!$res) {
            SQLError(DB::ErrorMsg());
        }
        $polls=[];
        foreach ($res as $row) {
            $polls[]=new Poll($row);
        }
        $this->setTemplateVar('polls', $polls);
        $this->setTemplateVar('validPollTypes', $validPollTypes);
        return $this->displayTemplate('web/polls/list');
    }

    public function OnHeader()
    {
        return '';
    }

    public function OptionForm(Poll $poll)
    {
        $form = new Form(fmtURL('poll'), 'post', 'optionform');
        $form->addHidden('act', 'updatepoll');
        $form->addHidden('pollID', $poll->ID);
        $fields['question']=$form->addTextbox('question');
        $fields['type']=$form->addSelect('type', $validPollTypes);
    }

    public function AddMultiChoiceOption(Poll $poll)
    {
        $npo = new PollOption();
        $npo->pollID = $poll->ID;
        $npo->text = $_POST['choice'];
        $npo->Insert();
    }
}

class PollDisplayPage extends Page
{
    public function __construct()
    {
        parent::__construct();
        $this->RegisterAction('delpoll_a', new DeletePollOptionsAction($this, false));
        $this->RegisterAction('addpoll_o', new AddPollOptionAction($this, false));
        $this->RegisterAction('rmpoll_o', new RemovePollOptionAction($this, false));
    }

    public function OnBody()
    {
        global $validPollTypes;
        $pollID = intval($this->request->param('pollid'));
        $poll = Poll::GetByID($pollID);
        if (!$poll) {
            UserError('Unable to find poll ' . $pollID);
        }
        $this->setTemplateVar('poll', $poll);
        $this->setTemplateVar('validPollTypes', $validPollTypes);
        $poll->LoadOptions();
        $responses=$poll->GetVotes();
        $this->setTemplateVar('responses', $responses);
        $this->setTemplateVar('totalRespondants', isset($responses['total']) ? $responses['total'] : []);
        $this->setTemplateVar('winningCount', isset($responses['winner']) ? $responses['winner'] : []);
        return $this->displayTemplate('web/polls/' . strtolower($poll->type));
    }
}

Router::Register('/poll/?', new PollListPage());
Router::Register('/poll/[i:pollid]/?', new PollDisplayPage());
