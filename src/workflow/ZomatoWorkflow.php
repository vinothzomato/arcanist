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
  		return !$this->isRawDiffSource();
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
  			'show' => array(
  				'help' => pht(
  					'Show the amended commit message, without modifying the '.
  					'working copy.'),
  				),
  			'revision' => array(
  				'param' => 'revision_id',
  				'help' => pht(
  					'Use the message from a specific revision. If you do not specify '.
  					'a revision, arc will guess which revision is in the working '.
  					'copy.'),
  				),
  			);
  	}

  	public function run() {
  		$this->console = PhutilConsole::getConsole();
  		echo 'test';
  	}
}

?>