<?php

namespace CI\Commands;

use CI\Utils\Comments;
use Symfony\Component\Yaml\Yaml;

class TravisCommands
{
    /**
     * Set up this project to auto-update via the
     * Composer Lock Update command on Travis.
     *
     * @command travis:clu
     */
    public function travisComposerLockUpdate($options = ['auto-merge' => false])
    {
        // TODO: Sanity checks
        // - There must be a composer.json
        // - The composer.lock file must not be in the .gitignore file
        $travisConfigPath = '.travis.yml';
        $travisContents = $this->readTravisConfig($travisConfigPath);

        // TODO: Should we allow folks to turn on auto-update if they
        // previously configured their project without it?
        if ($this->hasCLUConfig($travisContents)) {
            throw new \Exception("Composer Lock Update already configured in " . $travisConfigPath);
        }

        $alteredContents = $this->configureCLU($travisContents, $options['auto-merge']);

        $commentManager = new Comments();
        $commentManager->collect(explode("\n", $travisContents));
        $withComments = $commentManager->inject(explode("\n", $alteredContents));

        $result = implode("\n", $withComments);
        file_put_contents($travisConfigPath, $result);

        $this->setEnvironment();
    }

    protected function setEnvironment()
    {
        exec('which travis', $outputOfWhich, $return_code);
        if ($return_code) {
            // TODO: warn the user that `travis` tool is not available
            return;
        }
        // TODO: If $GITHUB_TOKEN is defined, then run
        // `travis env set GITHUB_TOKEN $GITHUB_TOKEN`
        $github_token = getenv('GITHUB_TOKEN');
        if (!$github_token) {
            // TODO: warn the user that GITHUB_TOKEN is not defined
            return;
        }

        passthru('travis env set GITHUB_TOKEN ' . $github_token);
    }

    protected function hasCLUConfig($travisContents)
    {
        return strpos($travisContents, 'danielbachhuber/composer-lock-updater') !== false;
    }

    protected function readTravisConfig($travisConfigPath)
    {
        // TODO: run `travis init`, or just advise
        // user to do so, to make Travis start watching
        // the project.
        if (!file_exists($travisConfigPath)) {
            return $this->initTravisFile();
        }
        return file_get_contents($travisConfigPath);
    }

    protected function configureCLU($travisContents, $auto_merge)
    {
        $template_name = $auto_merge ? 'clu-automerge-travis.yml' : 'clu-travis.yml';
        $cluConfigContents = $this->getTemplate($template_name);
        $cluConfig = Yaml::parse($cluConfigContents);
        $travisConfig = Yaml::parse($travisContents);

        $travisConfig = $this->combineConfig($travisConfig, $cluConfig);

        // TODO: Add DO_POST_BUILD_ACTIONS to the first matrix: include: php: section
        // TODO: Fail if there is no matrix: include: section (or fix it - but probably fail)

        $combinedContents = Yaml::dump($travisConfig, PHP_INT_MAX, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $travisContents = $combinedContents;

        return $travisContents;
    }

    protected function combineConfig($mainConfig, $injectedConfig)
    {
        foreach ($injectedConfig as $section => $injectedContents) {
            if (!isset($mainConfig[$section])) {
                $mainConfig[$section] = $injectedContents;
            } else {
                $mainConfig[$section] = array_merge($mainConfig[$section], $injectedContents);
            }
        }

        return $mainConfig;
    }

    protected function initTravisFile()
    {
        return $this->getTemplate('generic-travis.yml');
    }

    protected function templateDir()
    {
        return dirname(dirname(__DIR__)) . '/templates';
    }

    protected function templatePath($templateName)
    {
        return $this->templateDir() . '/' . $templateName;
    }

    protected function getTemplate($templateName)
    {
        $templatePath = $this->templatePath($templateName);
        if (!file_exists($templatePath)) {
            throw new \Exception('Could not find template ' . $templateName);
        }
        return file_get_contents($templatePath);
    }
}
