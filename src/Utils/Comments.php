<?php

namespace CI\Utils;

class Comments
{
    protected $headComments;
    protected $accumulated;
    protected $stored;
    protected $endComments;

    public function __construct()
    {
        $this->headComments = false;
        $this->accumulated = [];
        $this->stored = [];
        $this->endComments = [];
    }

    public function collect(array $contentLines)
    {
        foreach ($contentLines as $line) {
            if (empty($line) || $this->isComment($line)) {
                $this->accumulate($line);
            } else {
                $this->storeAccumulated($line);
            }
        }
        $this->endCollect();
    }

    public function inject(array $contentLines)
    {
        $result = $this->headComments === false ? [] : $this->headComments;
        foreach ($contentLines as $line) {
            $fetched = $this->find($line);
            $result = array_merge($result, $fetched);

            $result[] = $line;
        }
        $result = array_merge($result, $this->endComments);
        return $result;
    }

    protected function isComment($line)
    {
        return preg_match('%^ *#%', $line);
    }

    protected function endCollect()
    {
        $this->endComments = $this->accumulated;
        $this->accumulated = [];
    }

    protected function accumulate($line)
    {
        $this->accumulated[] = $line;
    }

    protected function storeAccumulated($line)
    {
        if ($this->headComments === false) {
            $this->headComments = $this->accumulated;
            $this->accumulated = [];
            return;
        }
        if (!empty($this->accumulated)) {
            $this->stored[$line][] = $this->accumulated;
            $this->accumulated = [];
        }
    }

    protected function find($line)
    {
        if (!isset($this->stored[$line]) || empty($this->stored[$line])) {
            return [];
        }

        return array_shift($this->stored[$line]);
    }
}
