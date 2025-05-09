<?php namespace thiagoalessio\TesseractOCR;

class Process {

    private $stdin;
    private $stdout;
    private $stderr;
    private $handle;
    private $startTime;

    public function __construct($command)
    {
        $this->startTime = microtime(true);
        $streamDescriptors = [
            array("pipe", "r"),
            array("pipe", "w"),
            array("pipe", "w")
        ];
        $this->handle = proc_open($command, $streamDescriptors, $pipes, NULL, NULL, ["bypass_shell" => true]);
        list($this->stdin, $this->stdout, $this->stderr) = $pipes;

        FriendlyErrors::checkProcessCreation($this->handle, $command);

        //This is can avoid deadlock on some cases (when stderr buffer is filled up before writing to stdout and vice-versa)
        stream_set_blocking($this->stdout, 0);
        stream_set_blocking($this->stderr, 0);
    }

    public function write($data, $len)
    {
        $total = 0;
        do
        {
            $res = fwrite($this->stdin, substr($data, $total));
        } while($res && $total += $res < $len);
        return $total === $len;
    }


    public function wait($timeout = 0)
    {
        $running = true;
        $data = ["out" => "", "err" => ""];
        while (($running === true) && !$this->hasTimedOut($timeout))
        {
            $data["out"] .= fread($this->stdout, 8192);
            $data["err"] .= fread($this->stderr, 8192);
            $procInfo = proc_get_status($this->handle);
            $running = $procInfo["running"];
            if ($running) {
                usleep(1000); // Sleep 1ms to yield CPU time
            }
        }
        return $data;
    }

    public function close()
    {
        $this->closeStream($this->stdin);
        $this->closeStream($this->stdout);
        $this->closeStream($this->stderr);
        return proc_close($this->handle);
    }

    public function closeStdin()
    {
        $this->closeStream($this->stdin);
    }

    private function hasTimedOut($timeout)
    {
        return (($timeout > 0) &&  ($this->startTime + $timeout < microtime(true)));    
    }
    
    private function closeStream(&$stream)
    {
        if ($stream !== NULL)
        {
            fclose($stream);
            $stream = NULL;
        }
    }
}
