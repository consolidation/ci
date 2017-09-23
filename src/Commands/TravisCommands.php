<?php

namespace CI\Commands;

class TravisCommands
{
    /**
     * Set up this project to auto-update via the
     * Composer Lock Update command on Travis.
     *
     * @command travis:clu
     */
    public function travisComposerLockUpdate()
    {
        print "cwd is " . getcwd() . "\n";
    }
}
