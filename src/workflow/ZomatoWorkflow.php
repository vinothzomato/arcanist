<?php

final class ZomatoWorkflow extends ArcanistWorkflow {

	private $console;
	private $diffID;
  private $revisionID;
  private $unresolvedLint;
  private $haveUncommittedChanges = false;

  const BASE_CONFIGKEY = 'zomato.base';
  const PROJECT_CONFIGKEY = 'project.id';
  const PUSH_ORIGIN_CONFIGKEY = 'push.origin';
  const REPOSITORY_CONFIGKEY = 'repository.id';

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
  					'Update the revision. '),
  				),
        'accept' => array(
          'short' => 'a',
          'help' => pht(
            'Accept the revision. '),
          ),
        'reject' => array(
          'short' => 'r',
          'help' => pht(
            'Reject the revision. '),
          ),
        'comment' => array(
          'short' => 'com',
          'help' => pht(
            'Add comment to the revision. '),
          ),
        'revision' => array(
          'param' => 'message',
          'short' => 'rev',
          'help' => pht(
            'Revision ID for accept or reject. '),
          ),
  			'commit-push' => array(
  				'conflicts' => array(
  					'push'    => pht(
  						'%s can not be used with %s.',
  						'--commit-push',
  						'--push'),
  					'add-commit-push'    => pht(
  						'%s can not be used with %s.',
  						'--commit-push',
  						'--add-commit-push'),
  					),
  				'short' => 'cp',
  				'help' => pht(
  					'Commit and Push the branch then do the arc commands'),
  				),
  			'add-commit-push' => array(
  				'short' => 'acp',
  				'help' => pht(
  					'Add files,commit and push the branch then do the arc commands'),
  				),
  			'push' => array(
  				'short' => 'zp',
  				'help' => pht(
  					'Push the branch then do the arc commands'),
  				),
  			'commit-message' => array(
  				'param' => 'message',
  				'short' => 'cm',
  				'help' => pht(
  					'Commit message for the branch'),
  				),
        'project' => array(
          'param' => 'message',
          'short' => 'p',
          'help' => pht(
            'Project Id for the revision'),
          ),
  			'message' => array(
  				'short' => 'm',
  				'param' => 'message',
  				'help' => pht(
  					'Message while updating the revision'),
  				),
  			'title' => array(
  				'short' => 't',
  				'param' => 'message',
  				'help' => pht(
  					'Title for the revision while creating a new revision'),
  				),
  			'summary' => array(
  				'short' => 's',
  				'param' => 'message',
  				'help' => pht(
  					'Summary for the revision while creating a new revision'),
  				),
  			'test-plan' => array(
  				'short' => 'tp',
  				'param' => 'message',
  				'help' => pht(
  					'Plan for the revision while creating a new revision'),
  				),
        'excuse' => array(
          'short' => 'e',
          'param' => 'message',
          'help' => pht(
            'Excuse message for lint errors'),
          ),
        'nolint' => array(
          'short' => 'nl',
          'help' => pht(
            'Skip linting.'),
          ),
  			);
  	}

  	public function run() {
  		$this->console = PhutilConsole::getConsole();
      $conduit = $this->getConduit();

      if ($this->getArgument('accept')) {
        if (!$this->getArgument('revision')) {
          echo pht("Need revision id for accept. Please provide --revision or -rev \n");
          exit(1);
        }
        $revision = $this->getArgument('revision');
        $message = $this->getArgument('message');
        $result = $conduit->callMethodSynchronous(
          'differential.createcomment',
          array(
            'revision_id' =>  $revision,
            'action' => 'accept',
            'message' => $message,
            ));
        var_dump($result);
        exit(0);
      }
      else if($this->getArgument('reject')){
        if (!$this->getArgument('revision')) {
          echo pht("Need revision id to reject. Please provide --revision or -rev \n");
          exit(1);
        }
        $revision = $this->getArgument('revision');
        $message = $this->getArgument('message');
        $result = $conduit->callMethodSynchronous(
          'differential.createcomment',
          array(
            'revision_id' =>  $revision,
            'action' => 'reject',
            'message' => $message,
            ));
        var_dump($result);
        exit(0);
      }

      else if($this->getArgument('comment')){
        if (!$this->getArgument('revision')) {
          echo pht("Need revision id to comment. Please provide --revision or -rev \n");
          exit(1);
        }
        $revision = $this->getArgument('revision');
        $message = $this->getArgument('message');
        $result = $conduit->callMethodSynchronous(
          'differential.createcomment',
          array(
            'revision_id' =>  $revision,
            'message' => $message,
            ));
        var_dump($result);
        exit(0);
      }

      //$data = $this->runLintUnit();
      //$lint_result = $data['lintResult'];
      $lint_result = ArcanistLintWorkflow::RESULT_SKIP;
      $excuse = $this->getArgument('excuse');
      if ($lint_result === ArcanistLintWorkflow::RESULT_ERRORS && $excuse === null) {
        echo pht("Lint has errors. Please fix the lint issues or provide excuse with --excuse \n");
        exit(1);
      }
  		$console = $this->console;
  		$repository = $this->getRepositoryAPI();
  		$base = $this->getConfigFromAnySource(self::BASE_CONFIGKEY);
      $origin = $this->getConfigFromAnySource(self::PUSH_ORIGIN_CONFIGKEY) ? $this->getConfigFromAnySource(self::PUSH_ORIGIN_CONFIGKEY) : 'origin';
      $projectId = $this->getArgument('project') ? $this->getArgument('project') : $this->getConfigFromAnySource(self::PROJECT_CONFIGKEY);
  		$repoId = $this->getConfigFromAnySource(self::REPOSITORY_CONFIGKEY);
  		$branch = $repository->getBranchName();
  		$repo = $repository->getRemoteURI();
  		$repoURL = null;
  		$pattern = "/git@github\.com:[^\/]*\/(.*).git/";
    	preg_match($pattern, $repo, $matches);
    	if (isset($matches[1])) {
    		$repoURL = "https://api.github.com/repos/Zomato/".$matches[1];
    	}
    	else{
    		$pattern = "/https:\/\/github\.com\/[^\/]*\/(.*).git/";
    		preg_match($pattern, $repo, $matches);
    		if (isset($matches[1])) {
    			$repoURL = "https://api.github.com/repos/Zomato/".$matches[1];
    		}
    	}
    	
    	if (!$repoURL) {
    		echo pht("Something is wrong. Please contact vinoth.kumar@zomato.com.");
  			exit(1);
    	}

  		if (!strlen($base)) {
  			echo pht("zomato.base key not found in your local configuration please add zomato.base key to your .arcconfig file \n");
  			exit(1);
  		}

  		if (!strlen($projectId)) {
  			echo pht("project.id key not found in your local configuration please add project.id key to your .arcconfig file \n");
  			exit(1);
  		}

  		if (!strlen($repoId)) {
  			echo pht("repository.id key not found in your local configuration please add repository.id key to your .arcconfig file \n");
  			exit(1);
  		}

      $base_origin = $this->getConfigFromAnySource('base');
      $matches = null;
      if (preg_match('/^git:merge-base\((.+)\)/', $base_origin, $matches)) {
        $bases = explode("/", $matches[1]);
        $repository->execPassthru('fetch --quiet '.$bases[0].' '.$bases[1]);
      }
      else{
        $repository->execPassthru('fetch zomato_origin master');
      }

  		if ($this->getArgument('add-commit-push')) {
  			$message = $this->getArgument('commit-message');
  			
  			if (!strlen($message)) {
  				$message = $this->getArgument('message');
  			}
  			if (!strlen($message)) {
  				$message = $this->getArgument('title');
  			}		

  			if (!strlen($message)) {
  				echo pht("We need message to commit pass --message \n");
  				exit(1);
  			}
  			$repository->execPassthru('commit -am "'.$message.'"');
  			$repository->execPassthru('push '.$origin.' '.$branch);
  		}	
		  else if ($this->getArgument('commit-push')) {
  			$message = $this->getArgument('commit-message');
  			
  			if (!strlen($message)) {
  				$message = $this->getArgument('message');
  			}
  			if (!strlen($message)) {
  				$message = $this->getArgument('title');
  			}		

  			if (!strlen($message)) {
  				echo pht("We need message to commit pass --message \n");
  				exit(1);
  			}
  			$repository->execPassthru('commit -m "'.$message.'"');
  			$repository->execPassthru('push '.$origin.' '.$branch);
  		}
  		else if ($this->getArgument('push')) {
  			$repository->execPassthru('push '.$origin.' '.$branch);
  		}
      else {
        $repository->execPassthru('push '.$origin.' '.$branch);
      }

      $diff_params = array(
        'repo' => $repoURL,
        'base' => $base,
        'head' => $branch,
        'projectId' => $projectId,
        );

      // $diff_result = $conduit->callMethodSynchronous(
      //     'zomato.getdiff',
      //     $diff_params);
      // $diff = $diff_result['diff'];

      $diff = $repository->getFullGitDiff(
       $repository->getBaseCommit(),
        $repository->getHeadCommit());

      if (!strlen($diff)) {
        echo pht("No changes found. \n");
        exit(1);
      }

      $parser = $this->newDiffParser();
      $changes = $parser->parseDiff($diff);

  		if ($this->getArgument('create')) {
  			$title = $this->getArgument('title');
  			if (!strlen($title)) {
  				echo pht("No title for revision. Please provide --title with --create \n");
  				exit(1);
  			}
  			$summary = $this->getArgument('summary') ? $this->getArgument('summary') : 'No summary';
  			$plan = $this->getArgument('plan') ? $this->getArgument('plan') : 'No plan';

  			$fields = array(
  				'title' => $title, 
  				'summary' => $summary, 
  				'testPlan' => $plan, 
  				);

  			$revision = array(
          'changes' => mpull($changes, 'toDictionary'),
//          'diff' => $diff,
  				'fields' => $fields, 
  				'repo' => $repoURL,
  				'base' => $base,
  				'head' => $branch,
  				'repoId' => $repoId,
  				'projectId' => $projectId,
          'lintStatus' => $this->getLintStatus($lint_result),
  				) + $this->buildDiffSpecification();

  			$result = $conduit->callMethodSynchronous(
  				'zomato.createrevision',
  				$revision);
  			$uri = $result['uri'];
  			echo phutil_console_format(
  				"        **%s** __%s__\n\n",
  				pht('Revision URI:'),
  				$uri);
  		}
  		else if($this->getArgument('update')){
  			$message = $this->getArgument('message') ? $this->getArgument('message') : 'No update message';
  			$revision = array(
          'changes' => mpull($changes, 'toDictionary'),
//          'diff' => $diff,
  				'repo' => $repoURL,
  				'base' => $base,
  				'head' => $branch,
  				'message' => $message,
  				) + $this->buildDiffSpecification();
  			$result = $conduit->callMethodSynchronous(
  				'zomato.updaterevision',
  				$revision);
  			$uri = $result['uri'];
  			echo phutil_console_format(
  				"        **%s** __%s__\n\n",
  				pht('Revision Updated:'),
  				$uri);
  		}
  		else{
  			echo $this->getCommandHelp();
  		}
  		echo "\n";
  	}

  /* -(  Diff Specification  )------------------------------------------------- */

  /**
   * @task diffspec
   */
  private function getLintStatus($lint_result) {
    $map = array(
      ArcanistLintWorkflow::RESULT_OKAY       => 'okay',
      ArcanistLintWorkflow::RESULT_ERRORS     => 'fail',
      ArcanistLintWorkflow::RESULT_WARNINGS   => 'warn',
      ArcanistLintWorkflow::RESULT_SKIP       => 'skip',
    );
    return idx($map, $lint_result, 'none');
  }

  /**
   * @task diffspec
   */
  private function getUnitStatus($unit_result) {
    $map = array(
      ArcanistUnitWorkflow::RESULT_OKAY       => 'okay',
      ArcanistUnitWorkflow::RESULT_FAIL       => 'fail',
      ArcanistUnitWorkflow::RESULT_UNSOUND    => 'warn',
      ArcanistUnitWorkflow::RESULT_SKIP       => 'skip',
    );
    return idx($map, $unit_result, 'none');
  }

  /* -(  Lint and Unit Tests  )------------------------------------------------ */


  /**
   * @task lintunit
   */
  private function runLintUnit() {
    $lint_result = $this->runLint();
    //$unit_result = $this->runUnit();
    return array(
      'lintResult' => $lint_result,
      'unresolvedLint' => $this->unresolvedLint,
      //'unitResult' => $unit_result,
      //'testResults' => $this->testResults,
    );
  }


  /**
   * @task lintunit
   */
  private function runLint() {
    if ($this->getArgument('nolint')) {
      return ArcanistLintWorkflow::RESULT_SKIP;
    }

    $repository_api = $this->getRepositoryAPI();

    $this->console->writeOut("%s\n", pht('Linting...'));
    try {
      $argv = $this->getPassthruArgumentsAsArgv('lint');
      if ($repository_api->supportsCommitRanges()) {
        $argv[] = '--rev';
        $argv[] = $repository_api->getBaseCommit();
      }

      $lint_workflow = $this->buildChildWorkflow('lint', $argv);
      $lint_result = $lint_workflow->run();

      switch ($lint_result) {
        case ArcanistLintWorkflow::RESULT_OKAY:
          if ($lint_workflow->getUnresolvedMessages()) {
            $this->console->writeOut(
              "<bg:red>** %s **</bg> %s\n",
              pht('LINT ERRORS'),
              pht('Lint raised errors!'));
          } else {
            $this->console->writeOut(
              "<bg:green>** %s **</bg> %s\n",
              pht('LINT OKAY'),
              pht('No lint problems.'));
          }
          break;
        case ArcanistLintWorkflow::RESULT_WARNINGS:
          $this->console->writeOut(
            "<bg:yellow>** %s **</bg> %s\n",
            pht('LINT WARNINGS'),
            pht('Lint raised warnings!'));
          break;
        case ArcanistLintWorkflow::RESULT_ERRORS:
          $this->console->writeOut(
            "<bg:red>** %s **</bg> %s\n",
            pht('LINT ERRORS'),
            pht('Lint raised errors!'));
          break;
      }

      $this->unresolvedLint = array();
      foreach ($lint_workflow->getUnresolvedMessages() as $message) {
        $this->unresolvedLint[] = $message->toDictionary();
      }
      return $lint_result;
    } catch (ArcanistNoEngineException $ex) {
      $this->console->writeOut(
        "%s\n",
        pht('No lint engine configured for this project.'));
    } catch (ArcanistNoEffectException $ex) {
      $this->console->writeOut("%s\n", $ex->getMessage());
    }

    return null;
  }

    /**
   * @task diffspec
   */
  private function buildDiffSpecification() {

    $base_revision  = null;
    $base_path      = null;
    $vcs            = null;
    $repo_uuid      = null;
    $parent         = null;
    $source_path    = null;
    $branch         = null;
    $bookmark       = null;

    $repository_api = $this->getRepositoryAPI();

    $base_revision  = $repository_api->getSourceControlBaseRevision();
    $base_path      = $repository_api->getSourceControlPath();
    $vcs            = $repository_api->getSourceControlSystemName();
    $source_path    = $repository_api->getPath();
    $branch         = $repository_api->getBranchName();
    $repo_uuid      = $repository_api->getRepositoryUUID();

    // if ($repository_api instanceof ArcanistGitAPI) {
    //   $info = $this->getGitParentLogInfo();
    //   if ($info['parent']) {
    //     $parent = $info['parent'];
    //   }
    //   if ($info['base_revision']) {
    //     $base_revision = $info['base_revision'];
    //   }
    //   if ($info['base_path']) {
    //     $base_path = $info['base_path'];
    //   }
    //   if ($info['uuid']) {
    //     $repo_uuid = $info['uuid'];
    //   }
    // } 

    $data = array(
      'sourceMachine'             => php_uname('n'),
      'sourcePath'                => $source_path,
      'branch'                    => $branch,
      'bookmark'                  => $bookmark,
      'sourceControlSystem'       => $vcs,
      'sourceControlPath'         => $base_path,
      'sourceControlBaseRevision' => $base_revision,
      'creationMethod'            => 'arc z',
    );

    // $repository_phid = $this->getRepositoryPHID();
    // if ($repository_phid) {
    //   $data['repositoryPHID'] = $repository_phid;
    // }

    return $data;
  }
}

?>