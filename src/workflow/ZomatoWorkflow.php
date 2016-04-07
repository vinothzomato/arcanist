<?php

final class ZomatoWorkflow extends ArcanistWorkflow {

	private $console;
	private $diffID;
  	private $revisionID;
  	private $haveUncommittedChanges = false;

	public function getWorkflowName() {
		return 'z';
	}

	public function getCommandSynopses() {
    	return phutil_console_format(<<<EOTEXT
      	**z** [__branch__] (git)
EOTEXT
      );
  	}

  	public function getCommandHelp() {
    	return phutil_console_format(<<<EOTEXT
          Supports: git
          Generate a Differential diff or revision from local changes.

          Under git, you can specify a branch (like __master__) and Differential will generate a diff against the
          merge base of that commit and your current working directory parent.
EOTEXT
      	);
  	}

  	public function requiresWorkingCopy() {
  		return true;
  	}

  	public function requiresConduit() {
  		return true;
  	}

  	public function requiresAuthentication() {
  		return true;
  	}

  	public function requiresRepositoryAPI() {
  		return true;
  	}

  	public function getArguments() {
  		return array(
  			'create' => array(
  				'short' => 'c',
  				'conflicts' => array(
  					'update'    => pht(
  						'%s can not be used with %s.',
  						'--create',
  						'--update'),
  					),
  				'help' => pht(
  					'Create a revision.'),
  				),
  			'update' => array(
  				'short' => 'u',
  				'help' => pht(
  					'Update the revision'),
  				),
  			'message' => array(
  				'short' => 'm',
  				'help' => pht(
  					'Message while updating the revision'),
  				),
  			'title' => array(
  				'short' => 't',
  				'help' => pht(
  					'Title for the revision while creating a new revision'),
  				),
  			'summary' => array(
  				'short' => 's',
  				'help' => pht(
  					'Summary for the revision while creating a new revision'),
  				),
  			'plan' => array(
  				'short' => 'p',
  				'help' => pht(
  					'Plan for the revision while creating a new revision'),
  				),
  			);
  	}

  	public function run() {
  		$this->console = PhutilConsole::getConsole();

  		if ($this->getArgument('create')) {


  		}
  		else if($this->getArgument('update')){

  		}
  		else{
  			echo $this->getCommandHelp();
  		}
  		echo "\n";
  	}
}

?>